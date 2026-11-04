package events

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"sync"
	"sync/atomic"
	"testing"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/gateway"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/solver"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/state"
)

type fakeController struct {
	mu       sync.Mutex
	starts   int
	stops    int
	restarts chan time.Duration
}

func newFakeController() *fakeController {
	return &fakeController{restarts: make(chan time.Duration, 4)}
}

func (c *fakeController) Start() {
	c.mu.Lock()
	c.starts++
	c.mu.Unlock()
}

func (c *fakeController) Stop() {
	c.mu.Lock()
	c.stops++
	c.mu.Unlock()
}

func (c *fakeController) ScheduleRestart(d time.Duration) {
	select {
	case c.restarts <- d:
	default:
	}
}

func (c *fakeController) counts() (int, int) {
	c.mu.Lock()
	defer c.mu.Unlock()
	return c.starts, c.stops
}

func messageEvent(t *testing.T, m rest.Message) gateway.Event {
	t.Helper()
	raw, err := json.Marshal(m)
	if err != nil {
		t.Fatalf("marshal pesan: %v", err)
	}
	return gateway.Event{Type: "MESSAGE_CREATE", Data: raw}
}

func mustSet(t *testing.T, cfg interface{ Set(string, any) error }, key string, value any) {
	t.Helper()
	if err := cfg.Set(key, value); err != nil {
		t.Fatalf("Set(%s): %v", key, err)
	}
}

func waitStarts(t *testing.T, ctrl *fakeController, want int) {
	t.Helper()
	deadline := time.Now().Add(15 * time.Second)
	for time.Now().Before(deadline) {
		starts, _ := ctrl.counts()
		if starts >= want {
			return
		}
		time.Sleep(5 * time.Millisecond)
	}
	starts, _ := ctrl.counts()
	t.Fatalf("expected minimal %d start, dapat %d", want, starts)
}

func TestOwoCollectorTimeout(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, Controller: ctrl,
		Timings: Timings{
			CollectorWindow: 40 * time.Millisecond,
			OwoOfflineDelay: 5 * time.Millisecond,
		},
	})

	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m1", ChannelID: "123", Content: "owoh", Author: rest.User{ID: "42"},
	})); err != nil {
		t.Fatalf("handle owoh: %v", err)
	}

	select {
	case d := <-ctrl.restarts:
		if d != 5*time.Millisecond {
			t.Fatalf("delay restart salah: %s", d)
		}
	case <-time.After(time.Second):
		t.Fatal("tidak ada scheduleRestart saat OwO offline")
	}
	if _, stops := ctrl.counts(); stops != 1 {
		t.Fatalf("expected 1 stop, dapat %d", stops)
	}
}

func TestOwoCollectorFound(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, Controller: ctrl,
		Timings: Timings{CollectorWindow: 80 * time.Millisecond},
	})

	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m1", ChannelID: "123", Content: "owoh", Author: rest.User{ID: "42"},
	})); err != nil {
		t.Fatalf("handle owoh: %v", err)
	}
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m2", ChannelID: "123", Content: "owo", Author: rest.User{ID: owoBotID},
	})); err != nil {
		t.Fatalf("handle owo: %v", err)
	}

	time.Sleep(150 * time.Millisecond)
	select {
	case d := <-ctrl.restarts:
		t.Fatalf("tidak boleh restart saat OwO merespons: %s", d)
	default:
	}
}

// TestOwoCollectorIgnoresOtherAuthors mengunci regresi: pesan dari bot/user
// selain OwO (mis. bot reaksi yang memposting pengumuman) TIDAK boleh
// membatalkan collector offline. Versi lama memanggil markFound() untuk semua
// pesan non-self, sehingga offline tidak pernah terdeteksi dan farming jalan
// terus walau OwO mati.
func TestOwoCollectorIgnoresOtherAuthors(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, Controller: ctrl,
		Timings: Timings{
			CollectorWindow: 40 * time.Millisecond,
			OwoOfflineDelay: 5 * time.Millisecond,
		},
	})

	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m1", ChannelID: "123", Content: "owoh", Author: rest.User{ID: "42"},
	})); err != nil {
		t.Fatalf("handle owoh: %v", err)
	}
	// Bot reaksi lain memposting di kanal yang sama: bukan OwO.
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m2", ChannelID: "123", Content: ":owoannouncement: it's time to say OWO",
		Author: rest.User{ID: "777777777777777777"},
	})); err != nil {
		t.Fatalf("handle pesan bot lain: %v", err)
	}

	select {
	case d := <-ctrl.restarts:
		if d != 5*time.Millisecond {
			t.Fatalf("delay restart salah: %s", d)
		}
	case <-time.After(time.Second):
		t.Fatal("OwO offline harus terdeteksi walau ada pesan bot lain")
	}
	if _, stops := ctrl.counts(); stops != 1 {
		t.Fatalf("expected 1 stop, dapat %d", stops)
	}
}

