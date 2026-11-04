package gateway

import (
	"bytes"
	"compress/zlib"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"strings"
	"sync/atomic"
	"testing"
	"time"

	"github.com/gorilla/websocket"
)

const testToken = "TESTTOKEN.abcd.efgh"

func upgrade(w http.ResponseWriter, r *http.Request) (*websocket.Conn, error) {
	u := websocket.Upgrader{CheckOrigin: func(*http.Request) bool { return true }}
	return u.Upgrade(w, r, nil)
}

func wsURL(srv *httptest.Server) string {
	return "ws" + strings.TrimPrefix(srv.URL, "http")
}

func newTestClient(t *testing.T, url string, opts Options) *Client {
	t.Helper()
	opts.Token = testToken
	opts.GatewayURL = url
	c, err := New(opts)
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	return c
}

func waitReady(t *testing.T, ch <-chan *Ready) *Ready {
	t.Helper()
	select {
	case r := <-ch:
		return r
	case <-time.After(3 * time.Second):
		t.Fatal("timeout menunggu READY")
		return nil
	}
}

func waitConnect(t *testing.T, done <-chan error) {
	t.Helper()
	select {
	case err := <-done:
		if err == nil {
			t.Fatal("Connect harus mengembalikan error saat ctx dibatalkan")
		}
	case <-time.After(15 * time.Second):
		t.Fatal("Connect tidak berhenti setelah ctx dibatalkan")
	}
}

func TestNewValidation(t *testing.T) {
	if _, err := New(Options{}); err == nil {
		t.Fatal("expected error untuk token kosong")
	}
	if _, err := New(Options{Token: "a b"}); err == nil {
		t.Fatal("expected error untuk token ber-whitespace")
	}
	c, err := New(Options{Token: testToken})
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	if c.opts.APIVersion != 9 {
		t.Fatalf("api version default salah: %d", c.opts.APIVersion)
	}
	if c.opts.Properties["browser"] != "Discord Client" {
		t.Fatalf("properties default tidak dipakai: %+v", c.opts.Properties)
	}
	if strings.Contains(c.gatewayURL(false), "compress=zlib-stream") {
		t.Fatalf("compress default harus mati: %q", c.gatewayURL(false))
	}
}

func TestHandshakeIdentifyAndReady(t *testing.T) {
	identifyCh := make(chan Payload, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if !strings.Contains(r.URL.RawQuery, "encoding=json") {
			t.Errorf("query encoding hilang: %q", r.URL.RawQuery)
		}
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()

		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}})

		_, data, err := conn.ReadMessage()
		if err != nil {
			return
		}
		var p Payload
		if err := json.Unmarshal(data, &p); err != nil {
			t.Errorf("decode identify: %v", err)
			return
		}
		identifyCh <- p

		_ = conn.WriteJSON(map[string]any{
			"op": 0, "s": 1, "t": "READY",
			"d": map[string]any{
				"v":                  9,
				"session_id":         "session-abcdef123456",
				"resume_gateway_url": "wss://resume.example",
				"user": map[string]any{
					"id": "42", "username": "tester", "discriminator": "0",
				},
			},
		})

		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
		}
	}))
	defer srv.Close()

	readyCh := make(chan *Ready, 1)
	c := newTestClient(t, wsURL(srv), Options{
		OnReady: func(r *Ready) { readyCh <- r },
	})

	ctx, cancel := context.WithCancel(context.Background())
	done := make(chan error, 1)
	go func() { done <- c.Connect(ctx) }()

	ready := waitReady(t, readyCh)
	if ready.User.ID != "42" || ready.SessionID != "session-abcdef123456" {
		t.Fatalf("READY salah: %+v", ready)
	}
	if c.SessionID() != ready.SessionID {
		t.Fatalf("SessionID client salah: %q", c.SessionID())
	}
	if c.ResumeURL() != "wss://resume.example" {
		t.Fatalf("ResumeURL client salah: %q", c.ResumeURL())
	}

	var identify Payload
	select {
	case identify = <-identifyCh:
	case <-time.After(15 * time.Second):
		t.Fatal("server tidak menerima IDENTIFY")
	}
	if identify.Op != OpIdentify {
		t.Fatalf("op identify salah: %d", identify.Op)
	}

	var d map[string]any
	if err := json.Unmarshal(identify.D, &d); err != nil {
		t.Fatalf("decode data identify: %v", err)
	}
	if d["token"] != testToken {
		t.Fatalf("token tidak terkirim mentah: %v", d["token"])
	}
	if cap, _ := d["capabilities"].(float64); cap != 0 {
		t.Fatalf("capabilities default harus 0, dapat %v", d["capabilities"])
	}
	if compress, _ := d["compress"].(bool); compress {
		t.Fatal("payload compress harus false")
	}
	props, _ := d["properties"].(map[string]any)
	if props["os"] != "Windows" || props["browser"] != "Discord Client" {
		t.Fatalf("properties tidak meniru selfbot: %v", props)
	}
	if id, _ := props["client_launch_id"].(string); id == "" {
		t.Fatal("client_launch_id kosong")
	}
	presence, _ := d["presence"].(map[string]any)
	if presence["status"] != "online" || presence["afk"] != true {
		t.Fatalf("presence salah: %+v", presence)
	}
	if _, ok := presence["activities"].([]any); !ok {
		t.Fatalf("presence.activities bukan array: %+v", presence["activities"])
	}

	cancel()
	waitConnect(t, done)
}

