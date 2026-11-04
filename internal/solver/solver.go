package solver

import (
	"bytes"
	"context"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
)

const (
	// DefaultAPIBase adalah endpoint captopia.
	DefaultAPIBase = "https://captopia.xyz"
	// DefaultOwoBase adalah base website OwO untuk verify captcha.
	DefaultOwoBase = "https://owobot.com"

	// jobFailedRetries adalah jumlah job tambahan saat job berakhir "failed".
	// 1 = total maksimal 2 job per solve.
	jobFailedRetries = 1

	maxAPIResponse = 1 << 20
	maxImageBytes  = 10 << 20
)

// ErrRateLimited menandakan captopia menolak karena terlalu banyak request.
var ErrRateLimited = errors.New("solver: rate limited by captopia")

// ErrJobFailed menandakan job captopia berakhir dengan status "failed"
// (mis. challenge tidak bisa dipecahkan). Berbeda dari timeout/rate limit:
// hanya error ini yang layak membuat job baru.
var ErrJobFailed = errors.New("solver: job failed")

// Options mengatur klien solver.
type Options struct {
	APIBase      string
	APIKey       string
	SiteKey      string
	URL          string
	Host         string
	Timeout      time.Duration
	ImageTimeout time.Duration
	ImageSamples int
	QueueMax     int
	Debug        bool
	OwoBase      string
	HTTPClient   *http.Client

	// RateLimitRetries adalah jumlah percobaan ulang saat captopia membalas
	// 429. 0 = 2.
	RateLimitRetries int
	// RateLimitWait adalah jeda default bila header RateLimit-Reset tidak
	// ada. 0 = 60 detik.
	RateLimitWait time.Duration
	// PollInterval adalah jeda antar polling hasil job. 0 = 3 detik.
	PollInterval time.Duration
}

// Client adalah klien captopia + verifikasi OwO.
type Client struct {
	opts Options
	http *http.Client
	sem  chan struct{}
}

// New membuat Client dengan default yang masuk akal.
func New(opts Options) *Client {
	if opts.APIBase == "" {
		opts.APIBase = DefaultAPIBase
	}
	opts.APIBase = strings.TrimRight(opts.APIBase, "/")
	if opts.OwoBase == "" {
		opts.OwoBase = DefaultOwoBase
	}
	opts.OwoBase = strings.TrimRight(opts.OwoBase, "/")
	if opts.Timeout <= 0 {
		opts.Timeout = 480 * time.Second
	}
	if opts.ImageTimeout <= 0 {
		opts.ImageTimeout = opts.Timeout
	}
	if opts.ImageSamples <= 0 {
		opts.ImageSamples = 3
	}
	if opts.QueueMax <= 0 {
		opts.QueueMax = 1
	}
	if opts.HTTPClient == nil {
		opts.HTTPClient = &http.Client{}
	}
	if opts.RateLimitRetries <= 0 {
		opts.RateLimitRetries = 2
	}
	if opts.RateLimitWait <= 0 {
		opts.RateLimitWait = 60 * time.Second
	}
	if opts.PollInterval <= 0 {
		opts.PollInterval = 3 * time.Second
	}

	return &Client{
		opts: opts,
		http: opts.HTTPClient,
		sem:  make(chan struct{}, opts.QueueMax),
	}
}

// APIBase mengembalikan base captopia.
func (c *Client) APIBase() string { return c.opts.APIBase }

// OwoBase mengembalikan base website OwO.
func (c *Client) OwoBase() string { return c.opts.OwoBase }

// Check memastikan API key tersedia.
func (c *Client) Check() error {
	if strings.TrimSpace(c.opts.APIKey) == "" {
		return errors.New("solver: CAPTOPIA_API_KEY is not set in .env")
	}
	return nil
}

func (c *Client) acquire() (func(), error) {
	select {
	case c.sem <- struct{}{}:
		return func() { <-c.sem }, nil
	default:
		return nil, errors.New("solver: queue full")
	}
}

type solveResponse struct {
	Success bool   `json:"success"`
	JobID   string `json:"job_id"`
	Status  string `json:"status"`
	Token   string `json:"token"`
	Text    string `json:"text"`
	Elapsed int64  `json:"elapsed"`
	Error   string `json:"error"`
}

func (c *Client) postSolve(ctx context.Context, payload map[string]any, timeout time.Duration) (solveResponse, error) {
	for attempt := 0; ; attempt++ {
		out, wait, err := c.postSolveOnce(ctx, payload, timeout)
		if !errors.Is(err, ErrRateLimited) {
			return out, err
		}
		if attempt >= c.opts.RateLimitRetries {
			return out, err
		}
		if wait <= 0 {
			wait = c.opts.RateLimitWait
		}
		logger.Warn("SOLVER", fmt.Sprintf("rate limited, tunggu %s lalu coba lagi (%d/%d)",
			wait.Round(time.Second), attempt+1, c.opts.RateLimitRetries))
		if err := sleepCtx(ctx, wait); err != nil {
			return out, err
		}
	}
}

