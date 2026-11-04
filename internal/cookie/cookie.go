package cookie

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/http/cookiejar"
	"net/url"
	"regexp"
	"strings"
	"time"
)

const (
	defaultOwoBase = "https://owobot.com"
	defaultTimeout = 15 * time.Second
	maxBodyBytes   = 1 << 20
	userAgent      = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/111.0"
)

// ErrInvalid menandakan alur OAuth OwO tidak menghasilkan cookie sesi.
var ErrInvalid = errors.New("cookie: invalid OAuth flow")

var linkRe = regexp.MustCompile(`(?i)<a\s+href="([^"]+)"`)

// Options mengatur pengambilan cookie.
type Options struct {
	Token      string
	OwoBase    string
	HTTPClient *http.Client
	Timeout    time.Duration
}

// Get menjalankan alur OAuth OwO memakai token Discord lalu mengembalikan
// cookie sesi (format "name=value").
func Get(ctx context.Context, opts Options) (string, error) {
	token := strings.TrimSpace(opts.Token)
	if token == "" {
		return "", errors.New("cookie: empty token")
	}

	base := strings.TrimRight(strings.TrimSpace(opts.OwoBase), "/")
	if base == "" {
		base = defaultOwoBase
	}

	hc := newHTTPClient(opts)

	// 1. Mulai alur OAuth: OwO mengarahkan ke halaman otorisasi Discord.
	authURL := base + "/api/auth/discord"
	resp, err := do(ctx, hc, http.MethodGet, authURL, nil, nil)
	if err != nil {
		return "", err
	}
	_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, maxBodyBytes))
	_ = resp.Body.Close()

	oauthURL, err := resolve(base, resp.Header.Get("Location"))
	if err != nil || oauthURL == "" {
		return "", ErrInvalid
	}

	// 2. Ambil link otorisasi dari halaman Discord.
	resp, err = do(ctx, hc, http.MethodGet, oauthURL, nil, map[string]string{
		"User-Agent":      userAgent,
		"Accept-Language": "en-US,en;q=0.5",
	})
	if err != nil {
		return "", err
	}
	page, readErr := io.ReadAll(io.LimitReader(resp.Body, maxBodyBytes))
	_ = resp.Body.Close()
	if readErr != nil {
		return "", readErr
	}

	match := linkRe.FindSubmatch(page)
	if len(match) < 2 {
		return "", ErrInvalid
	}
	referOAuth := string(match[1])

	// 3. Setujui otorisasi memakai token Discord.
	postBody, _ := json.Marshal(map[string]any{"permissions": "0", "authorize": true})
	resp, err = do(ctx, hc, http.MethodPost, oauthURL, postBody, map[string]string{
		"User-Agent":       userAgent,
		"Accept-Language":  "en-US,en;q=0.5",
		"Content-Type":     "application/json",
		"Authorization":    token,
		"X-Discord-Locale": "en-US",
		"X-Debug-Options":  "bugReporterEnabled",
		"Origin":           "https://discord.com",
		"Referer":          referOAuth,
		"Sec-Fetch-Dest":   "empty",
		"Sec-Fetch-Mode":   "cors",
		"Sec-Fetch-Site":   "same-origin",
	})
	if err != nil {
		return "", err
	}
	postData, readErr := io.ReadAll(io.LimitReader(resp.Body, maxBodyBytes))
	_ = resp.Body.Close()
	if readErr != nil {
		return "", readErr
	}
	if resp.StatusCode != http.StatusOK {
		return "", ErrInvalid
	}

	var result struct {
		Location string `json:"location"`
	}
	if err := json.Unmarshal(postData, &result); err != nil || result.Location == "" {
		return "", ErrInvalid
	}

	// 4. Ikuti redirect terakhir dan ambil Set-Cookie sesi OwO.
	callback, err := resolve(oauthURL, result.Location)
	if err != nil || callback == "" {
		return "", ErrInvalid
	}
	host := ""
	if u, err := url.Parse(callback); err == nil {
		host = u.Host
	}

	resp, err = do(ctx, hc, http.MethodGet, callback, nil, map[string]string{
		"Accept-Language": "en-US,en;q=0.5",
		"Host":            host,
		"Referer":         "https://discord.com/",
		"Sec-Fetch-Dest":  "document",
		"Sec-Fetch-Mode":  "navigate",
		"Sec-Fetch-Site":  "cross-site",
		"Sec-Fetch-User":  "?1",
		"User-Agent":      userAgent,
	})
	if err != nil {
		return "", err
	}
	_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, maxBodyBytes))
	_ = resp.Body.Close()

	if resp.StatusCode != http.StatusFound && resp.StatusCode != http.StatusTemporaryRedirect {
		return "", ErrInvalid
	}

	if cookies := resp.Cookies(); len(cookies) > 0 {
		return cookies[0].Name + "=" + cookies[0].Value, nil
	}
	if raw := resp.Header.Get("Set-Cookie"); raw != "" {
		if pair := strings.SplitN(raw, ";", 2)[0]; pair != "" {
			return pair, nil
		}
	}
	return "", ErrInvalid
}