// TestOwoCollectorIgnoresOtherChannel memastikan pesan OwO di kanal lain tidak
// membatalkan collector di kanal farming (collector Node bersifat per-channel).
func TestOwoCollectorIgnoresOtherChannel(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, Controller: ctrl,
		Timings: Timings{
			CollectorWindow: 40 * time.Millisecond,
			OwoOfflineDelay: 5 * time.Millisecond,
		},
	})

	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m1", ChannelID: "123", Content: "owoh", Author: rest.User{ID: "42"},
	})); err != nil {
		t.Fatalf("handle owoh: %v", err)
	}
	// OwO membalas, tetapi di kanal lain.
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, rest.Message{
		ID: "m2", ChannelID: "999", Content: "owo", Author: rest.User{ID: owoBotID},
	})); err != nil {
		t.Fatalf("handle OwO kanal lain: %v", err)
	}

	select {
	case d := <-ctrl.restarts:
		if d != 5*time.Millisecond {
			t.Fatalf("delay restart salah: %s", d)
		}
	case <-time.After(time.Second):
		t.Fatal("pesan OwO di kanal lain tidak boleh dianggap merespons")
	}
}

func TestOwoDuelClick(t *testing.T) {
	requests := make(chan map[string]any, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/interactions" {
			t.Errorf("path interaksi salah: %s", r.URL.Path)
		}
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		requests <- body
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	st.SetSessionID("sess")
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, REST: rc, Controller: ctrl,
		Timings: Timings{DuelClickDelay: 5 * time.Millisecond},
	})

	msg := rest.Message{
		ID: "999", ChannelID: "123", GuildID: "7",
		Content: "<@42>",
		Author:  rest.User{ID: owoBotID},
		Embeds: []rest.Embed{{
			Author: &rest.EmbedAuthor{Name: "Someone challenges you to a duel!"},
		}},
		Components: []rest.ComponentRow{{
			Type: 1,
			Components: []rest.Component{{
				Type: 2, CustomID: "battle_accept",
			}},
		}},
	}
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, msg)); err != nil {
		t.Fatalf("handle duel: %v", err)
	}

	select {
	case body := <-requests:
		data, _ := body["data"].(map[string]any)
		if data["custom_id"] != "battle_accept" {
			t.Fatalf("custom_id salah: %+v", data)
		}
		if body["session_id"] != "sess" {
			t.Fatalf("session_id salah: %v", body["session_id"])
		}
		// Application id HARUS milik OwO. Regresi: kode lama memakai
		// m.Author.ID sebagai cadangan, yang bukan OwO bila pesan duel datang
		// dari webhook, sehingga Discord menolak interaksinya.
		if body["application_id"] != rest.OwoApplicationID {
			t.Fatalf("application_id harus %s, dapat %v",
				rest.OwoApplicationID, body["application_id"])
		}
	case <-time.After(15 * time.Second):
		t.Fatal("tombol duel tidak diklik")
	}
}

