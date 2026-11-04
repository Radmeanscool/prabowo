package rest

import (
	"bytes"
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

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/identity"
)

const (
	// DefaultBaseURL adalah base REST Discord yang dipakai library JS.
	DefaultBaseURL = "https://discord.com/api/v9"
	// DefaultUserAgent meniru client Discord desktop (sama dengan
	// discord.js-selfbot-v13).
	DefaultUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 " +
		"(KHTML, like Gecko) discord/1.0.9210 Chrome/134.0.6998.205 " +
		"Electron/35.3.0 Safari/537.36"

	defaultTimeout    = 30 * time.Second
	defaultMaxRetries = 3
	maxResponseBytes  = 4 << 20
)

// APIError adalah respons non-2xx dari Discord.
type APIError struct {
	Status     int
	Code       int
	Message    string
	RetryAfter time.Duration
}

func (e *APIError) Error() string {
	if e.Code != 0 {
		return fmt.Sprintf("discord api %d: %s (code %d)", e.Status, e.Message, e.Code)
	}
	return fmt.Sprintf("discord api %d: %s", e.Status, e.Message)
}

// Options mengatur pembuatan Client.
type Options struct {
	Token     string
	BaseURL   string
	UserAgent string

	// HTTPClient opsional; bila nil dibuat dengan Timeout.
	HTTPClient *http.Client

	// Timeout hanya dipakai bila HTTPClient nil. 0 = 30 detik.
	Timeout time.Duration

	// MaxRetries untuk respons 429. 0 = 3.
	MaxRetries int

	// SuperProperties opsional (sudah base64). Bila kosong dibuat default.
	SuperProperties string

	// Profil adalah identitas klien (User-Agent, locale, timezone, build
	// number). Bila kosong dipakai identity.DefaultProfil(), yang membaca nilai
	// asli dari mesin ini alih-alih angka karangan.
	Profil *identity.Profil

	// Fingerprint opsional; bila nil dibuat otomatis dan di-cache.
	Fingerprint *identity.Fingerprint
}

// Client adalah klien REST Discord dengan user token.
type Client struct {
	http            *http.Client
	baseURL         string
	token           string
	userAgent       string
	maxRetries      int
	superProperties string
	locale          string
	timezone        string
	fingerprint     *identity.Fingerprint

	// slashCache menyimpan hasil lookup application-command agar slash
	// command berikutnya tidak perlu GET command-index tiap kali.
	slashMu    sync.Mutex
	slashCache map[string]appCommand

	// nonceMu melindungi nonceWaiters. Meniru penantian MESSAGE_CREATE
	// ber-nonce di discord.js-selfbot-v13: setelah mengirim interaksi tanpa
	// menunggu balasan HTTP, kita menunggu pesan balasan yang membawa nonce
	// yang sama.
	nonceMu      sync.Mutex
	nonceWaiters map[string]chan struct{}
}

// New membuat Client. Token wajib diisi; tidak ada prefix "Bot ".
func New(opts Options) (*Client, error) {
	token := strings.TrimSpace(opts.Token)
	if token == "" {
		return nil, errors.New("rest: token is required")
	}
	if strings.ContainsAny(token, " \t\r\n") {
		return nil, errors.New("rest: token contains whitespace")
	}

	base := strings.TrimRight(strings.TrimSpace(opts.BaseURL), "/")
	if base == "" {
		base = DefaultBaseURL
	}
	if _, err := url.ParseRequestURI(base); err != nil {
		return nil, fmt.Errorf("rest: invalid base url: %w", err)
	}

	ua := strings.TrimSpace(opts.UserAgent)
	profil := opts.Profil
	if profil == nil {
		p := identity.DefaultProfil()
		profil = &p
	}
	if ua == "" {
		ua = profil.UserAgent()
	}

	hc := opts.HTTPClient
	if hc == nil {
		timeout := opts.Timeout
		if timeout <= 0 {
			timeout = defaultTimeout
		}
		hc = &http.Client{Timeout: timeout}
	}

	maxRetries := opts.MaxRetries
	if maxRetries <= 0 {
		maxRetries = defaultMaxRetries
	}

	sp := opts.SuperProperties
	if sp == "" {
		// Nilai asli dari mesin ini (build number resmi, Electron/Chrome yang
		// benar), bukan angka karangan seperti sebelumnya.
		sp = profil.BuildSuperProperties()
	}

	fp := opts.Fingerprint
	if fp == nil {
		fp = identity.NewFingerprint(hc)
	}

	return &Client{
		http:            hc,
		baseURL:         base,
		token:           token,
		userAgent:       ua,
		maxRetries:      maxRetries,
		superProperties: sp,
		locale:          profil.Locale,
		timezone:        profil.Timezone,
		fingerprint:     fp,
		slashCache:      make(map[string]appCommand),
		nonceWaiters:    make(map[string]chan struct{}),
	}, nil
}

