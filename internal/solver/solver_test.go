package solver

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"sync/atomic"
	"testing"
	"time"
)

func TestNewDefaults(t *testing.T) {
	c := New(Options{})
	if c.APIBase() != DefaultAPIBase || c.OwoBase() != DefaultOwoBase {
		t.Fatalf("default base salah: %q %q", c.APIBase(), c.OwoBase())
	}
	if c.opts.ImageSamples != 3 || c.opts.QueueMax != 1 {
		t.Fatalf("default solver salah: %+v", c.opts)
	}
	if err := c.Check(); err == nil {
		t.Fatal("expected error tanpa api key")
	}
}

func TestSolveHCaptcha(t *testing.T) {
	polls := 0
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("X-API-Key") != "key123" {
			t.Errorf("api key salah: %q", r.Header.Get("X-API-Key"))
		}
		switch {
		case r.Method == http.MethodPost && r.URL.Path == "/api/solve":
			var body map[string]any
			_ = json.NewDecoder(r.Body).Decode(&body)
			if body["task"] != "hcaptcha" || body["url"] != "https://owobot.com/captcha" || body["sitekey"] != "sk" {
				t.Errorf("payload salah: %+v", body)
			}
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"hcaptcha_x","status":"proses"}`)
		case r.Method == http.MethodGet && r.URL.Path == "/api/solve/hcaptcha_x":
			polls++
			if polls < 2 {
				// Captopia membalas 202 + status "proses" selama job jalan.
				w.WriteHeader(http.StatusAccepted)
				_, _ = io.WriteString(w, `{"job_id":"hcaptcha_x","status":"proses"}`)
				return
			}
			_, _ = io.WriteString(w, `{"job_id":"hcaptcha_x","status":"success","token":"tok123"}`)
		default:
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "key123", PollInterval: time.Millisecond})
	token, err := c.SolveHCaptcha(context.Background(), "https://owobot.com/captcha", "sk")
	if err != nil {
		t.Fatalf("SolveHCaptcha: %v", err)
	}
	if token != "tok123" {
		t.Fatalf("token salah: %q", token)
	}
	if polls != 2 {
		t.Fatalf("expected 2 poll, dapat %d", polls)
	}
}

func TestSolveJobFailed(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"hcaptcha_x","status":"proses"}`)
			return
		}
		_, _ = io.WriteString(w, `{"success":false,"job_id":"hcaptcha_x","status":"failed","error":"Captcha could not be solved"}`)
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "k", PollInterval: time.Millisecond})
	_, err := c.SolveHCaptcha(context.Background(), "https://x", "s")
	if err == nil || !strings.Contains(err.Error(), "could not be solved") {
		t.Fatalf("expected error failed, dapat %v", err)
	}
	if !errors.Is(err, ErrJobFailed) {
		t.Fatalf("error harus membungkus ErrJobFailed, dapat %v", err)
	}
}

// TestSolveJobFailedRetriesOnce mengunci perilaku baru: job yang "failed"
// dicoba sekali lagi dengan job baru, dan token dari job kedua dipakai.
// Ini menggantikan loop "percobaan ulang solver x/4" lama di lapisan events.
func TestSolveJobFailedRetriesOnce(t *testing.T) {
	var posts atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			n := posts.Add(1)
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, fmt.Sprintf(`{"success":true,"job_id":"job_%d","status":"proses"}`, n))
			return
		}
		// Job pertama failed, job kedua sukses.
		if strings.HasSuffix(r.URL.Path, "job_1") {
			_, _ = io.WriteString(w, `{"job_id":"job_1","status":"failed","error":"could not be solved"}`)
			return
		}
		_, _ = io.WriteString(w, `{"job_id":"job_2","status":"success","token":"TOK2"}`)
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "k", PollInterval: time.Millisecond})
	token, err := c.SolveHCaptcha(context.Background(), "https://x", "s")
	if err != nil {
		t.Fatalf("SolveHCaptcha: %v", err)
	}
	if token != "TOK2" {
		t.Fatalf("token salah: %q", token)
	}
	if posts.Load() != 2 {
		t.Fatalf("harus 2 job (1 gagal + 1 ulang), dapat %d", posts.Load())
	}
}