func TestAutogemsInventory(t *testing.T) {
	sent := make(chan string, 2)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/channels/123/messages" {
			t.Errorf("path kirim salah: %s", r.URL.Path)
		}
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		if content, ok := body["content"].(string); ok {
			sent <- content
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = io.WriteString(w, `{"id":"out","channel_id":"123"}`)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "status", true)
	mustSet(t, cfg, "statusgems", true)

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, REST: rc, Controller: ctrl,
		Timings: Timings{
			GemSelectMin: time.Millisecond,
			GemSelectMax: time.Millisecond,
			GemCooldown:  20 * time.Millisecond,
		},
	})

	msg := rest.Message{
		ID: "m1", ChannelID: "123", GuildID: "7",
		Author: rest.User{ID: owoBotID},
		// 051/065/072 tersedia, tetapi juga 052/066: logika baru memilih
		// tingkat tertinggi per bentuk, jadi hasilnya 52 66 72.
		Content: "**tester** inventory\n`051` `065` `072` `052` `066`",
	}
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, msg)); err != nil {
		t.Fatalf("handle inventory: %v", err)
	}

	select {
	case content := <-sent:
		if content != "owouse 52 66 72" {
			t.Fatalf("command gem salah: %q", content)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("command gem tidak dikirim")
	}

	if _, stops := ctrl.counts(); stops != 1 {
		t.Fatalf("expected 1 stop, dapat %d", stops)
	}
	waitStarts(t, ctrl, 1)
	if !cfg.StatusGems() {
		t.Fatal("statusgems tidak boleh dimatikan")
	}
}

// TestAutogemsAkumulasiSlotMati memastikan dua bentuk yang mati beruntun
// (sebelum inventory sempat dibalas) sama-sama tercatat, sehingga use berikutnya
// mengisi keduanya. Regresi: versi lama menimpa pendingSlots dengan slot mati
// terakhir sehingga salah satu bentuk tak pernah di-use.
func TestAutogemsAkumulasiSlotMati(t *testing.T) {
	sent := make(chan string, 4)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		if c, ok := body["content"].(string); ok {
			sent <- c
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = io.WriteString(w, `{"id":"out","channel_id":"123"}`)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "status", true)
	mustSet(t, cfg, "statusgems", true)

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	d := New(Options{
		Config: cfg, State: st, REST: rc,
		Timings: Timings{
			GemSpentMin: time.Millisecond, GemSpentMax: time.Millisecond,
		},
	})
	ctx := context.Background()
	h := d.gems

	// Patokan: ketiganya aktif.
	h.handleDeadSlots(ctx, "123", map[string]bool{"1": true, "3": true, "4": true})

	// Diamond mati -> minta inventory.
	h.handleDeadSlots(ctx, "123", map[string]bool{"3": true, "4": true})
	// Bulat ikut mati sebelum inventory dibalas -> harus ikut tercatat.
	h.handleDeadSlots(ctx, "123", map[string]bool{"4": true})

	h.slotMu.Lock()
	pending := strings.Join(h.pendingSlots, ",")
	h.slotMu.Unlock()
	if pending != "1,3" {
		t.Fatalf("pendingSlots harus mengakumulasi 1,3 dapat %q", pending)
	}
}

func TestCaptchaDetectionStopsLoop(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{Config: cfg, State: st, Controller: ctrl})

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	msg := rest.Message{
		ID: "c1", ChannelID: "123",
		Author:  rest.User{ID: owoBotID},
		Content: "<@42> please complete this captcha, are you a real human?",
		Components: []rest.ComponentRow{{
			Components: []rest.Component{{Type: 2, CustomID: "captcha"}},
		}},
	}
	if err := d.handleMessageCreate(ctx, messageEvent(t, msg)); err != nil {
		t.Fatalf("handle captcha: %v", err)
	}

	if !cfg.CaptchaActive() {
		t.Fatal("captchaStatus tidak diset")
	}
	if _, stops := ctrl.counts(); stops != 1 {
		t.Fatalf("expected 1 stop, dapat %d", stops)
	}
}

func TestCaptchaThankDMResumes(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "captchaStatus", true)

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()
	d := New(Options{
		Config: cfg, State: st, Controller: ctrl,
		Timings: Timings{
			ThankDelayMin: time.Millisecond,
			ThankDelayMax: time.Millisecond,
		},
	})

	msg := rest.Message{
		ID: "d1", ChannelID: "dm1", GuildID: "",
		Author:  rest.User{ID: owoBotID},
		Content: "Thank you for verifying!",
	}
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, msg)); err != nil {
		t.Fatalf("handle thank: %v", err)
	}

	if cfg.CaptchaActive() {
		t.Fatal("captchaStatus harus false setelah Thank")
	}
	waitStarts(t, ctrl, 1)
}