func (c *Client) postSolveOnce(ctx context.Context, payload map[string]any, timeout time.Duration) (solveResponse, time.Duration, error) {
	var out solveResponse

	body, err := json.Marshal(payload)
	if err != nil {
		return out, 0, fmt.Errorf("solver: encode payload: %w", err)
	}

	cctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	req, err := http.NewRequestWithContext(cctx, http.MethodPost, c.opts.APIBase+"/api/solve", bytes.NewReader(body))
	if err != nil {
		return out, 0, fmt.Errorf("solver: build request: %w", err)
	}
	req.Header.Set("X-API-Key", c.opts.APIKey)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.http.Do(req)
	if err != nil {
		if cctx.Err() != nil {
			return out, 0, errors.New("solver: request timeout")
		}
		return out, 0, fmt.Errorf("solver: request: %w", err)
	}
	defer func() { _ = resp.Body.Close() }()

	data, readErr := io.ReadAll(io.LimitReader(resp.Body, maxAPIResponse))
	if readErr != nil {
		return out, 0, fmt.Errorf("solver: read response: %w", readErr)
	}

	_ = json.Unmarshal(data, &out)

	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusAccepted {
		msg := strings.TrimSpace(out.Error)
		if msg == "" {
			msg = fmt.Sprintf("HTTP %d", resp.StatusCode)
		}
		if resp.StatusCode == http.StatusTooManyRequests {
			return out, rateLimitWait(resp), ErrRateLimited
		}
		return out, 0, fmt.Errorf("solver: %s", msg)
	}
	if !out.Success {
		msg := strings.TrimSpace(out.Error)
		if msg == "" {
			msg = "tanpa keterangan"
		}
		return out, 0, fmt.Errorf("solver: %s", msg)
	}
	if strings.TrimSpace(out.JobID) == "" {
		return out, 0, errors.New("solver: response without job_id")
	}

	return out, 0, nil
}

// getSolveOnce mengambil status/hasil job sekali.
func (c *Client) getSolveOnce(ctx context.Context, jobID string) (solveResponse, time.Duration, error) {
	var out solveResponse

	cctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(cctx, http.MethodGet, c.opts.APIBase+"/api/solve/"+jobID, nil)
	if err != nil {
		return out, 0, fmt.Errorf("solver: build result request: %w", err)
	}
	req.Header.Set("X-API-Key", c.opts.APIKey)

	resp, err := c.http.Do(req)
	if err != nil {
		if cctx.Err() != nil {
			return out, 0, errors.New("solver: result request timeout")
		}
		return out, 0, fmt.Errorf("solver: result request: %w", err)
	}
	defer func() { _ = resp.Body.Close() }()

	data, readErr := io.ReadAll(io.LimitReader(resp.Body, maxAPIResponse))
	if readErr != nil {
		return out, 0, fmt.Errorf("solver: read result response: %w", readErr)
	}

	_ = json.Unmarshal(data, &out)

	if resp.StatusCode == http.StatusTooManyRequests {
		return out, rateLimitWait(resp), ErrRateLimited
	}
	// 202 = job masih "proses", bukan error; pollSolve yang akan mengulang.
	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusAccepted {
		msg := strings.TrimSpace(out.Error)
		if msg == "" {
			msg = fmt.Sprintf("HTTP %d", resp.StatusCode)
		}
		return out, 0, fmt.Errorf("solver: %s", msg)
	}

	return out, 0, nil
}

// pollSolve menunggu job selesai, mengambil token/text dari hasil akhir.
func (c *Client) pollSolve(ctx context.Context, jobID string, timeout time.Duration) (solveResponse, error) {
	var out solveResponse

	deadline := time.Now().Add(timeout)
	for {
		res, wait, err := c.getSolveOnce(ctx, jobID)
		if errors.Is(err, ErrRateLimited) {
			if wait <= 0 {
				wait = c.opts.RateLimitWait
			}
			logger.Warn("SOLVER", fmt.Sprintf("rate limited saat polling, tunggu %s", wait.Round(time.Second)))
			if sleepCtx(ctx, wait) != nil {
				return out, err
			}
			continue
		}
		if err != nil {
			return out, err
		}
		out = res

		switch strings.TrimSpace(res.Status) {
		case "success":
			return out, nil
		case "failed":
			msg := strings.TrimSpace(res.Error)
			if msg == "" {
				msg = "tanpa keterangan"
			}
			return out, fmt.Errorf("%w: %s", ErrJobFailed, msg)
		}

		if time.Now().Add(c.opts.PollInterval).After(deadline) {
			return out, errors.New("solver: timeout waiting for result")
		}
		if sleepCtx(ctx, c.opts.PollInterval) != nil {
			return out, ctx.Err()
		}
	}
}

// rateLimitWait membaca RateLimit-Reset (detik) atau Retry-After.
func rateLimitWait(resp *http.Response) time.Duration {
	for _, key := range []string{"RateLimit-Reset", "Retry-After"} {
		if v := strings.TrimSpace(resp.Header.Get(key)); v != "" {
			if secs, err := strconv.Atoi(v); err == nil && secs > 0 {
				return time.Duration(secs)*time.Second + time.Second
			}
		}
	}
	return 0
}

