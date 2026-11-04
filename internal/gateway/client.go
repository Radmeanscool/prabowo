package gateway

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/gorilla/websocket"
)

const (
	defaultAPIVersion   = 9
	defaultHelloTimeout = 20 * time.Second
	writeTimeout        = 10 * time.Second
	maxFrameBytes       = 64 << 20
)

var (
	errClosed         = errors.New("gateway: connection closed")
	errReconnect      = errors.New("gateway: reconnect requested by server")
	errInvalidSession = errors.New("gateway: invalid session")
)

// Options mengatur koneksi gateway.
type Options struct {
	Token      string
	APIVersion int
	Compress   bool

	// GatewayURL opsional untuk override (mis. server uji).
	GatewayURL string

	UserAgent    string
	Capabilities int

	// Properties opsional; bila nil dibuat default meniru selfbot.
	Properties map[string]any

	// OnReady dipanggil saat dispatch READY diterima.
	OnReady func(*Ready)
	// OnEvent dipanggil untuk setiap dispatch. Harus non-blocking.
	OnEvent func(Event)

	// OnFatalClose dipanggil saat koneksi ditutup dengan kode yang tidak
	// bisa dipulihkan (mis. 4004 token invalid).
	OnFatalClose func(code int)

	Dialer       *websocket.Dialer
	HelloTimeout time.Duration
}

// Client adalah koneksi gateway Discord untuk satu akun.
type Client struct {
	opts   Options
	dialer *websocket.Dialer

	mu        sync.RWMutex
	conn      *websocket.Conn
	sessionID string
	resumeURL string
	ping      time.Duration

	writeMu  sync.Mutex
	seq      int
	interval time.Duration
	acked    bool
	lastPing time.Time
}

// New memvalidasi opsi dan membuat Client.
func New(opts Options) (*Client, error) {
	token := strings.TrimSpace(opts.Token)
	if token == "" {
		return nil, errors.New("gateway: token is required")
	}
	if strings.ContainsAny(token, " \t\r\n") {
		return nil, errors.New("gateway: token contains whitespace")
	}
	opts.Token = token

	if opts.APIVersion <= 0 {
		opts.APIVersion = defaultAPIVersion
	}
	if opts.UserAgent == "" {
		opts.UserAgent = rest.DefaultUserAgent
	}
	if opts.Properties == nil {
		opts.Properties = defaultProperties(opts.UserAgent)
	}
	if opts.HelloTimeout <= 0 {
		opts.HelloTimeout = defaultHelloTimeout
	}

	dialer := opts.Dialer
	if dialer == nil {
		dialer = websocket.DefaultDialer
	}

	return &Client{opts: opts, dialer: dialer, acked: true}, nil
}

func (c *Client) gatewayURL(resuming bool) string {
	base := ""
	if resuming {
		c.mu.RLock()
		base = c.resumeURL
		c.mu.RUnlock()
	}
	if base == "" {
		base = c.opts.GatewayURL
	}
	if base == "" {
		base = "wss://gateway.discord.gg/"
	}

	u, err := url.Parse(base)
	if err != nil {
		return base
	}

	q := u.Query()
	if q.Get("v") == "" {
		q.Set("v", strconv.Itoa(c.opts.APIVersion))
	}
	if q.Get("encoding") == "" {
		q.Set("encoding", "json")
	}
	if c.opts.Compress {
		q.Set("compress", "zlib-stream")
	}
	u.RawQuery = q.Encode()
	return u.String()
}

// Ping mengembalikan latency heartbeat terakhir.
func (c *Client) Ping() time.Duration {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.ping
}

// SessionID mengembalikan session aktif (kosong bila belum READY).
func (c *Client) SessionID() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.sessionID
}

// ResumeURL mengembalikan URL resume dari READY.
func (c *Client) ResumeURL() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.resumeURL
}

func (c *Client) getConn() *websocket.Conn {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.conn
}

func (c *Client) setConn(conn *websocket.Conn) {
	c.mu.Lock()
	c.conn = conn
	c.mu.Unlock()
}

