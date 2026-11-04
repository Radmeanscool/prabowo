package events

import (
	"context"
	"fmt"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/config"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/gateway"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/solver"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/state"
)

const queueSize = 256

// Controller mengatur loop farming tanpa membuat events bergantung pada
// implementasi farm.
type Controller interface {
	Start()
	Stop()
	ScheduleRestart(time.Duration)
}

// Handler menangani satu dispatch gateway.
type Handler func(context.Context, gateway.Event) error

// Timings mengatur jeda handler. Nilai nol memakai default.
type Timings struct {
	CollectorWindow     time.Duration
	OwoOfflineDelay     time.Duration
	DuelClickDelay      time.Duration
	GemSpentMin         time.Duration
	GemSpentMax         time.Duration
	GemSelectMin        time.Duration
	GemSelectMax        time.Duration
	GemCooldown         time.Duration
	CaptchaImageConfirm time.Duration
	CaptchaConfirmPoll  time.Duration
	CaptchaPollEvery    time.Duration
	VerifyRetryDelay    time.Duration
	CaptchaResumeDelay  time.Duration
	CaptchaFallbackWait time.Duration
	ThankDelayMin       time.Duration
	ThankDelayMax       time.Duration
}

func (t Timings) withDefaults() Timings {
	set := func(d *time.Duration, v time.Duration) {
		if *d <= 0 {
			*d = v
		}
	}
	set(&t.CollectorWindow, 20*time.Second)
	set(&t.OwoOfflineDelay, 5*time.Minute)
	set(&t.DuelClickDelay, 5*time.Second)
	set(&t.GemSpentMin, 2*time.Second)
	set(&t.GemSpentMax, 5*time.Second)
	set(&t.GemSelectMin, 2*time.Second)
	set(&t.GemSelectMax, 4*time.Second)
	set(&t.GemCooldown, 15*time.Second)
	set(&t.CaptchaImageConfirm, 30*time.Second)
	set(&t.CaptchaConfirmPoll, 2*time.Second)
	set(&t.CaptchaPollEvery, 5*time.Second)
	set(&t.VerifyRetryDelay, 5*time.Second)
	set(&t.CaptchaResumeDelay, 15*time.Second)
	set(&t.CaptchaFallbackWait, 10*time.Second)
	set(&t.ThankDelayMin, 15*time.Second)
	set(&t.ThankDelayMax, 16*time.Second)
	return t
}

// Options mengatur dependensi Dispatcher.
type Options struct {
	Config     *config.Config
	State      *state.Store
	REST       *rest.Client
	Solver     *solver.Client
	Controller Controller
	Timings    Timings
	// Token adalah token akun (dari sesi Discord), dipakai membuat cookie
	// OAuth OwO. Config tidak lagi menyimpan token.
	Token string
}

// Dispatcher meneruskan dispatch gateway ke handler secara berurutan lewat
// antrean, sehingga handler yang lambat (REST) tidak memblokir gateway.
type Dispatcher struct {
	cfg     *config.Config
	state   *state.Store
	rest    *rest.Client
	solver  *solver.Client
	ctrl    Controller
	timings Timings
	token   string

	queue    chan gateway.Event
	handlers map[string]Handler

	owo     *owoHandler
	gems    *gemsHandler
	captcha *captchaHandler
}

// New membuat Dispatcher dengan handler bawaan.
func New(opts Options) *Dispatcher {
	st := opts.State
	if st == nil {
		st = state.New()
	}

	d := &Dispatcher{
		cfg:      opts.Config,
		state:    st,
		rest:     opts.REST,
		solver:   opts.Solver,
		ctrl:     opts.Controller,
		timings:  opts.Timings.withDefaults(),
		token:    opts.Token,
		queue:    make(chan gateway.Event, queueSize),
		handlers: make(map[string]Handler),
	}

	d.owo = &owoHandler{d: d}
	d.gems = &gemsHandler{d: d}
	d.captcha = &captchaHandler{d: d}

	d.On("MESSAGE_CREATE", d.handleMessageCreate)
	d.On("GUILD_CREATE", d.handleGuildCreate)
	d.On("CHANNEL_CREATE", d.handleChannelUpsert)
	d.On("CHANNEL_UPDATE", d.handleChannelUpsert)
	return d
}

// On mendaftarkan handler untuk tipe event. Mendaftar ulang akan menimpa.
func (d *Dispatcher) On(eventType string, h Handler) {
	d.handlers[eventType] = h
}

// Enqueue dipanggil dari gateway; tidak boleh blocking.
func (d *Dispatcher) Enqueue(e gateway.Event) {
	if e.Type == "" {
		return
	}
	select {
	case d.queue <- e:
	default:
		logger.Warn("DISCORD", "event queue full; dropping", e.Type)
	}
}

// Run memproses antrean sampai ctx dibatalkan.
func (d *Dispatcher) Run(ctx context.Context) {
	for {
		select {
		case <-ctx.Done():
			return
		case e := <-d.queue:
			h := d.handlers[e.Type]
			if h == nil {
				continue
			}
			if err := runHandler(ctx, h, e); err != nil {
				logger.Error("EVENT", e.Type+":", err)
			}
		}
	}
}

// TriggerCaptcha memulai protokol captcha tanpa menunggu pesan MESSAGE_CREATE
// dari OwO. Dipakai saat status captcha terdeteksi lewat polling website.
func (d *Dispatcher) TriggerCaptcha(ctx context.Context) {
	if d.ctrl == nil {
		return
	}
	if d.cfg.CaptchaActive() {
		return
	}
	logger.Warn("CAPTCHA", "captcha active detected via website")
	if err := d.cfg.Set("captchaStatus", true); err != nil {
		logger.Error("CONFIG", err)
	}
	d.stopLoop()
	safeGo("captcha website (poll)", func() { d.captcha.solveWebsite(ctx) })
}

func (d *Dispatcher) startLoop() {
	if d.ctrl != nil {
		d.ctrl.Start()
	}
}

func (d *Dispatcher) stopLoop() {
	if d.ctrl != nil {
		d.ctrl.Stop()
	}
}

func (d *Dispatcher) scheduleRestart(delay time.Duration) {
	if d.ctrl != nil {
		d.ctrl.ScheduleRestart(delay)
	}
}

func runHandler(ctx context.Context, h Handler, e gateway.Event) (err error) {
	defer func() {
		if r := recover(); r != nil {
			err = fmt.Errorf("panic: %v", r)
		}
	}()
	return h(ctx, e)
}
