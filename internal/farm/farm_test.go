package farm

import (
	"context"
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"path/filepath"
	"sync/atomic"
	"testing"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/config"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

func testConfig(t *testing.T) *config.Config {
	t.Helper()
	cfg, err := config.Load(filepath.Join(t.TempDir(), "config.json"))
	if err != nil {
		t.Fatalf("config.Load: %v", err)
	}
	return cfg
}

func mustSet(t *testing.T, cfg *config.Config, key string, value any) {
	t.Helper()
	if err := cfg.Set(key, value); err != nil {
		t.Fatalf("Set(%s): %v", key, err)
	}
}

func fastTimings() Timings {
	return Timings{
		CommandGapMin:   time.Millisecond,
		CommandGapMax:   time.Millisecond,
		TargetCycleMin:  20 * time.Millisecond,
		TargetCycleMax:  20 * time.Millisecond,
		MinRemaining:    time.Millisecond,
		ActionInterval:  10 * time.Millisecond,
		CaptchaPauseMin: 5 * time.Millisecond,
		CaptchaPauseMax: 5 * time.Millisecond,
		// Wajib ikut diperkecil. Nilai default 10 detik jauh lebih besar dari
		// siklus test (20ms), sehingga siklus kedua dianggap "double loop" dan
		// loop berhenti sebelum probeCaptcha sempat jalan.
		DoubleLoopGap:   5 * time.Millisecond,
		DoubleLoopHalt:  5 * time.Millisecond,
		DoubleLoopRetry: time.Millisecond,
	}
}

func newMessageServer(t *testing.T, sent chan<- string, status int, body string) *rest.Client {
	t.Helper()
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/channels/123/typing" {
			w.WriteHeader(http.StatusNoContent)
			return
		}
		if r.URL.Path == "/channels/123" && r.Method == http.MethodGet {
			w.Header().Set("Content-Type", "application/json")
			_, _ = io.WriteString(w, `{"id":"123","name":"farming","type":0}`)
			return
		}
		if r.URL.Path != "/channels/123/messages" {
			t.Errorf("path tak terduga: %s", r.URL.Path)
			return
		}
		if status != http.StatusOK {
			w.WriteHeader(status)
			_, _ = io.WriteString(w, body)
			return
		}
		var req map[string]any
		_ = json.NewDecoder(r.Body).Decode(&req)
		if content, ok := req["content"].(string); ok && sent != nil {
			sent <- content
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = io.WriteString(w, `{"id":"m","channel_id":"123"}`)
	}))
	t.Cleanup(srv.Close)

	c, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}
	return c
}

func TestLoopCommandsAndStop(t *testing.T) {
	sent := make(chan string, 128)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "action", "pray")

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	ctrl := New(ctx, Options{Config: cfg, REST: rc, Timings: fastTimings()})
	defer ctrl.Close()
	ctrl.Start()

	want := []string{"owoh", "owob", "owo", "owopray"}
	for i, expected := range want {
		select {
		case got := <-sent:
			if got != expected {
				t.Fatalf("command #%d salah: got %q want %q", i+1, got, expected)
			}
		case <-time.After(15 * time.Second):
			t.Fatalf("timeout menunggu command %q", expected)
		}
	}

	if !cfg.Status() {
		t.Fatal("status harus true saat loop berjalan")
	}

	ctrl.Stop()
	if cfg.Status() {
		t.Fatal("status harus false setelah Stop")
	}

	time.Sleep(30 * time.Millisecond)
	for len(sent) > 0 {
		<-sent
	}
	select {
	case extra := <-sent:
		t.Fatalf("masih ada command setelah Stop: %q", extra)
	case <-time.After(50 * time.Millisecond):
	}
}

// TestChannelIDMelacakKanalAktif mengunci getter yang dipakai hot-reload untuk
// memutuskan perpindahan kanal.
func TestChannelIDMelacakKanalAktif(t *testing.T) {
	sent := make(chan string, 8)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")

	ctrl := New(context.Background(), Options{Config: cfg, REST: rc, Timings: fastTimings()})
	defer ctrl.Close()

	if got := ctrl.ChannelID(); got != "" {
		t.Fatalf("sebelum start harus kosong, dapat %q", got)
	}

	ctrl.Start()
	deadline := time.Now().Add(2 * time.Second)
	for time.Now().Before(deadline) && ctrl.ChannelID() != "123" {
		time.Sleep(5 * time.Millisecond)
	}
	if got := ctrl.ChannelID(); got != "123" {
		t.Fatalf("kanal aktif salah: %q", got)
	}
}

func TestStartInvalidChannel(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		calls.Add(1)
	}))
	defer srv.Close()

	rc, err := rest.New(rest.Options{Token: "TESTTOKEN.abcd.efgh", BaseURL: srv.URL})
	if err != nil {
		t.Fatalf("rest.New: %v", err)
	}

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "-")

	ctrl := New(context.Background(), Options{Config: cfg, REST: rc, Timings: fastTimings()})
	ctrl.Start()
	time.Sleep(20 * time.Millisecond)
	ctrl.Close()

	if cfg.Status() {
		t.Fatal("status harus false untuk channel tidak valid")
	}
	if calls.Load() != 0 {
		t.Fatalf("tidak boleh ada request, dapat %d", calls.Load())
	}
}