func TestHeartbeat(t *testing.T) {
	var heartbeats atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()

		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 25}})

		for {
			_, data, err := conn.ReadMessage()
			if err != nil {
				return
			}
			var p Payload
			if err := json.Unmarshal(data, &p); err != nil {
				continue
			}
			switch p.Op {
			case OpIdentify:
				_ = conn.WriteJSON(map[string]any{
					"op": 0, "s": 1, "t": "READY",
					"d": map[string]any{
						"v": 9, "session_id": "s1",
						"user": map[string]any{"id": "42", "username": "tester"},
					},
				})
			case OpHeartbeat:
				heartbeats.Add(1)
				_ = conn.WriteJSON(map[string]any{"op": 11})
			}
		}
	}))
	defer srv.Close()

	readyCh := make(chan *Ready, 1)
	c := newTestClient(t, wsURL(srv), Options{OnReady: func(r *Ready) { readyCh <- r }})

	ctx, cancel := context.WithCancel(context.Background())
	done := make(chan error, 1)
	go func() { done <- c.Connect(ctx) }()

	waitReady(t, readyCh)
	time.Sleep(150 * time.Millisecond)

	if got := heartbeats.Load(); got < 2 {
		t.Fatalf("expected minimal 2 heartbeat, dapat %d", got)
	}

	cancel()
	waitConnect(t, done)
}

func TestCompressedZlibStream(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if !strings.Contains(r.URL.RawQuery, "compress=zlib-stream") {
			t.Errorf("query compress hilang: %q", r.URL.RawQuery)
		}
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()

		var buf bytes.Buffer
		zw := zlib.NewWriter(&buf)
		defer zw.Close()

		send := func(v any) error {
			raw, err := json.Marshal(v)
			if err != nil {
				return err
			}
			buf.Reset()
			if _, err := zw.Write(raw); err != nil {
				return err
			}
			if err := zw.Flush(); err != nil {
				return err
			}
			return conn.WriteMessage(websocket.BinaryMessage, buf.Bytes())
		}

		if err := send(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}}); err != nil {
			return
		}

		if _, _, err := conn.ReadMessage(); err != nil {
			return
		}

		_ = send(map[string]any{
			"op": 0, "s": 1, "t": "READY",
			"d": map[string]any{
				"v": 9, "session_id": "zsess",
				"user": map[string]any{"id": "42", "username": "tester"},
			},
		})

		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
		}
	}))
	defer srv.Close()

	readyCh := make(chan *Ready, 1)
	c := newTestClient(t, wsURL(srv), Options{
		Compress: true,
		OnReady:  func(r *Ready) { readyCh <- r },
	})

	ctx, cancel := context.WithCancel(context.Background())
	done := make(chan error, 1)
	go func() { done <- c.Connect(ctx) }()

	ready := waitReady(t, readyCh)
	if ready.SessionID != "zsess" {
		t.Fatalf("READY terkompresi salah: %+v", ready)
	}

	cancel()
	waitConnect(t, done)
}