func TestCaptchaImageSolvedAndSentAsDM(t *testing.T) {
	var imageHit, solveHit, dmHit atomic.Int32

	imageSrv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		imageHit.Add(1)
		_, _ = io.WriteString(w, "PNGDATA")
	}))
	defer imageSrv.Close()

	solveSrv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodGet {
			_, _ = io.WriteString(w, `{"success":true,"status":"success","text":"XY12Z"}`)
			return
		}
		solveHit.Add(1)
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		if body["task"] != "imagetotext" {
			t.Errorf("task salah: %v", body["task"])
		}
		want := base64.StdEncoding.EncodeToString([]byte("PNGDATA"))
		if body["image"] != want {
			t.Errorf("image bukan base64 attachment: %v", body["image"])
		}
		w.WriteHeader(http.StatusAccepted)
		_, _ = io.WriteString(w, `{"success":true,"job_id":"imagetotext_y","status":"proses"}`)
	}))
	defer solveSrv.Close()

	discordSrv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch {
		case r.URL.Path == "/users/@me/channels":
			w.Header().Set("Content-Type", "application/json")
			_, _ = io.WriteString(w, `{"id":"dm-owo","type":1}`)
		case r.URL.Path == "/channels/dm-owo/messages":
			dmHit.Add(1)
			var body map[string]any
			_ = json.NewDecoder(r.Body).Decode(&body)
			if body["content"] != "XY12Z" {
				t.Errorf("jawaban DM salah: %v", body["content"])
			}
			w.Header().Set("Content-Type", "application/json")
			_, _ = io.WriteString(w, `{"id":"m1","channel_id":"dm-owo"}`)
		default:
			t.Errorf("path tak terduga: %s", r.URL.Path)
		}
	}))
	defer discordSrv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: discordSrv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}
	sc := solver.New(solver.Options{APIBase: solveSrv.URL, APIKey: "k"})

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	ctrl := newFakeController()

	d := New(Options{
		Config: cfg, State: st, REST: rc, Solver: sc, Controller: ctrl,
		Timings: Timings{
			CaptchaImageConfirm: 60 * time.Millisecond,
			CaptchaConfirmPoll:  10 * time.Millisecond,
			CaptchaResumeDelay:  time.Millisecond,
			CaptchaFallbackWait: time.Millisecond,
		},
	})

	msg := rest.Message{
		ID: "cap1", ChannelID: "123",
		Author:  rest.User{ID: owoBotID},
		Content: "<@42> please complete this captcha, are you a real human?",
		Attachments: []rest.Attachment{{
			ID: "a1", ContentType: "image/png", URL: imageSrv.URL + "/cap.png",
		}},
	}

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	if err := d.handleMessageCreate(ctx, messageEvent(t, msg)); err != nil {
		t.Fatalf("handle captcha image: %v", err)
	}

	deadline := time.Now().Add(15 * time.Second)
	for time.Now().Before(deadline) {
		if dmHit.Load() > 0 {
			break
		}
		time.Sleep(10 * time.Millisecond)
	}

	if imageHit.Load() == 0 {
		t.Fatal("attachment tidak diunduh")
	}
	if solveHit.Load() == 0 {
		t.Fatal("captopia imagetotext tidak dipanggil")
	}
	if dmHit.Load() == 0 {
		t.Fatal("jawaban tidak dikirim ke DM OwO")
	}

	// Fallback: tanpa konfirmasi "Thank", captchaStatus direset dan loop lanjut.
	deadline = time.Now().Add(3 * time.Second)
	for time.Now().Before(deadline) {
		if !cfg.CaptchaActive() {
			break
		}
		time.Sleep(10 * time.Millisecond)
	}
	if cfg.CaptchaActive() {
		t.Fatal("captchaStatus tidak direset setelah fallback")
	}
	waitStarts(t, ctrl, 1)
}