func TestScheduleRestartStartsLoop(t *testing.T) {
	sent := make(chan string, 8)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "status", false)

	ctrl := New(context.Background(), Options{Config: cfg, REST: rc, Timings: fastTimings()})
	defer ctrl.Close()
	ctrl.ScheduleRestart(10 * time.Millisecond)

	select {
	case got := <-sent:
		if got != "owoh" {
			t.Fatalf("command pertama salah: %q", got)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("ScheduleRestart tidak memulai loop")
	}
	ctrl.Stop()
}

func TestCaptchaPause(t *testing.T) {
	sent := make(chan string, 16)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "captchaStatus", true)

	timings := fastTimings()
	timings.CaptchaPauseMin = 10 * time.Millisecond
	timings.CaptchaPauseMax = 10 * time.Millisecond

	ctrl := New(context.Background(), Options{Config: cfg, REST: rc, Timings: timings})
	defer ctrl.Close()
	ctrl.Start()

	select {
	case got := <-sent:
		t.Fatalf("tidak boleh mengirim saat captcha aktif: %q", got)
	case <-time.After(60 * time.Millisecond):
	}

	mustSet(t, cfg, "captchaStatus", false)

	select {
	case got := <-sent:
		if got != "owoh" {
			t.Fatalf("command setelah captcha salah: %q", got)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("loop tidak lanjut setelah captcha selesai")
	}
	ctrl.Stop()
}

func TestAuthErrorStops(t *testing.T) {
	rc := newMessageServer(t, nil, http.StatusUnauthorized, `{"message":"401: Unauthorized","code":0}`)

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")

	ctrl := New(context.Background(), Options{Config: cfg, REST: rc, Timings: fastTimings()})
	defer ctrl.Close()
	ctrl.Start()

	deadline := time.Now().Add(15 * time.Second)
	for time.Now().Before(deadline) {
		if !cfg.Status() {
			return
		}
		time.Sleep(5 * time.Millisecond)
	}
	t.Fatal("status tidak berhenti setelah error AUTH")
}

func TestIsTransient(t *testing.T) {
	cases := []struct {
		err  error
		want bool
	}{
		{&rest.APIError{Status: 502}, true},
		{&rest.APIError{Status: 404}, false},
		{errString("socket hang up"), true},
		{errString("ENOTFOUND discord.com"), true},
		{errString("invalid token"), false},
	}
	for _, c := range cases {
		if got := isTransient(c.err); got != c.want {
			t.Errorf("isTransient(%v) = %v, want %v", c.err, got, c.want)
		}
	}
}

type errString string

func (e errString) Error() string { return string(e) }

// TestInteractionFailedRestarts mengunci: slash terkirim tanpa balasan OwO
// (ErrInteractionFailed) menjeda farming lalu menjadwalkan restart, bukan
// dianggap error tak dikenal.
func TestInteractionFailedRestarts(t *testing.T) {
	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")

	ctrl := New(context.Background(), Options{Config: cfg, Timings: fastTimings()})
	defer ctrl.Close()

	retries := 0
	stop := ctrl.handleError(context.Background(), rest.ErrInteractionFailed, &retries)
	if !stop {
		t.Fatal("ErrInteractionFailed harus menghentikan loop sementara")
	}

	ctrl.mu.Lock()
	scheduled := ctrl.scheduled
	ctrl.restartTimer.Stop()
	ctrl.mu.Unlock()
	if !scheduled {
		t.Fatal("harus ada restart terjadwal setelah ErrInteractionFailed")
	}
}

func TestDetectDoubleLoop(t *testing.T) {
	ctrl := New(nil, Options{Timings: Timings{DoubleLoopGap: 10 * time.Second}})

	now := time.Now()
	if _, doubled := ctrl.detectDoubleLoop(now); doubled {
		t.Fatal("hunt pertama tidak boleh dianggap double-loop")
	}
	if gap, doubled := ctrl.detectDoubleLoop(now.Add(3 * time.Second)); !doubled {
		t.Fatal("gap 3s harus terdeteksi double-loop")
	} else if gap != 3*time.Second {
		t.Fatalf("gap salah: %s", gap)
	}
	if _, doubled := ctrl.detectDoubleLoop(now.Add(30 * time.Second)); doubled {
		t.Fatal("gap 27s tidak boleh dianggap double-loop")
	}
}

func TestCaptchaProbeStopsLoop(t *testing.T) {
	sent := make(chan string, 128)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "action", "pray")

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	var checks atomic.Int32
	triggered := make(chan struct{}, 1)
	ctrl := New(ctx, Options{
		Config:  cfg,
		REST:    rc,
		Timings: fastTimings(),
		CheckCaptcha: func(context.Context) (bool, error) {
			checks.Add(1)
			return true, nil
		},
		OnCaptcha: func(context.Context) {
			select {
			case triggered <- struct{}{}:
			default:
			}
		},
	})
	defer ctrl.Close()
	ctrl.Start()

	select {
	case <-triggered:
	case <-time.After(15 * time.Second):
		t.Fatal("OnCaptcha tidak dipanggil setelah status captcha aktif")
	}

	if checks.Load() == 0 {
		t.Fatal("CheckCaptcha tidak pernah dipanggil")
	}

	deadline := time.Now().Add(time.Second)
	for ctrl.running.Load() && time.Now().Before(deadline) {
		time.Sleep(5 * time.Millisecond)
	}
	if ctrl.running.Load() {
		t.Fatal("loop harus berhenti setelah captcha terdeteksi")
	}
}

