package farm

import (
	"context"
	"errors"
	"fmt"
	"math/rand/v2"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/config"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

const (
	defaultCommandGap   = 500 * time.Millisecond
	defaultCycle        = 16 * time.Second
	defaultMinRemaining = 3 * time.Second
	defaultAction       = 5 * time.Minute
	defaultMaxRetries   = 5
	defaultTransRestart = 5 * time.Minute
)

// Timings mengatur jeda loop. Nilai nol memakai default.
type Timings struct {
	CommandGapMin    time.Duration
	CommandGapMax    time.Duration
	TargetCycleMin   time.Duration
	TargetCycleMax   time.Duration
	MinRemaining     time.Duration
	ActionInterval   time.Duration
	RateLimitMin     time.Duration
	RateLimitMax     time.Duration
	TransientMin     time.Duration
	TransientMax     time.Duration
	UnknownMin       time.Duration
	UnknownMax       time.Duration
	CaptchaPauseMin  time.Duration
	CaptchaPauseMax  time.Duration
	TransientRestart time.Duration
	// InteractionRestart dipakai saat slash command terkirim tetapi tidak
	// dibalas (OwO offline di mode slash).
	InteractionRestart time.Duration
	DoubleLoopGap      time.Duration
	DoubleLoopHalt     time.Duration
	DoubleLoopRetry    time.Duration
	MaxRetries         int
}

func (t Timings) withDefaults() Timings {
	set := func(d *time.Duration, v time.Duration) {
		if *d <= 0 {
			*d = v
		}
	}
	set(&t.CommandGapMin, defaultCommandGap)
	set(&t.CommandGapMax, 2*defaultCommandGap)
	set(&t.TargetCycleMin, defaultCycle)
	set(&t.TargetCycleMax, defaultCycle+2*time.Second)
	set(&t.MinRemaining, defaultMinRemaining)
	set(&t.ActionInterval, defaultAction)
	set(&t.RateLimitMin, 60*time.Second)
	set(&t.RateLimitMax, 90*time.Second)
	set(&t.TransientMin, 5*time.Second)
	set(&t.TransientMax, 8*time.Second)
	set(&t.UnknownMin, 10*time.Second)
	set(&t.UnknownMax, 15*time.Second)
	set(&t.CaptchaPauseMin, 5*time.Second)
	set(&t.CaptchaPauseMax, 6*time.Second)
	set(&t.TransientRestart, defaultTransRestart)
	set(&t.InteractionRestart, 5*time.Minute)
	set(&t.DoubleLoopGap, 10*time.Second)
	set(&t.DoubleLoopHalt, 20*time.Second)
	set(&t.DoubleLoopRetry, time.Second)
	if t.MaxRetries <= 0 {
		t.MaxRetries = defaultMaxRetries
	}
	return t
}

// CaptchaChecker memeriksa status captcha akun via website OwO. Mengembalikan
// true bila akun sedang diminta menyelesaikan captcha.
type CaptchaChecker func(ctx context.Context) (bool, error)

// SlashSender mengirim slash command (mis. "hunt") ke channel farming.
type SlashSender func(ctx context.Context, name string) (time.Duration, error)

// Options mengatur Controller.
type Options struct {
	Config  *config.Config
	REST    *rest.Client
	Timings Timings
	// CheckCaptcha opsional; dipanggil setelah tiap cycle selesai.
	CheckCaptcha CaptchaChecker
	// OnCaptcha dipanggil saat CheckCaptcha melaporkan captcha aktif.
	OnCaptcha func(ctx context.Context)
	// SendSlash opsional; wajib bila prefix "/" (mode slash command).
	SendSlash SlashSender
}

// Controller menjalankan loop farming dan restart terjadwal.
type Controller struct {
	cfg          *config.Config
	rest         *rest.Client
	base         context.Context
	timings      Timings
	checkCaptcha CaptchaChecker
	onCaptcha    func(ctx context.Context)
	sendSlash    SlashSender

	mu           sync.Mutex
	running      atomic.Bool
	scheduled    bool
	loopCancel   context.CancelFunc
	restartTimer *time.Timer
	closed       bool
	lastHunt     time.Time
	// currentChannel adalah channelId yang sedang di-farm, dipakai hot-reload
	// untuk mendeteksi perpindahan kanal.
	currentChannel string
	// lastPray bertahan lintas restart loop. Sebelumnya waktu pray disimpan
	// sebagai variabel lokal di loop(), sehingga setiap restart (captcha
	// selesai, autogems, network error) mengirim pray lagi walau 5 menit belum
	// lewat.
	lastPray time.Time
	wg       sync.WaitGroup
}

// New membuat Controller. ctx adalah lifetime proses.
func New(ctx context.Context, opts Options) *Controller {
	if ctx == nil {
		ctx = context.Background()
	}
	return &Controller{
		cfg:          opts.Config,
		rest:         opts.REST,
		base:         ctx,
		timings:      opts.Timings.withDefaults(),
		checkCaptcha: opts.CheckCaptcha,
		onCaptcha:    opts.OnCaptcha,
		sendSlash:    opts.SendSlash,
	}
}

// Start memulai loop bila belum berjalan. Idempoten.
func (c *Controller) Start() {
	c.mu.Lock()
	if c.closed || c.running.Load() {
		c.mu.Unlock()
		return
	}
	c.cancelRestartLocked()

	channelID := c.cfg.ChannelID()
	if channelID == "" || channelID == "-" {
		c.mu.Unlock()
		c.setStatus(false)
		logger.Error("LOOP", "channelId invalid, farming cancelled")
		return
	}
	if c.rest == nil {
		c.mu.Unlock()
		c.setStatus(false)
		logger.Error("LOOP", "REST client unavailable")
		return
	}

	loopCtx, cancel := context.WithCancel(c.base)
	c.loopCancel = cancel
	c.currentChannel = channelID
	c.running.Store(true)
	c.wg.Add(1)
	c.mu.Unlock()

	c.setStatus(true)
	go c.loop(loopCtx, channelID)
}

// Stop menghentikan loop dan membatalkan restart terjadwal.
func (c *Controller) Stop() {
	c.mu.Lock()
	c.cancelRestartLocked()
	c.running.Store(false)
	if c.loopCancel != nil {
		c.loopCancel()
		c.loopCancel = nil
	}
	c.mu.Unlock()

	c.setStatus(false)
}

// ScheduleRestart menjadwalkan Start setelah delay.
func (c *Controller) ScheduleRestart(delay time.Duration) {
	if delay < 0 {
		delay = 0
	}

	c.mu.Lock()
	if c.closed {
		c.mu.Unlock()
		return
	}
	if c.restartTimer != nil {
		c.restartTimer.Stop()
	}
	c.scheduled = true
	c.restartTimer = time.AfterFunc(delay, c.onRestart)
	c.mu.Unlock()
}

// Close menghentikan semuanya dan menunggu goroutine loop selesai.
func (c *Controller) Close() {
	c.mu.Lock()
	c.closed = true
	c.mu.Unlock()

	c.Stop()
	c.wg.Wait()
}

func (c *Controller) onRestart() {
	c.mu.Lock()
	c.scheduled = false
	c.restartTimer = nil
	if c.closed {
		c.mu.Unlock()
		return
	}
	if c.running.Load() {
		c.mu.Unlock()
		c.ScheduleRestart(time.Second)
		return
	}
	c.mu.Unlock()

	if c.cfg.CaptchaActive() {
		return
	}
	if id := c.cfg.ChannelID(); id == "" || id == "-" {
		return
	}
	c.Start()
}

func (c *Controller) cancelRestartLocked() {
	if c.restartTimer != nil {
		c.restartTimer.Stop()
		c.restartTimer = nil
	}
	c.scheduled = false
}

func (c *Controller) setStatus(v bool) {
	if err := c.cfg.Set("status", v); err != nil {
		logger.Error("CONFIG", err)
	}
}

func (c *Controller) active() bool {
	return c.running.Load() && c.cfg.Status()
}

// ChannelID mengembalikan kanal yang sedang di-farm (kosong bila tidak jalan).
func (c *Controller) ChannelID() string {
	c.mu.Lock()
	defer c.mu.Unlock()
	return c.currentChannel
}

func (c *Controller) loop(ctx context.Context, channelID string) {
	defer c.wg.Done()
	defer func() {
		if r := recover(); r != nil {
			logger.Error("EXCEPTION", "farm loop:", fmt.Sprint(r))
		}
		c.running.Store(false)
		c.mu.Lock()
		if c.loopCancel != nil {
			c.loopCancel()
			c.loopCancel = nil
		}
		c.mu.Unlock()
	}()

	if err := c.verifyChannel(ctx, channelID); err != nil {
		logger.Error("LOOP", "failed to fetch channel:", err)
		c.setStatus(false)
		return
	}

	c.lastHunt = time.Time{}
	retries := 0
	cycles := 0

	// Mode slash: prefix "/" berarti hanya /hunt dan /battle yang dikirim
	// (tanpa pesan teks owo/pray/typing). Cocok untuk akun yang kena rate
	// limit kirim pesan tetapi masih bisa slash.
	slashMode := strings.TrimSpace(c.cfg.Prefix()) == "/"
	if slashMode && c.sendSlash == nil {
		logger.Error("LOOP", "slash mode active but the slash sender is not ready")
		c.setStatus(false)
		return
	}

	sendHunt := func() (time.Duration, error) {
		if slashMode {
			return c.sendSlash(ctx, "hunt")
		}
		return c.send(ctx, channelID, c.cfg.Prefix()+"h")
	}
	sendBattle := func() (time.Duration, error) {
		if slashMode {
			return c.sendSlash(ctx, "battle")
		}
		return c.send(ctx, channelID, c.cfg.Prefix()+"b")
	}

	for c.active() && ctx.Err() == nil {
		if c.cfg.CaptchaActive() {
			sleepCtx(ctx, jitter(c.timings.CaptchaPauseMin, c.timings.CaptchaPauseMax))
			continue
		}

		if !slashMode {
			go func() {
				defer func() { _ = recover() }()
				_ = c.rest.SendTyping(ctx, channelID)
			}()
		}

		cycleStart := time.Now()

		huntMs, err := sendHunt()
		if err != nil {
			if c.handleError(ctx, err, &retries) {
				return
			}
			continue
		}
		if gap, doubled := c.detectDoubleLoop(time.Now()); doubled {
			logger.Warn("DOUBLE-LOOP", fmt.Sprintf("detected! gap=%ds, unloop + cooldown %s",
				int(gap.Seconds()), c.timings.DoubleLoopHalt))
			c.Stop()
			go c.doubleLoopCooldown()
			return
		}
		if !sleepCtx(ctx, jitter(c.timings.CommandGapMin, c.timings.CommandGapMax)) || !c.active() {
			break
		}

		battleMs, err := sendBattle()
		if err != nil {
			if c.handleError(ctx, err, &retries) {
				return
			}
			continue
		}
		if !sleepCtx(ctx, jitter(c.timings.CommandGapMin, c.timings.CommandGapMax)) || !c.active() {
			break
		}

		var owoMs time.Duration
		if !slashMode {
			owoMs, err = c.send(ctx, channelID, "owo")
			if err != nil {
				if c.handleError(ctx, err, &retries) {
					return
				}
				continue
			}
		}

		remaining := jitter(c.timings.TargetCycleMin, c.timings.TargetCycleMax) - time.Since(cycleStart)
		if remaining < c.timings.MinRemaining {
			remaining = c.timings.MinRemaining
		}
		if !sleepCtx(ctx, remaining+jitter(0, 500*time.Millisecond)) || !c.active() {
			break
		}

		if !slashMode && c.prayDue() {
			action := c.cfg.Action()
			if action == "" {
				action = "pray"
			}
			if _, err := c.send(ctx, channelID, c.cfg.Prefix()+action); err != nil {
				if c.handleError(ctx, err, &retries) {
					return
				}
				continue
			}
			c.markPray()
		}

		total := time.Since(cycleStart)
		worst := huntMs
		if battleMs > worst {
			worst = battleMs
		}
		if owoMs > worst {
			worst = owoMs
		}
		cycles++
		logger.Cycle(cycles, int(total.Milliseconds()), int(worst.Milliseconds()))
		retries = 0

		if active, err := c.probeCaptcha(ctx); err != nil {
			logger.Warn("CAPTCHA", "captcha status check failed:", err)
		} else if active {
			logger.Warn("CAPTCHA", "account needs verification, stopping farming")
			c.Stop()
			if c.onCaptcha != nil {
				c.onCaptcha(ctx)
			}
			return
		}
	}
}

// probeCaptcha memanggil checker bila tersedia. Mengembalikan false tanpa
// error bila checker tidak dikonfigurasi.
func (c *Controller) probeCaptcha(ctx context.Context) (bool, error) {
	if c.checkCaptcha == nil {
		return false, nil
	}
	return c.checkCaptcha(ctx)
}

// verifyChannel memastikan channel tujuan bisa diakses sebelum farming.
func (c *Controller) verifyChannel(ctx context.Context, channelID string) error {
	if c.rest == nil {
		return errors.New("rest client unavailable")
	}
	if _, err := c.rest.Channel(ctx, channelID); err != nil {
		return err
	}
	return nil
}

// prayDue melaporkan apakah action (pray/curse) sudah waktunya dikirim.
//
// Waktu terakhir disimpan di Controller, bukan di variabel lokal loop(),
// sehingga restart loop tidak mengirim pray lebih cepat dari ActionInterval.
// Selisih negatif (jam sistem mundur) diperlakukan sebagai "sudah waktunya"
// agar pray tidak tertahan selamanya.
func (c *Controller) prayDue() bool {
	c.mu.Lock()
	last := c.lastPray
	c.mu.Unlock()

	if last.IsZero() {
		return true
	}
	elapsed := time.Since(last)
	return elapsed < 0 || elapsed >= c.timings.ActionInterval
}

// markPray mencatat waktu action terakhir terkirim.
func (c *Controller) markPray() {
	c.mu.Lock()
	c.lastPray = time.Now()
	c.mu.Unlock()
}

// detectDoubleLoop mendeteksi hunt kedua yang terlalu berdekatan.
func (c *Controller) detectDoubleLoop(now time.Time) (time.Duration, bool) {
	if c.lastHunt.IsZero() || now.Sub(c.lastHunt) >= c.timings.DoubleLoopGap {
		c.lastHunt = now
		return 0, false
	}
	gap := now.Sub(c.lastHunt)
	c.lastHunt = now
	return gap, true
}

func (c *Controller) doubleLoopCooldown() {
	if !sleepCtx(c.base, c.timings.DoubleLoopHalt) {
		return
	}
	c.ScheduleRestart(c.timings.DoubleLoopRetry)
}

// handleError mengembalikan true bila loop harus berhenti.
func (c *Controller) handleError(ctx context.Context, err error, retries *int) bool {
	if ctx.Err() != nil {
		return true
	}

	var apiErr *rest.APIError
	if errors.As(err, &apiErr) {
		switch {
		case apiErr.Status == 429:
			logger.Warn("RATE LIMIT", "account rate limited, waiting 60s")
			sleepCtx(ctx, jitter(c.timings.RateLimitMin, c.timings.RateLimitMax))
			return false
		case apiErr.Status == 401 || apiErr.Status == 403:
			logger.Error("AUTH", "Token invalid or account banned:", err)
			c.setStatus(false)
			return true
		case apiErr.Status == 404:
			logger.Error("LOOP", "Channel not found, farming stopped")
			c.setStatus(false)
			return true
		}
	}

	if isTransient(err) {
		*retries++
		logger.Warn("LOOP", fmt.Sprintf("network error (%d/%d): %v", *retries, c.timings.MaxRetries, err))
		if *retries >= c.timings.MaxRetries {
			logger.Warn("LOOP", "too many failures, farming paused, retry in 5m")
			c.ScheduleRestart(c.timings.TransientRestart)
			return true
		}
		mult := time.Duration(*retries)
		sleepCtx(ctx, jitter(c.timings.TransientMin*mult, c.timings.TransientMax*mult))
		return false
	}

	// Slash command terkirim (HTTP 2xx) tetapi tidak dibalas dalam batas waktu:
	// OwO tidak merespons. Jeda farming lalu coba lagi (meniru "owo offline").
	if errors.Is(err, rest.ErrInteractionFailed) {
		logger.Warn("DOWN", "owo did not respond to slash command, farming paused, retry in 5m")
		c.ScheduleRestart(c.timings.InteractionRestart)
		return true
	}

	*retries++
	logger.Error("LOOP", "unknown error:", err)
	if *retries >= c.timings.MaxRetries {
		logger.Error("LOOP", "max retries reached, farming stopped")
		c.setStatus(false)
		return true
	}
	sleepCtx(ctx, jitter(c.timings.UnknownMin, c.timings.UnknownMax))
	return false
}

func (c *Controller) send(ctx context.Context, channelID, content string) (time.Duration, error) {
	start := time.Now()
	_, err := c.rest.SendMessage(ctx, channelID, content)
	elapsed := time.Since(start)
	if err != nil {
		return elapsed, err
	}
	return elapsed, nil
}

func isTransient(err error) bool {
	var apiErr *rest.APIError
	if errors.As(err, &apiErr) {
		return apiErr.Status >= 500 && apiErr.Status < 600
	}

	msg := strings.ToLower(err.Error())
	for _, s := range []string{
		"econnreset", "etimedout", "socket hang up", "network",
		"connect", "enotfound", "fetch failed", "aborted", "timeout",
	} {
		if strings.Contains(msg, s) {
			return true
		}
	}
	return false
}

func jitter(min, max time.Duration) time.Duration {
	if max <= min {
		return min
	}
	span := int64(max-min) / int64(time.Millisecond)
	if span <= 0 {
		return min
	}
	return min + time.Duration(rand.Int64N(span+1))*time.Millisecond
}

func sleepCtx(ctx context.Context, d time.Duration) bool {
	if d <= 0 {
		return true
	}
	timer := time.NewTimer(d)
	defer timer.Stop()
	select {
	case <-ctx.Done():
		return false
	case <-timer.C:
		return true
	}
}