// TestSolveJobFailedTwiceStops memastikan retry job dibatasi sekali: gagal dua
// kali -> berhenti di 2 POST, tidak membuat job ketiga.
func TestSolveJobFailedTwiceStops(t *testing.T) {
	var posts atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			posts.Add(1)
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"job","status":"proses"}`)
			return
		}
		_, _ = io.WriteString(w, `{"job_id":"job","status":"failed","error":"could not be solved"}`)
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "k", PollInterval: time.Millisecond})
	_, err := c.SolveHCaptcha(context.Background(), "https://x", "s")
	if !errors.Is(err, ErrJobFailed) {
		t.Fatalf("expected ErrJobFailed, dapat %v", err)
	}
	if posts.Load() != 2 {
		t.Fatalf("harus berhenti di 2 job, dapat %d", posts.Load())
	}
}

// TestSolveTimeoutTidakMembuatJobBaru memastikan timeout (bukan "failed")
// tidak memicu job baru.
func TestSolveTimeoutTidakMembuatJobBaru(t *testing.T) {
	var posts atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			posts.Add(1)
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"job","status":"proses"}`)
			return
		}
		w.WriteHeader(http.StatusAccepted)
		_, _ = io.WriteString(w, `{"job_id":"job","status":"proses"}`)
	}))
	defer srv.Close()

	c := New(Options{
		APIBase: srv.URL, APIKey: "k",
		PollInterval: time.Millisecond, Timeout: 20 * time.Millisecond,
	})
	_, err := c.SolveHCaptcha(context.Background(), "https://x", "s")
	if err == nil {
		t.Fatal("expected timeout")
	}
	if posts.Load() != 1 {
		t.Fatalf("timeout tidak boleh membuat job baru, dapat %d POST", posts.Load())
	}
}

func TestSolveFailures(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if strings.HasPrefix(r.URL.Path, "/fail") {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = io.WriteString(w, `{"success":false,"error":"insufficient balance"}`)
			return
		}
		_, _ = io.WriteString(w, `{"success":false,"error":"solve failed"}`)
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "k"})
	if _, err := c.SolveHCaptcha(context.Background(), "https://x", "s"); err == nil || !strings.Contains(err.Error(), "solve failed") {
		t.Fatalf("expected error solve, dapat %v", err)
	}

	c = New(Options{APIBase: srv.URL + "/fail", APIKey: "k"})
	if _, err := c.SolveHCaptcha(context.Background(), "https://x", "s"); err == nil || !strings.Contains(err.Error(), "insufficient balance") {
		t.Fatalf("expected error HTTP, dapat %v", err)
	}
}