// TestOwoDuelApplicationIDBukanAuthor memastikan application_id tetap milik OwO
// walau pesan duel dikirim oleh akun/webhook lain dan tidak membawa
// application_id. Discord menolak interaksi yang application_id-nya bukan
// pemilik komponen tombol.
func TestOwoDuelApplicationIDBukanAuthor(t *testing.T) {
	requests := make(chan map[string]any, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		requests <- body
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	st.SetSessionID("sess")
	d := New(Options{
		Config: cfg, State: st, REST: rc, Controller: newFakeController(),
		Timings: Timings{DuelClickDelay: 5 * time.Millisecond},
	})

	msg := rest.Message{
		ID: "999", ChannelID: "123", GuildID: "7",
		Content: "<@42>",
		// Author bukan OwO dan ApplicationID kosong: meniru pesan webhook.
		Author: rest.User{ID: "777777777777777777"},
		Embeds: []rest.Embed{{
			Author: &rest.EmbedAuthor{Name: "Someone challenges you to a duel!"},
		}},
		Components: []rest.ComponentRow{{
			Type: 1,
			Components: []rest.Component{{
				Type: 2, CustomID: "battle_accept",
			}},
		}},
	}
	if err := d.handleMessageCreate(context.Background(), messageEvent(t, msg)); err != nil {
		t.Fatalf("handle duel: %v", err)
	}

	select {
	case body := <-requests:
		if body["application_id"] != rest.OwoApplicationID {
			t.Fatalf("application_id harus %s (bukan author), dapat %v",
				rest.OwoApplicationID, body["application_id"])
		}
	case <-time.After(15 * time.Second):
		t.Fatal("tombol duel tidak diklik")
	}
}

// TestAutogemsHanyaMengisiSlotKosong memastikan alur lengkap: pesan hunt yang
// kehilangan diamond memicu owoinv, lalu inventory memicu use untuk diamond
// saja (bukan ketiganya). Mengirim gem yang masih aktif ditolak OwO.
func TestAutogemsHanyaMengisiSlotKosong(t *testing.T) {
	sent := make(chan string, 4)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		if c, ok := body["content"].(string); ok {
			sent <- c
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = io.WriteString(w, `{"id":"out","channel_id":"123"}`)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "status", true)
	mustSet(t, cfg, "statusgems", true)

	st := state.New()
	st.SetSelf(rest.User{ID: "42", Username: "tester"})
	d := New(Options{
		Config: cfg, State: st, REST: rc, Controller: newFakeController(),
		Timings: Timings{
			GemSpentMin:  time.Millisecond,
			GemSpentMax:  time.Millisecond,
			GemSelectMin: time.Millisecond,
			GemSelectMax: time.Millisecond,
			GemCooldown:  20 * time.Millisecond,
		},
	})
	ctx := context.Background()

	// 1. Hunt lengkap: jadi patokan, tidak boleh ada aksi.
	send := func(content string) {
		t.Helper()
		if err := d.handleMessageCreate(ctx, messageEvent(t, rest.Message{
			ID: "x", ChannelID: "123", GuildID: "7",
			Author: rest.User{ID: owoBotID}, Content: content,
		})); err != nil {
			t.Fatalf("handle: %v", err)
		}
	}

	send("**tester**, hunt is empowered by <a:fgem3:510366794792566785>`[224/1000]` " +
		"<a:fgem4:510366765340033025>`[224/1000]` <a:lgem1:1>`[3/100]` !")
	select {
	case c := <-sent:
		t.Fatalf("hunt lengkap tidak boleh memicu aksi, dapat %q", c)
	case <-time.After(50 * time.Millisecond):
	}

	// 2. Hunt berikutnya: diamond hilang -> minta inventory.
	send("**tester**, hunt is empowered by <a:fgem3:510366794792566785>`[199/1000]` " +
		"<a:fgem4:510366765340033025>`[198/1000]` !")
	select {
	case c := <-sent:
		if c != "owoinv" {
			t.Fatalf("harus minta inventory, dapat %q", c)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("owoinv tidak dikirim setelah diamond habis")
	}

	// 3. Inventory berisi gem ketiga bentuk -> hanya diamond yang di-use.
	send("**tester** inventory\n`051` `055` `065` `071` `072` `078`")
	select {
	case c := <-sent:
		if c != "owouse 55" {
			t.Fatalf("harus use diamond saja, dapat %q", c)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("owouse tidak dikirim")
	}
}