// CaptchaStatus mengambil cookie sesi lalu memeriksa apakah akun sedang
// diminta menyelesaikan captcha (field captcha.active dari /api/auth).
// Bila cookie tidak diberikan, alur OAuth dijalankan lebih dulu.
func CaptchaStatus(ctx context.Context, opts Options, sessionCookie string) (bool, error) {
	base := strings.TrimRight(strings.TrimSpace(opts.OwoBase), "/")
	if base == "" {
		base = defaultOwoBase
	}

	cookieValue := strings.TrimSpace(sessionCookie)
	if cookieValue == "" {
		c, err := Get(ctx, opts)
		if err != nil {
			return false, err
		}
		cookieValue = c
	}

	hc := newHTTPClient(opts)

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, base+"/api/auth", nil)
	if err != nil {
		return false, err
	}
	req.Header.Set("Cookie", cookieValue)
	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("Accept", "application/json")

	resp, err := hc.Do(req)
	if err != nil {
		return false, err
	}
	defer func() { _ = resp.Body.Close() }()

	data, err := io.ReadAll(io.LimitReader(resp.Body, maxBodyBytes))
	if err != nil {
		return false, err
	}
	if resp.StatusCode != http.StatusOK {
		return false, fmt.Errorf("cookie: /api/auth HTTP %d", resp.StatusCode)
	}

	var out struct {
		Captcha struct {
			Active bool `json:"active"`
		} `json:"captcha"`
	}
	if err := json.Unmarshal(data, &out); err != nil {
		return false, fmt.Errorf("cookie: decode /api/auth: %w", err)
	}
	return out.Captcha.Active, nil
}

func newHTTPClient(opts Options) *http.Client {
	timeout := opts.Timeout
	if timeout <= 0 {
		timeout = defaultTimeout
	}

	var hc http.Client
	if opts.HTTPClient != nil {
		hc = *opts.HTTPClient
	}
	if hc.Jar == nil {
		jar, err := cookiejar.New(nil)
		if err == nil {
			hc.Jar = jar
		}
	}
	if hc.Timeout == 0 {
		hc.Timeout = timeout
	}
	hc.CheckRedirect = func(*http.Request, []*http.Request) error {
		return http.ErrUseLastResponse
	}
	return &hc
}

func do(ctx context.Context, hc *http.Client, method, target string, body []byte, headers map[string]string) (*http.Response, error) {
	var reader io.Reader
	if body != nil {
		reader = bytes.NewReader(body)
	}
	req, err := http.NewRequestWithContext(ctx, method, target, reader)
	if err != nil {
		return nil, err
	}
	for k, v := range headers {
		req.Header.Set(k, v)
	}
	return hc.Do(req)
}

func resolve(base, ref string) (string, error) {
	ref = strings.TrimSpace(ref)
	if ref == "" {
		return "", nil
	}
	refURL, err := url.Parse(ref)
	if err != nil {
		return "", err
	}
	if refURL.IsAbs() {
		return refURL.String(), nil
	}
	baseURL, err := url.Parse(base)
	if err != nil {
		return "", err
	}
	return baseURL.ResolveReference(refURL).String(), nil
}