func TestSolveImageToText(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			var body map[string]any
			_ = json.NewDecoder(r.Body).Decode(&body)
			if body["task"] != "imagetotext" || body["image"] != "aW1n" {
				t.Errorf("payload image salah: %+v", body)
			}
			if body["samples"] != float64(3) {
				t.Errorf("samples salah: %v", body["samples"])
			}
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"imagetotext_y","status":"proses"}`)
			return
		}
		_, _ = io.WriteString(w, `{"success":true,"job_id":"imagetotext_y","status":"success","text":"abcde"}`)
	}))
	defer srv.Close()

	c := New(Options{APIBase: srv.URL, APIKey: "k", PollInterval: time.Millisecond})
	text, err := c.SolveImageToText(context.Background(), "aW1n", 0)
	if err != nil {
		t.Fatalf("SolveImageToText: %v", err)
	}
	if text != "abcde" {
		t.Fatalf("text salah: %q", text)
	}
}

func TestQueueFull(t *testing.T) {
	c := New(Options{APIBase: "http://127.0.0.1:1", APIKey: "k", QueueMax: 1})
	c.sem <- struct{}{}
	defer func() { <-c.sem }()

	if _, err := c.SolveHCaptcha(context.Background(), "https://x", "s"); err == nil || !strings.Contains(err.Error(), "queue") {
		t.Fatalf("expected antrean penuh, dapat %v", err)
	}
}

func TestFetchImageBase64(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = io.WriteString(w, "PNGDATA")
	}))
	defer srv.Close()

	c := New(Options{})
	got, err := c.FetchImageBase64(context.Background(), srv.URL)
	if err != nil {
		t.Fatalf("FetchImageBase64: %v", err)
	}
	if got != base64.StdEncoding.EncodeToString([]byte("PNGDATA")) {
		t.Fatalf("base64 salah: %q", got)
	}

	if _, err := c.FetchImageBase64(context.Background(), ""); err == nil {
		t.Fatal("expected error url kosong")
	}
}

func TestVerifyHCaptcha(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/captcha/verify" {
			t.Errorf("path verify salah: %s", r.URL.Path)
		}
		if r.Header.Get("Cookie") != "sess=abc" {
			t.Errorf("cookie salah: %q", r.Header.Get("Cookie"))
		}
		var body map[string]string
		_ = json.NewDecoder(r.Body).Decode(&body)
		if body["token"] != "tok" {
			t.Errorf("token verify salah: %+v", body)
		}
		w.WriteHeader(http.StatusOK)
	}))
	defer srv.Close()

	c := New(Options{OwoBase: srv.URL})
	status, err := c.VerifyHCaptcha(context.Background(), "sess=abc", "tok")
	if err != nil {
		t.Fatalf("VerifyHCaptcha: %v", err)
	}
	if status != 200 {
		t.Fatalf("status salah: %d", status)
	}
}

func TestRateLimitRetry(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodGet {
			_, _ = io.WriteString(w, `{"success":true,"status":"success","token":"tok-after-wait"}`)
			return
		}
		if calls.Add(1) == 1 {
			w.Header().Set("RateLimit-Reset", "0")
			w.WriteHeader(http.StatusTooManyRequests)
			_, _ = io.WriteString(w, `{"success":false,"error":"Too Many Requests"}`)
			return
		}
		w.WriteHeader(http.StatusAccepted)
		_, _ = io.WriteString(w, `{"success":true,"job_id":"hcaptcha_x","status":"proses"}`)
	}))
	defer srv.Close()

	c := New(Options{
		APIBase: srv.URL, APIKey: "k",
		RateLimitRetries: 2, RateLimitWait: time.Millisecond,
		PollInterval: time.Millisecond,
	})
	token, err := c.SolveHCaptcha(context.Background(), "https://owobot.com/captcha", "sk")
	if err != nil {
		t.Fatalf("SolveHCaptcha: %v", err)
	}
	if token != "tok-after-wait" {
		t.Fatalf("token salah: %q", token)
	}
	if calls.Load() != 2 {
		t.Fatalf("expected 2 request, dapat %d", calls.Load())
	}
}

func TestRateLimitExhausted(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		calls.Add(1)
		w.Header().Set("RateLimit-Reset", "0")
		w.WriteHeader(http.StatusTooManyRequests)
		_, _ = io.WriteString(w, `{"success":false,"error":"Too Many Requests"}`)
	}))
	defer srv.Close()

	c := New(Options{
		APIBase: srv.URL, APIKey: "k",
		RateLimitRetries: 1, RateLimitWait: time.Millisecond,
		PollInterval: time.Millisecond,
	})
	_, err := c.SolveHCaptcha(context.Background(), "https://owobot.com/captcha", "sk")
	if !errors.Is(err, ErrRateLimited) {
		t.Fatalf("expected ErrRateLimited, dapat %v", err)
	}
	if calls.Load() != 2 {
		t.Fatalf("expected 2 request, dapat %d", calls.Load())
	}
}

func TestRateLimitWaitHeader(t *testing.T) {
	cases := []struct {
		header string
		value  string
		want   time.Duration
	}{
		{"RateLimit-Reset", "9", 10 * time.Second},
		{"Retry-After", "30", 31 * time.Second},
		{"RateLimit-Reset", "abc", 0},
	}
	for _, c := range cases {
		resp := &http.Response{Header: http.Header{}}
		resp.Header.Set(c.header, c.value)
		if got := rateLimitWait(resp); got != c.want {
			t.Errorf("%s=%s -> %s, want %s", c.header, c.value, got, c.want)
		}
	}
}