// Connect melakukan satu sesi: dial, tunggu HELLO, IDENTIFY, lalu loop
// sampai koneksi putus atau ctx dibatalkan.
func (c *Client) Connect(ctx context.Context) error {
	c.mu.RLock()
	resuming := c.sessionID != "" && c.resumeURL != ""
	c.mu.RUnlock()

	headers := http.Header{}
	headers.Set("User-Agent", c.opts.UserAgent)

	conn, resp, err := c.dialer.DialContext(ctx, c.gatewayURL(resuming), headers)
	if err != nil {
		if resp != nil {
			return fmt.Errorf("gateway: dial (HTTP %d): %w", resp.StatusCode, err)
		}
		return fmt.Errorf("gateway: dial: %w", err)
	}
	conn.SetReadLimit(maxFrameBytes)
	c.setConn(conn)
	if !resuming {
		c.seq = 0
	}
	c.acked = true

	defer func() {
		c.setConn(nil)
		_ = conn.Close()
	}()

	packets := make(chan Payload, 32)
	errs := make(chan error, 1)

	var pw *io.PipeWriter
	if c.opts.Compress {
		pr, w := io.Pipe()
		pw = w
		defer func() {
			_ = pw.Close()
			_ = pr.Close()
		}()
		go c.decodeZlib(ctx, pr, packets, errs)
	}
	go c.readLoop(ctx, conn, pw, packets, errs)

	hb := newHeartbeatTimer()
	defer hb.Stop()

	helloTimeout := time.NewTimer(c.opts.HelloTimeout)
	defer stopTimer(helloTimeout)

	gotHello := false
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()

		case err := <-errs:
			return err

		case <-helloTimeout.C:
			return errors.New("gateway: timeout waiting for HELLO")

		case <-hb.C():
			if !c.acked {
				return errors.New("gateway: heartbeat ACK not received (zombie)")
			}
			if err := c.sendHeartbeat(ctx); err != nil {
				return err
			}
			hb.Reset(c.interval)

		case p := <-packets:
			if err := c.handlePacket(ctx, &p, hb, helloTimeout, &gotHello, resuming); err != nil {
				return err
			}
		}
	}
}

// Run menjaga koneksi tetap hidup dengan reconnect + backoff sampai ctx
// dibatalkan. Close code fatal (mis. 4004) menghentikan loop.
func (c *Client) Run(ctx context.Context) error {
	attempt := 0
	for {
		err := c.Connect(ctx)
		if ctx.Err() != nil {
			return ctx.Err()
		}
		if err == nil {
			return nil
		}
		if isFatalClose(err) {
			if c.opts.OnFatalClose != nil {
				var closeErr *websocket.CloseError
				if errors.As(err, &closeErr) {
					c.opts.OnFatalClose(closeErr.Code)
				}
			}
			return err
		}

		delay := backoff(attempt)

		timer := time.NewTimer(delay)
		select {
		case <-ctx.Done():
			stopTimer(timer)
			return ctx.Err()
		case <-timer.C:
		}

		if attempt < 5 {
			attempt++
		}
	}
}

func backoff(attempt int) time.Duration {
	d := time.Duration(1<<uint(attempt)) * time.Second
	if d > 30*time.Second {
		d = 30 * time.Second
	}
	return d
}

func (c *Client) handlePacket(ctx context.Context, p *Payload, hb *heartbeatTimer, helloTimer *time.Timer, gotHello *bool, resuming bool) error {
	if p.S != nil {
		c.seq = *p.S
	}

	switch p.Op {
	case OpHello:
		if *gotHello {
			return errors.New("gateway: HELLO received twice")
		}
		var hello Hello
		if err := json.Unmarshal(p.D, &hello); err != nil {
			return fmt.Errorf("gateway: decode HELLO: %w", err)
		}
		if hello.HeartbeatInterval <= 0 {
			return errors.New("gateway: invalid heartbeat_interval")
		}
		*gotHello = true
		c.interval = time.Duration(hello.HeartbeatInterval) * time.Millisecond
		c.acked = true
		hb.Reset(c.interval)
		stopTimer(helloTimer)
		if resuming {
			return c.resume(ctx)
		}
		return c.identify(ctx)

	case OpHeartbeat:
		return c.sendHeartbeat(ctx)

	case OpHeartbeatAck:
		c.acked = true
		ping := time.Since(c.lastPing)
		c.mu.Lock()
		c.ping = ping
		c.mu.Unlock()

	case OpReconnect:
		return errReconnect

	case OpInvalidSession:
		c.mu.Lock()
		c.sessionID = ""
		c.resumeURL = ""
		c.mu.Unlock()
		c.seq = 0
		return errInvalidSession

	case OpDispatch:
		return c.handleDispatch(ctx, p)
	}
	return nil
}