func TestHelloTimeout(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()
		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
		}
	}))
	defer srv.Close()

	c := newTestClient(t, wsURL(srv), Options{HelloTimeout: 60 * time.Millisecond})

	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()

	err := c.Connect(ctx)
	if err == nil || !strings.Contains(err.Error(), "HELLO") {
		t.Fatalf("expected timeout HELLO, dapat %v", err)
	}
}

func TestFatalCloseCodes(t *testing.T) {
	cases := map[int]bool{
		4004: true,
		4010: true,
		4011: true,
		4012: true,
		4013: true,
		4014: true,
		4000: false,
		4009: false,
	}
	for code, want := range cases {
		err := &websocket.CloseError{Code: code}
		if got := isFatalClose(err); got != want {
			t.Errorf("isFatalClose(%d) = %v, want %v", code, got, want)
		}
	}
	if isFatalClose(errors.New("biasa")) {
		t.Error("error non-close tidak boleh fatal")
	}
}

func TestRunStopsOnFatalClose(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()
		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}})
		_, _, _ = conn.ReadMessage()
		_ = conn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(4004, "auth failed"))
		time.Sleep(20 * time.Millisecond)
	}))
	defer srv.Close()

	c := newTestClient(t, wsURL(srv), Options{})

	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()

	start := time.Now()
	err := c.Run(ctx)
	if err == nil {
		t.Fatal("expected error fatal")
	}
	if !errors.Is(err, context.DeadlineExceeded) && !isFatalClose(err) {
		t.Fatalf("expected close fatal, dapat %v", err)
	}
	if time.Since(start) > time.Second {
		t.Fatal("Run tidak berhenti pada close fatal")
	}
}

func TestGuildSubscriptions(t *testing.T) {
	subCh := make(chan Payload, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()

		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}})
		if _, _, err := conn.ReadMessage(); err != nil {
			return
		}

		_ = conn.WriteJSON(map[string]any{
			"op": 0, "s": 1, "t": "READY",
			"d": map[string]any{
				"v": 9, "session_id": "s1",
				"user":   map[string]any{"id": "42", "username": "tester"},
				"guilds": []map[string]any{{"id": "7"}, {"id": "8"}},
			},
		})

		_, data, err := conn.ReadMessage()
		if err != nil {
			return
		}
		var p Payload
		if err := json.Unmarshal(data, &p); err != nil {
			t.Errorf("decode subscription: %v", err)
			return
		}
		subCh <- p

		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
		}
	}))
	defer srv.Close()

	readyCh := make(chan *Ready, 1)
	c := newTestClient(t, wsURL(srv), Options{OnReady: func(r *Ready) { readyCh <- r }})

	ctx, cancel := context.WithCancel(context.Background())
	done := make(chan error, 1)
	go func() { done <- c.Connect(ctx) }()

	waitReady(t, readyCh)

	var sub Payload
	select {
	case sub = <-subCh:
	case <-time.After(15 * time.Second):
		t.Fatal("server tidak menerima guild subscription")
	}

	if OpGuildSubscriptionsBulk != 37 {
		t.Fatalf("op GUILD_SUBSCRIPTIONS_BULK harus 37, dapat %d", OpGuildSubscriptionsBulk)
	}
	if sub.Op != 37 {
		t.Fatalf("op subscription salah: %d", sub.Op)
	}

	var d struct {
		Subscriptions map[string]guildSubscription `json:"subscriptions"`
	}
	if err := json.Unmarshal(sub.D, &d); err != nil {
		t.Fatalf("decode data subscription: %v", err)
	}
	if len(d.Subscriptions) != 2 {
		t.Fatalf("expected 2 guild, dapat %d", len(d.Subscriptions))
	}
	for _, id := range []string{"7", "8"} {
		g, ok := d.Subscriptions[id]
		if !ok {
			t.Fatalf("guild %s tidak disubscribe", id)
		}
		if !g.Typing || !g.Threads || !g.Activities || !g.MemberUpdates {
			t.Fatalf("flag subscription guild %s salah: %+v", id, g)
		}
	}

	cancel()
	waitConnect(t, done)
}