// BaseURL mengembalikan base URL yang dipakai.
func (c *Client) BaseURL() string { return c.baseURL }

func (c *Client) do(ctx context.Context, method, path string, body, out any) error {
	var payload []byte
	if body != nil {
		var err error
		payload, err = json.Marshal(body)
		if err != nil {
			return fmt.Errorf("rest: encode body: %w", err)
		}
	}

	endpoint := c.baseURL + path
	for attempt := 0; ; attempt++ {
		var reader io.Reader
		if payload != nil {
			reader = bytes.NewReader(payload)
		}
		req, err := http.NewRequestWithContext(ctx, method, endpoint, reader)
		if err != nil {
			return fmt.Errorf("rest: build request: %w", err)
		}
		// Token user dikirim MENTAH tanpa prefix "Bearer"; prefix itu
		// menghasilkan 401 (sudah diuji langsung ke Discord).
		req.Header.Set("Authorization", c.token)
		req.Header.Set("User-Agent", c.userAgent)
		req.Header.Set("Accept", "*/*")
		req.Header.Set("Accept-Language", "en-US,en;q=0.9")
		req.Header.Set("X-Super-Properties", c.superProperties)
		if c.locale != "" {
			req.Header.Set("X-Discord-Locale", c.locale)
		}
		if c.timezone != "" {
			req.Header.Set("X-Discord-Timezone", c.timezone)
		}
		// X-Debug-Options dan X-Fingerprint ikut dikirim klien desktop asli.
		// Keduanya bukan syarat otorisasi; fingerprint hanya diambil sekali
		// lalu di-cache, dan kegagalannya tidak menggagalkan permintaan.
		req.Header.Set("X-Debug-Options", "bugReporterEnabled")
		if c.fingerprint != nil {
			if fp := c.fingerprint.Get(ctx, c.userAgent); fp != "" {
				req.Header.Set("X-Fingerprint", fp)
			}
		}
		if payload != nil {
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("Origin", "https://discord.com")
			req.Header.Set("Referer", "https://discord.com/channels/@me")
			req.Header.Set("Sec-Fetch-Dest", "empty")
			req.Header.Set("Sec-Fetch-Mode", "cors")
			req.Header.Set("Sec-Fetch-Site", "same-origin")
		}

		resp, err := c.http.Do(req)
		if err != nil {
			if ctx.Err() != nil {
				return ctx.Err()
			}
			return fmt.Errorf("rest: %s %s: %w", method, path, err)
		}

		data, readErr := io.ReadAll(io.LimitReader(resp.Body, maxResponseBytes))
		_ = resp.Body.Close()
		if readErr != nil {
			return fmt.Errorf("rest: read response: %w", readErr)
		}

		if resp.StatusCode == http.StatusTooManyRequests {
			apiErr := parseAPIError(resp, data)
			if attempt >= c.maxRetries {
				return apiErr
			}
			if err := sleepCtx(ctx, apiErr.RetryAfter); err != nil {
				return err
			}
			continue
		}

		if resp.StatusCode < 200 || resp.StatusCode >= 300 {
			return parseAPIError(resp, data)
		}

		if out != nil && len(bytes.TrimSpace(data)) > 0 {
			if err := json.Unmarshal(data, out); err != nil {
				return fmt.Errorf("rest: decode response: %w", err)
			}
		}
		return nil
	}
}

func parseAPIError(resp *http.Response, data []byte) *APIError {
	var body struct {
		Message    string  `json:"message"`
		Code       int     `json:"code"`
		RetryAfter float64 `json:"retry_after"`
	}
	_ = json.Unmarshal(data, &body)

	retryAfter := time.Duration(body.RetryAfter * float64(time.Second))
	if retryAfter <= 0 {
		if v := resp.Header.Get("Retry-After"); v != "" {
			if secs, err := strconv.ParseFloat(v, 64); err == nil {
				retryAfter = time.Duration(secs * float64(time.Second))
			}
		}
	}
	if retryAfter < 0 {
		retryAfter = 0
	}

	msg := strings.TrimSpace(body.Message)
	if msg == "" {
		msg = http.StatusText(resp.StatusCode)
	}

	return &APIError{
		Status:     resp.StatusCode,
		Code:       body.Code,
		Message:    msg,
		RetryAfter: retryAfter,
	}
}

func sleepCtx(ctx context.Context, d time.Duration) error {
	if d <= 0 {
		d = 0
	}
	timer := time.NewTimer(d)
	defer timer.Stop()
	select {
	case <-ctx.Done():
		return ctx.Err()
	case <-timer.C:
		return nil
	}
}

func channelPath(channelID string, suffix string) (string, error) {
	id := strings.TrimSpace(channelID)
	if id == "" {
		return "", errors.New("rest: empty channel id")
	}
	return "/channels/" + url.PathEscape(id) + suffix, nil
}