func (c *Client) handleDispatch(ctx context.Context, p *Payload) error {
	if p.T == "READY" {
		var ready Ready
		if err := json.Unmarshal(p.D, &ready); err != nil {
			logger.Warn("DISCORD", "failed to decode READY:", err)
		} else {
			c.mu.Lock()
			c.sessionID = ready.SessionID
			c.resumeURL = ready.ResumeGatewayURL
			c.mu.Unlock()

			logger.Log("LOGIN", fmt.Sprintf("welcome %s", ready.User.Tag()))

			if c.opts.OnReady != nil {
				c.opts.OnReady(&ready)
			}

			if err := c.subscribeGuilds(ctx, ready.Guilds); err != nil {
				logger.Warn("DISCORD", "guild subscription failed:", err)
			}
		}
	}

	if c.opts.OnEvent != nil {
		c.opts.OnEvent(Event{Type: p.T, Data: p.D, Sequence: c.seq})
	}
	return nil
}

func (c *Client) identify(ctx context.Context) error {
	data := identifyData{
		Token:        c.opts.Token,
		Capabilities: c.opts.Capabilities,
		Properties:   c.opts.Properties,
		Compress:     false,
		Presence: presenceData{
			Activities: []any{},
			AFK:        true,
			Since:      0,
			Status:     "online",
		},
		ClientState: clientState{GuildVersions: map[string]string{}},
	}
	raw, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("gateway: encode IDENTIFY: %w", err)
	}

	return c.sendPayload(ctx, Payload{Op: OpIdentify, D: raw})
}

func (c *Client) resume(ctx context.Context) error {
	c.mu.RLock()
	data := resumeData{Token: c.opts.Token, SessionID: c.sessionID, Seq: c.seq}
	c.mu.RUnlock()

	raw, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("gateway: encode RESUME: %w", err)
	}
	return c.sendPayload(ctx, Payload{Op: OpResume, D: raw})
}

func (c *Client) sendHeartbeat(ctx context.Context) error {
	c.acked = false
	c.lastPing = time.Now()

	var data json.RawMessage = []byte("null")
	if c.seq > 0 {
		raw, err := json.Marshal(c.seq)
		if err != nil {
			return err
		}
		data = raw
	}
	return c.sendPayload(ctx, Payload{Op: OpHeartbeat, D: data})
}

func (c *Client) sendPayload(ctx context.Context, p Payload) error {
	select {
	case <-ctx.Done():
		return ctx.Err()
	default:
	}

	conn := c.getConn()
	if conn == nil {
		return errors.New("gateway: connection unavailable")
	}

	data, err := json.Marshal(p)
	if err != nil {
		return fmt.Errorf("gateway: encode payload: %w", err)
	}

	c.writeMu.Lock()
	defer c.writeMu.Unlock()

	_ = conn.SetWriteDeadline(time.Now().Add(writeTimeout))
	if err := conn.WriteMessage(websocket.TextMessage, data); err != nil {
		return fmt.Errorf("gateway: send payload: %w", err)
	}
	_ = conn.SetWriteDeadline(time.Time{})
	return nil
}

func (c *Client) readLoop(ctx context.Context, conn *websocket.Conn, pw *io.PipeWriter, packets chan<- Payload, errs chan<- error) {
	for {
		_, data, err := conn.ReadMessage()
		if err != nil {
			sendErr(errs, err)
			return
		}

		if pw != nil {
			if _, err := pw.Write(data); err != nil {
				sendErr(errs, err)
				return
			}
			continue
		}

		var p Payload
		if err := json.Unmarshal(data, &p); err != nil {
			logger.Warn("DISCORD", "invalid payload:", err)
			continue
		}
		select {
		case packets <- p:
		case <-ctx.Done():
			return
		}
	}
}

func isFatalClose(err error) bool {
	var closeErr *websocket.CloseError
	if !errors.As(err, &closeErr) {
		return false
	}
	switch closeErr.Code {
	case 4004, 4010, 4011, 4012, 4013, 4014:
		return true
	}
	return false
}

type heartbeatTimer struct {
	t *time.Timer
}

func newHeartbeatTimer() *heartbeatTimer {
	return &heartbeatTimer{t: time.NewTimer(time.Hour)}
}

func (h *heartbeatTimer) C() <-chan time.Time { return h.t.C }

func (h *heartbeatTimer) Reset(d time.Duration) {
	if d <= 0 {
		d = time.Millisecond
	}
	stopTimer(h.t)
	h.t.Reset(d)
}

func (h *heartbeatTimer) Stop() { h.t.Stop() }

func stopTimer(t *time.Timer) {
	if !t.Stop() {
		select {
		case <-t.C:
		default:
		}
	}
}