func TestResumeAfterDisconnect(t *testing.T) {
	var srv *httptest.Server
	var connID atomic.Int32
	resumeCh := make(chan Payload, 1)

	srv = httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()

		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}})

		if connID.Add(1) == 1 {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
			_ = conn.WriteJSON(map[string]any{
				"op": 0, "s": 1, "t": "READY",
				"d": map[string]any{
					"v": 9, "session_id": "sess1",
					"resume_gateway_url": "ws" + strings.TrimPrefix(srv.URL, "http"),
					"user":               map[string]any{"id": "42", "username": "tester"},
				},
			})
			time.Sleep(20 * time.Millisecond)
			_ = conn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(4000, "bye"))
			return
		}

		_, data, err := conn.ReadMessage()
		if err != nil {
			return
		}
		var p Payload
		if err := json.Unmarshal(data, &p); err != nil {
			t.Errorf("decode resume: %v", err)
			return
		}
		resumeCh <- p

		_ = conn.WriteJSON(map[string]any{"op": 0, "s": 2, "t": "RESUMED", "d": map[string]any{}})

		for {
			if _, _, err := conn.ReadMessage(); err != nil {
				return
			}
		}
	}))
	defer srv.Close()

	readyCh := make(chan *Ready, 1)
	resumedCh := make(chan struct{}, 1)
	c := newTestClient(t, wsURL(srv), Options{
		OnReady: func(r *Ready) { readyCh <- r },
		OnEvent: func(e Event) {
			if e.Type == "RESUMED" {
				select {
				case resumedCh <- struct{}{}:
				default:
				}
			}
		},
	})

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	if err := c.Connect(ctx); err == nil {
		t.Fatal("Connect pertama harus berakhir dengan close")
	}
	waitReady(t, readyCh)

	done := make(chan error, 1)
	go func() { done <- c.Connect(ctx) }()

	select {
	case <-resumedCh:
	case <-time.After(15 * time.Second):
		t.Fatal("tidak menerima RESUMED")
	}

	select {
	case p := <-resumeCh:
		if p.Op != OpResume {
			t.Fatalf("op resume salah: %d", p.Op)
		}
		var d resumeData
		if err := json.Unmarshal(p.D, &d); err != nil {
			t.Fatalf("decode data resume: %v", err)
		}
		if d.SessionID != "sess1" || d.Seq != 1 || d.Token != testToken {
			t.Fatalf("data resume salah: %+v", d)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("server tidak menerima RESUME")
	}

	cancel()
	select {
	case <-done:
	case <-time.After(15 * time.Second):
		t.Fatal("Connect kedua tidak berhenti")
	}
}

func TestFatalCloseCallback(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		conn, err := upgrade(w, r)
		if err != nil {
			return
		}
		defer conn.Close()
		_ = conn.WriteJSON(map[string]any{"op": 10, "d": map[string]any{"heartbeat_interval": 1000}})
		_, _, _ = conn.ReadMessage()
		_ = conn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(4004, "auth failed"))
		time.Sleep(20 * time.Millisecond)
	}))
	defer srv.Close()

	codes := make(chan int, 1)
	c := newTestClient(t, wsURL(srv), Options{
		OnFatalClose: func(code int) {
			select {
			case codes <- code:
			default:
			}
		},
	})

	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()

	_ = c.Run(ctx)

	select {
	case code := <-codes:
		if code != 4004 {
			t.Fatalf("close code salah: %d", code)
		}
	case <-time.After(time.Second):
		t.Fatal("OnFatalClose tidak dipanggil")
	}
}