func TestSlashModeSendsOnlyHuntBattle(t *testing.T) {
	sentText := make(chan string, 128)
	rc := newMessageServer(t, sentText, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "/")

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	slash := make(chan string, 64)
	ctrl := New(ctx, Options{
		Config:  cfg,
		REST:    rc,
		Timings: fastTimings(),
		SendSlash: func(_ context.Context, name string) (time.Duration, error) {
			slash <- name
			return time.Millisecond, nil
		},
	})
	defer ctrl.Close()
	ctrl.Start()

	for _, want := range []string{"hunt", "battle"} {
		select {
		case got := <-slash:
			if got != want {
				t.Fatalf("slash salah: got %q want %q", got, want)
			}
		case <-time.After(15 * time.Second):
			t.Fatalf("timeout menunggu slash %q", want)
		}
	}

	if len(sentText) != 0 {
		t.Fatalf("mode slash tidak boleh mengirim pesan teks, dapat %d", len(sentText))
	}

	ctrl.Stop()
}

// TestPrayTidakBerulangSaatRestart mengunci perilaku: pray hanya dikirim
// sekali per ActionInterval, walau loop berhenti-lalu-mulai berkali-kali
// (captcha selesai, autogems, network error). Sebelumnya waktu pray disimpan
// sebagai variabel lokal, sehingga setiap restart mengirim pray lagi.
func TestPrayTidakBerulangSaatRestart(t *testing.T) {
	sent := make(chan string, 256)
	rc := newMessageServer(t, sent, http.StatusOK, "")

	cfg := testConfig(t)
	mustSet(t, cfg, "channelId", "123")
	mustSet(t, cfg, "prefix", "owo")
	mustSet(t, cfg, "action", "pray")

	timings := fastTimings()
	// Interval jauh lebih panjang dari umur tiap loop, meniru 5 menit nyata.
	timings.ActionInterval = time.Hour

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	ctrl := New(ctx, Options{Config: cfg, REST: rc, Timings: timings})
	defer ctrl.Close()

	// Kumpulkan semua perintah yang terkirim selama durasi tertentu tanpa
	// membuang apa pun, supaya penghitungan pray tidak salah.
	collect := func(d time.Duration) []string {
		var got []string
		deadline := time.After(d)
		for {
			select {
			case c := <-sent:
				got = append(got, c)
			case <-deadline:
				return got
			}
		}
	}

	countPray := func(cmds []string) int {
		n := 0
		for _, c := range cmds {
			if c == "owopray" {
				n++
			}
		}
		return n
	}

	// loop memberi jeda acak sampai 500ms setelah tiap siklus, jadi putaran
	// pertama ditunggu sampai pray benar-benar muncul (maks 10 detik).
	waitPray := func() []string {
		var got []string
		deadline := time.After(10 * time.Second)
		for {
			select {
			case c := <-sent:
				got = append(got, c)
				if c == "owopray" {
					// Kumpulkan sisa sebentar untuk mendeteksi duplikat.
					got = append(got, collect(200*time.Millisecond)...)
					return got
				}
			case <-deadline:
				return got
			}
		}
	}

	// Putaran pertama: harus mengirim pray.
	ctrl.Start()
	first := waitPray()
	ctrl.Stop()
	time.Sleep(50 * time.Millisecond)
	first = append(first, collect(50*time.Millisecond)...)

	if countPray(first) != 1 {
		t.Fatalf("putaran pertama harus 1 pray, dapat %d dari %v", countPray(first), first)
	}

	// Putaran ke-2 dan ke-3: interval 1 jam belum lewat, pray tidak boleh dikirim.
	for i := 0; i < 2; i++ {
		ctrl.Start()
		// Beri waktu lebih dari satu siklus penuh; pray hanya boleh muncul
		// pada putaran pertama.
		cmds := collect(900 * time.Millisecond)
		ctrl.Stop()
		time.Sleep(50 * time.Millisecond)
		cmds = append(cmds, collect(50*time.Millisecond)...)
		if n := countPray(cmds); n != 0 {
			t.Fatalf("putaran ke-%d tidak boleh mengirim pray, dapat %d dari %v", i+2, n, cmds)
		}
	}
}

// waitFor menunggu satu perintah tertentu terkirim, membuang yang lain.
func waitFor(t *testing.T, ch chan string, want string) {
	t.Helper()
	deadline := time.After(15 * time.Second)
	for {
		select {
		case got := <-ch:
			if got == want {
				return
			}
		case <-deadline:
			t.Fatalf("timeout menunggu %q", want)
		}
	}
}