// SolveHCaptcha meminta token hCaptcha ke captopia.
func (c *Client) SolveHCaptcha(ctx context.Context, targetURL, sitekey string) (string, error) {
	if err := c.Check(); err != nil {
		return "", err
	}
	if targetURL == "" {
		targetURL = c.opts.URL
	}
	if targetURL == "" && c.opts.Host != "" {
		targetURL = "https://" + c.opts.Host
	}
	if targetURL == "" {
		return "", errors.New("solver: captcha target url is empty")
	}
	if sitekey == "" {
		sitekey = c.opts.SiteKey
	}

	release, err := c.acquire()
	if err != nil {
		return "", err
	}
	defer release()

	if c.opts.Debug {
		logger.Log("SOLVER", "hcaptcha url="+targetURL)
	}

	var resp solveResponse
	for job := 0; ; job++ {
		created, err := c.postSolve(ctx, map[string]any{
			"task":    "hcaptcha",
			"url":     targetURL,
			"sitekey": sitekey,
		}, c.opts.Timeout)
		if err != nil {
			return "", err
		}

		resp, err = c.pollSolve(ctx, created.JobID, c.opts.Timeout)
		if err == nil {
			break
		}
		// Hanya job "failed" yang layak dicoba ulang dengan job baru. Timeout,
		// rate limit, atau error lain tidak menambah job di captopia.
		if !errors.Is(err, ErrJobFailed) || job >= jobFailedRetries {
			return "", err
		}
		if c.opts.Debug {
			logger.Warn("SOLVER", "job failed, creating a new job")
		}
		if sleepCtx(ctx, c.opts.PollInterval) != nil {
			return "", err
		}
	}

	if resp.Token == "" {
		return "", errors.New("solver: empty hCaptcha token")
	}

	logger.Log("SOLVED", "https://owobot.com/captcha terpecahkan")
	return resp.Token, nil
}

// SolveImageToText membaca captcha gambar (base64) lewat captopia.
func (c *Client) SolveImageToText(ctx context.Context, image string, samples int) (string, error) {
	if err := c.Check(); err != nil {
		return "", err
	}
	if image == "" {
		return "", errors.New("solver: empty image")
	}
	if samples <= 0 {
		samples = c.opts.ImageSamples
	}

	release, err := c.acquire()
	if err != nil {
		return "", err
	}
	defer release()

	if c.opts.Debug {
		logger.Log("SOLVER", "imagetotext")
	}

	created, err := c.postSolve(ctx, map[string]any{
		"task":    "imagetotext",
		"image":   image,
		"samples": samples,
	}, c.opts.ImageTimeout)
	if err != nil {
		return "", err
	}

	resp, err := c.pollSolve(ctx, created.JobID, c.opts.ImageTimeout)
	if err != nil {
		return "", err
	}
	if resp.Text == "" {
		return "", errors.New("solver: empty read result")
	}

	logger.Log("SOLVER", "image captcha terbaca: "+resp.Text)
	return resp.Text, nil
}

// FetchImageBase64 mengunduh URL gambar lalu mengubahnya menjadi base64.
func (c *Client) FetchImageBase64(ctx context.Context, url string) (string, error) {
	if url == "" {
		return "", errors.New("solver: empty image url")
	}

	cctx, cancel := context.WithTimeout(ctx, 20*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(cctx, http.MethodGet, url, nil)
	if err != nil {
		return "", fmt.Errorf("solver: build image request: %w", err)
	}

	resp, err := c.http.Do(req)
	if err != nil {
		return "", fmt.Errorf("solver: download image: %w", err)
	}
	defer func() { _ = resp.Body.Close() }()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return "", fmt.Errorf("solver: download image HTTP %d", resp.StatusCode)
	}

	data, err := io.ReadAll(io.LimitReader(resp.Body, maxImageBytes))
	if err != nil {
		return "", fmt.Errorf("solver: read image: %w", err)
	}
	if len(data) == 0 {
		return "", errors.New("solver: empty image")
	}
	return base64.StdEncoding.EncodeToString(data), nil
}

// VerifyHCaptcha mengirim token ke OwO dengan cookie sesi hasil OAuth.
func (c *Client) VerifyHCaptcha(ctx context.Context, cookie, token string) (int, error) {
	if cookie == "" || token == "" {
		return 0, errors.New("solver: empty verify cookie/token")
	}

	cctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	body, err := json.Marshal(map[string]string{"token": token})
	if err != nil {
		return 0, fmt.Errorf("solver: encode verify: %w", err)
	}

	req, err := http.NewRequestWithContext(cctx, http.MethodPost, c.opts.OwoBase+"/api/captcha/verify", bytes.NewReader(body))
	if err != nil {
		return 0, fmt.Errorf("solver: build verify request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Cookie", cookie)

	resp, err := c.http.Do(req)
	if err != nil {
		return 0, fmt.Errorf("solver: verify: %w", err)
	}
	defer func() { _ = resp.Body.Close() }()
	_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, maxAPIResponse))

	return resp.StatusCode, nil
}

func sleepCtx(ctx context.Context, d time.Duration) error {
	if d <= 0 {
		return nil
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
