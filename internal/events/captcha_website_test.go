package events

import (
	"context"
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"sync/atomic"
	"testing"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/solver"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/state"
)

// TestCaptchaWebsiteEndToEnd mensimulasikan alur captcha hCaptcha lengkap
// seperti di produksi, bukan sekadar "success" yang di-stub:
//
//  1. OwO mengirim pesan captcha ber-button di kanal.
//  2. captopia POST /api/solve membalas HTTP 202 + status "proses" (job dibuat).
//  3. captopia GET /api/solve/{job_id} pertama masih 202 "proses", kedua
//     "success" + token (inilah langkah polling yang hilang di binary lama).
//  4. cookie OAuth OwO diambil lewat /api/auth/discord → OAuth → callback.
//  5. token dikirim ke /api/captcha/verify dengan cookie sesi → 200.
//  6. captchaStatus direset dan loop farming dilanjutkan.
//
// Regresi yang dikunci: kode lama menganggap HTTP 202 sebagai error sehingga
// membuat job baru tiap percobaan (token tidak pernah diambil, saldo terbuang).
func TestCaptchaWebsiteEndToEnd(t *testing.T) {
	var (
		solvePosts   atomic.Int32
		solvePolls   atomic.Int32
		verifyHits   atomic.Int32
		verifyToken  atomic.Value
		verifyCookie atomic.Value
	)

	// --- captopia (solver APIBase) ---
	captopia := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		switch {
		case r.Method == http.MethodPost && r.URL.Path == "/api/solve":
			solvePosts.Add(1)
			var body map[string]any
			_ = json.NewDecoder(r.Body).Decode(&body)
			if body["task"] != "hcaptcha" {
				t.Errorf("task salah: %v", body["task"])
			}
			if body["sitekey"] != "site-key-test" {
				t.Errorf("sitekey salah: %v", body["sitekey"])
			}
			// Job dibuat asinkron: 202 + masih "proses".
			w.WriteHeader(http.StatusAccepted)
			_, _ = io.WriteString(w, `{"success":true,"job_id":"hcaptcha_1","status":"proses"}`)
		case r.Method == http.MethodGet && r.URL.Path == "/api/solve/hcaptcha_1":
			n := solvePolls.Add(1)
			if n < 2 {
				w.WriteHeader(http.StatusAccepted)
				_, _ = io.WriteString(w, `{"job_id":"hcaptcha_1","status":"proses"}`)
				return
			}
			_, _ = io.WriteString(w, `{"job_id":"hcaptcha_1","status":"success","token":"HCAPTOKEN"}`)
		default:
			t.Errorf("captopia path tak terduga: %s %s", r.Method, r.URL.Path)
		}
	}))
	defer captopia.Close()

	// --- OwO (solver OwoBase): OAuth cookie + verify ---
	owo := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch {
		case r.Method == http.MethodGet && r.URL.Path == "/api/auth/discord":
			// Mulai OAuth: arahkan ke halaman otorisasi.
			w.Header().Set("Location", "/discord-oauth")
			w.WriteHeader(http.StatusFound)

		case r.Method == http.MethodGet && r.URL.Path == "/discord-oauth":
			// Halaman otorisasi Discord berisi link yang harus di-POST.
			_, _ = io.WriteString(w, `<html><a href="/discord-oauth">Authorize</a></html>`)

		case r.Method == http.MethodPost && r.URL.Path == "/discord-oauth":
			// Setujui otorisasi; Discord membalas lokasi callback.
			w.Header().Set("Content-Type", "application/json")
			_, _ = io.WriteString(w, `{"location":"/callback"}`)

		case r.Method == http.MethodGet && r.URL.Path == "/callback":
			// Callback menetapkan cookie sesi OwO lewat redirect.
			http.SetCookie(w, &http.Cookie{Name: "owosession", Value: "OWOCOOKIE"})
			w.WriteHeader(http.StatusFound)

		case r.Method == http.MethodPost && r.URL.Path == "/api/captcha/verify":
			verifyHits.Add(1)
			verifyCookie.Store(r.Header.Get("Cookie"))
			var body map[string]string
			_ = json.NewDecoder(r.Body).Decode(&body)
			verifyToken.Store(body["token"])
			w.WriteHeader(http.StatusOK)

		default:
			t.Errorf("OwO path tak terduga: %s %s", r.Method, r.URL.Path)
		}
	}))
	defer owo.Close()

	sc := solver.New(solver.Options{
		APIBase:      captopia.URL,
		OwoBase:      owo.URL,
		APIKey:       "k",
		SiteKey:      "site-key-test",
		URL:          "https://owobot.com/captcha",
		PollInterval: time.Millisecond,
	})

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()

	d := New(Options{
		Config: cfg, State: st, Solver: sc, Controller: ctrl,
		Token: "TESTTOKEN.abcd.efgh",
		Timings: Timings{
			CaptchaResumeDelay: time.Millisecond,
			VerifyRetryDelay:   time.Millisecond,
		},
	})

	msg := rest.Message{
		ID: "cap1", ChannelID: "123",
		Author:  rest.User{ID: owoBotID},
		Content: "<@42> please complete this captcha, are you a real human?",
		Components: []rest.ComponentRow{{
			Components: []rest.Component{{Type: 2, CustomID: "captcha"}},
		}},
	}

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	if err := d.handleMessageCreate(ctx, messageEvent(t, msg)); err != nil {
		t.Fatalf("handle captcha website: %v", err)
	}

	// Tunggu sampai verify terkirim dan loop lanjut kembali.
	deadline := time.Now().Add(20 * time.Second)
	for time.Now().Before(deadline) {
		if verifyHits.Load() > 0 {
			break
		}
		time.Sleep(5 * time.Millisecond)
	}

	if solvePosts.Load() != 1 {
		t.Fatalf("harus tepat 1 job captopia (regresi: kode lama spam 202), dapat %d", solvePosts.Load())
	}
	if solvePolls.Load() < 2 {
		t.Fatalf("harus polling sampai job selesai, dapat %d poll", solvePolls.Load())
	}
	if verifyHits.Load() != 1 {
		t.Fatalf("verify OwO harus dipanggil sekali, dapat %d", verifyHits.Load())
	}
	if tok, _ := verifyToken.Load().(string); tok != "HCAPTOKEN" {
		t.Fatalf("token ke verify salah: %q", tok)
	}
	if ck, _ := verifyCookie.Load().(string); ck != "owosession=OWOCOOKIE" {
		t.Fatalf("cookie sesi ke verify salah: %q", ck)
	}

	// captchaStatus harus direset dan farming dilanjutkan.
	deadline = time.Now().Add(3 * time.Second)
	for time.Now().Before(deadline) {
		if !cfg.CaptchaActive() {
			break
		}
		time.Sleep(5 * time.Millisecond)
	}
	if cfg.CaptchaActive() {
		t.Fatal("captchaStatus tidak direset setelah verify sukses")
	}
	waitStarts(t, ctrl, 1)
}
