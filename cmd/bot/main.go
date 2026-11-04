package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/config"
	owocookie "github.com/Pangleran/discordgo-owoselfbot-v1/internal/cookie"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/discordapp"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/env"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/environment"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/events"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/farm"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/gateway"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/instance"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/solver"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/state"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/token"
)

const version = "0.8.0"

func main() {
	cfgPath := flag.String("config", config.DefaultPath(), "path to config file")
	envPath := flag.String("env", ".env", "path to .env file (secrets)")
	showVersion := flag.Bool("version", false, "print version then exit")
	me := flag.Bool("me", false, "print account info via REST then exit")
	send := flag.String("send", "", "send text to a channel via REST then exit")
	channelFlag := flag.String("channel", "", "override channel id for -send")
	gatewayFlag := flag.Bool("gateway", false, "connect to the gateway until Ctrl+C")
	tokenStatus := flag.Bool("token-status", false, "print the account in use then exit")
	flag.Parse()

	if *showVersion {
		fmt.Printf("discordgo-selfbot %s\n", version)
		return
	}

	envStore, err := env.Load(*envPath)
	if err != nil {
		logger.Error("ENV", err)
		os.Exit(1)
	}

	if *tokenStatus {
		cfg, err := config.Load(*cfgPath)
		if err != nil {
			logger.Error("CONFIG", err)
			os.Exit(1)
		}
		runTokenStatus(cfg, envStore)
		return
	}

	cfg, err := config.Load(*cfgPath)
	if err != nil {
		logger.Error("CONFIG", err)
		os.Exit(1)
	}
	if err := cfg.Validate(); err != nil {
		logger.Error("CONFIG", err)
		os.Exit(1)
	}

	if *gatewayFlag {
		runGateway(cfg, envStore)
		return
	}

	if !*me && *send == "" {
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	acc, err := resolveAccount(ctx, cfg, envStore)
	if err != nil {
		logger.Error("TOKEN", err)
		os.Exit(1)
	}
	client, err := rest.New(rest.Options{Token: acc.Token})
	if err != nil {
		logger.Error("DISCORD", err)
		os.Exit(1)
	}

	if *me {
		start := time.Now()
		user, err := client.Me(ctx)
		if err != nil {
			logger.Error("AUTH", err)
			os.Exit(1)
		}
		logger.Log("READY", "logged in as", user.Tag(), "("+user.ID+")",
			fmt.Sprintf("%dms", time.Since(start).Milliseconds()))
	}

	if *send != "" {
		channelID := *channelFlag
		if channelID == "" {
			channelID = cfg.ChannelID()
		}
		if channelID == "" || channelID == "-" {
			logger.Error("CONFIG", "channelId is empty (use -channel)")
			os.Exit(1)
		}

		start := time.Now()
		msg, err := client.SendMessage(ctx, channelID, *send)
		if err != nil {
			logger.Error("DISCORD", err)
			os.Exit(1)
		}
		logger.Command(*send, int(time.Since(start).Milliseconds()))
		logger.Log("INFO", "sent, id", msg.ID)
	}
}

// openDiscordChannel membuka aplikasi Discord dan mengarahkannya ke channelId
// dari config.json.
//
// Kegagalan hanya dilaporkan, tidak menghentikan bot: farming berjalan lewat
// REST/gateway dan tidak bergantung pada jendela Discord yang terbuka.
//
// Bila token berasal dari .env (mode server/VPS/termux), aplikasi Discord tidak
// dibuka sama sekali.
func openDiscordChannel(cfg *config.Config, envStore *env.Store) {
	if strings.TrimSpace(envStore.Get("DISCORD_TOKEN")) != "" {
		logger.Log("DISCORD", "token from .env, not opening Discord app")
		return
	}
	id := strings.TrimSpace(cfg.ChannelID())
	if id == "" || id == "-" {
		logger.Log("DISCORD", "channelId is empty, opening Discord without a target channel")
		return
	}
	if err := discordapp.OpenChannel(id); err != nil {
		logger.Error("DISCORD", "failed to open Discord:", err)
		return
	}
	logger.Log("DISCORD", "opening Discord at channel", id)
}

func runGateway(cfg *config.Config, envStore *env.Store) {
	lock, err := instance.Acquire(cfg.Path() + ".lock")
	if err != nil {
		if errors.Is(err, instance.ErrAlreadyRunning) {
			logger.Error("CONFIG", "another instance is already running; stop it before starting again")
		} else {
			logger.Error("CONFIG", err)
		}
		os.Exit(1)
	}
	defer func() { _ = lock.Release() }()

	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	if err := cfg.Set("status", false); err != nil {
		logger.Error("CONFIG", err)
	}
	if err := cfg.Set("captchaStatus", false); err != nil {
		logger.Error("CONFIG", err)
	}

	// Buka Discord dan arahkan ke kanal farming. Tidak fatal bila gagal:
	// farming tetap jalan lewat REST, hanya jendela Discord yang tidak muncul.
	openDiscordChannel(cfg, envStore)

	invalidToken := make(chan struct{}, 1)

	err = runSession(ctx, cfg, envStore, invalidToken)

	select {
	case <-invalidToken:
		// Token tidak valid. Arahan disesuaikan dengan lingkungan.
		logger.Log("DISCORD", "stopped;", environment.Detect().Hints().TokenInvalid)
		os.Exit(1)
	default:
	}

	if err != nil {
		logger.Error("DISCORD", err)
		os.Exit(1)
	}
	logger.Log("DISCORD", "stopped")
}

// runSession membuka satu koneksi gateway. Sinyal token invalid dikirim ke
// invalidToken tanpa mematikan proses.
func runSession(ctx context.Context, cfg *config.Config, envStore *env.Store, invalidToken chan<- struct{}) error {
	gwCfg := cfg.GatewaySettings()

	st := state.New()
	acc, err := resolveAccount(ctx, cfg, envStore)
	if err != nil {
		return err
	}
	logger.Log("TOKEN", "account", acc.Username, "("+acc.ID+")", "token", tokenSource(envStore))
	restClient, err := rest.New(rest.Options{Token: acc.Token})
	if err != nil {
		return err
	}
	solverClient := buildSolver(cfg, envStore)

	var dispatcher *events.Dispatcher

	captchaCheck := newCaptchaStatusChecker(cfg, solverClient, acc.Token)
	slashSender, warmSlash := newSlashSender(ctx, cfg, restClient, st)
	farmCtl := farm.New(ctx, farm.Options{
		Config:       cfg,
		REST:         restClient,
		CheckCaptcha: captchaCheck,
		SendSlash:    slashSender,
		OnCaptcha: func(ctx context.Context) {
			if dispatcher != nil {
				dispatcher.TriggerCaptcha(ctx)
			}
		},
	})
	defer farmCtl.Close()

	dispatcher = events.New(events.Options{
		Config:     cfg,
		State:      st,
		REST:       restClient,
		Solver:     solverClient,
		Controller: farmCtl,
		Token:      acc.Token,
	})
	go dispatcher.Run(ctx)

	// Hot-reload config.json: channelId menentukan run/stop. Dilacak dari nilai
	// channelId sebelumnya, BUKAN dari status, karena status juga ditulis saat
	// captcha/autogems menjeda farming (itu bukan alasan untuk start ulang).
	lastChannel := strings.TrimSpace(cfg.ChannelID())
	go cfg.Watch(ctx, time.Second, func(ch config.Change) {
		onConfigChange(ctx, cfg, farmCtl, warmSlash, &lastChannel)
	})

	client, err := gateway.New(gateway.Options{
		Token:      acc.Token,
		APIVersion: gwCfg.APIVersion,
		Compress:   gwCfg.Compress,
		OnReady: func(ready *gateway.Ready) {
			st.SetSelf(ready.User)
			st.SetSessionID(ready.SessionID)
			for _, ch := range ready.PrivateChannels {
				st.SetChannel(ch)
			}
			// Farming dimulai otomatis bila channelId sudah diisi, apa pun
			// prefix-nya. Kontrol run/stop lewat channelId (hot-reload), bukan
			// perintah chat.
			if id := strings.TrimSpace(cfg.ChannelID()); id != "" && id != "-" && !cfg.CaptchaActive() {
				go func() {
					// Mode slash butuh cache guild + command agar latency
					// cycle pertama tidak menanggung lookup.
					if strings.TrimSpace(cfg.Prefix()) == "/" {
						warmSlash(ctx)
					}
					logger.Log("LOOP", "farming started in channel", id)
					farmCtl.Start()
				}()
			} else if id == "" || id == "-" {
				logger.Warn("LOOP", "channelId is empty; farming not started (set it in config.json)")
			}
		},
		OnEvent: dispatcher.Enqueue,
		OnFatalClose: func(code int) {
			if code == 4004 {
				logger.Error("TOKEN", "Token is invalid or the session ended.", environment.Detect().Hints().TokenInvalid)
				select {
				case invalidToken <- struct{}{}:
				default:
				}
				return
			}
			logger.Error("DISCORD", fmt.Sprintf("connection closed permanently (code %d)", code))
		},
	})
	if err != nil {
		return err
	}

	err = client.Run(ctx)
	if errors.Is(err, context.Canceled) {
		return nil
	}
	return err
}

// resolveAccount menentukan token yang dipakai.
//
// Bila DISCORD_TOKEN terisi di .env, token itu divalidasi langsung ke Discord
// dan dipakai apa adanya (mode VPS/termux/hosting): LevelDB tidak dibaca dan
// aplikasi Discord tidak perlu terbuka. Bila DISCORD_ACCOUNT_ID diisi juga, ID
// di dalam token HARUS cocok, kalau tidak proses dihentikan.
//
// Bila DISCORD_TOKEN kosong, token diambil otomatis dari sesi aplikasi Discord
// desktop (perilaku lama).
func resolveAccount(ctx context.Context, cfg *config.Config, envStore *env.Store) (token.Account, error) {
	manual := strings.TrimSpace(envStore.Get("DISCORD_TOKEN"))
	accountID := strings.TrimSpace(envStore.Get("DISCORD_ACCOUNT_ID"))
	hints := environment.Detect().Hints()

	if manual != "" {
		acc, err := token.Validate(ctx, token.Options{}, manual)
		if err != nil {
			return token.Account{}, fmt.Errorf("%w\n  %s", err, hints.TokenInvalid)
		}
		if accountID != "" && acc.ID != accountID {
			return token.Account{}, fmt.Errorf(
				"token in .env belongs to account %s, but DISCORD_ACCOUNT_ID=%s; fix one of them",
				acc.ID, accountID)
		}
		return acc, nil
	}

	acc, err := token.Find(ctx, token.Options{AccountID: accountID})
	if err != nil {
		return token.Account{}, pesanToken(err, accountID, hints)
	}
	return acc, nil
}

// tokenSource mengembalikan keterangan sumber token untuk log.
func tokenSource(envStore *env.Store) string {
	if strings.TrimSpace(envStore.Get("DISCORD_TOKEN")) != "" {
		return "from .env"
	}
	return "from Discord session"
}

// pesanToken menerjemahkan error paket token menjadi saran yang bisa
// ditindaklanjuti, tanpa mengubah rantai error aslinya.
func pesanToken(err error, accountID string, hints environment.Hints) error {
	switch {
	case errors.Is(err, token.ErrAccountNotFound):
		return fmt.Errorf("%w\n  DISCORD_ACCOUNT_ID: %s\n  that account is not logged into the Discord app. Log in first, or clear DISCORD_ACCOUNT_ID to use any valid account.", err, accountID)
	case errors.Is(err, token.ErrAccountInvalid):
		return fmt.Errorf("%w\n  account %s exists in storage but its token was rejected by Discord. %s", err, accountID, hints.TokenInvalid)
	case errors.Is(err, token.ErrNotFound):
		return fmt.Errorf("%w\n  %s", err, hints.TokenEmpty)
	default:
		return err
	}
}

// onConfigChange bereaksi terhadap perubahan config.json saat runtime: channelId
// menentukan farming jalan/berhenti. lastChannel adalah nilai channelId yang
// terakhir diproses (di-update di sini).
func onConfigChange(ctx context.Context, cfg *config.Config, ctrl *farm.Controller, warm func(context.Context), lastChannel *string) {
	id := strings.TrimSpace(cfg.ChannelID())
	if id == *lastChannel {
		return
	}
	prev := *lastChannel
	*lastChannel = id

	if id == "" || id == "-" {
		logger.Log("LOOP", "channelId cleared; farming stopped")
		ctrl.Stop()
		return
	}
	if cfg.CaptchaActive() {
		logger.Warn("LOOP", "channelId changed while captcha is active; farming waits for captcha to finish")
		return
	}
	if prev == "" || prev == "-" {
		logger.Log("LOOP", "channelId set; farming started in channel", id)
		if warm != nil && strings.TrimSpace(cfg.Prefix()) == "/" {
			warm(ctx)
		}
		ctrl.Start()
		return
	}
	logger.Log("LOOP", "channelId changed; farming moved to channel", id)
	ctrl.Stop()
	if warm != nil && strings.TrimSpace(cfg.Prefix()) == "/" {
		warm(ctx)
	}
	ctrl.Start()
}

// runTokenStatus menampilkan akun yang tokennya terbaca, tanpa membocorkan
// nilai token itu sendiri.
func runTokenStatus(cfg *config.Config, envStore *env.Store) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	acc, err := resolveAccount(ctx, cfg, envStore)
	if err != nil {
		logger.Error("TOKEN", err)
		os.Exit(1)
	}
	logger.Log("TOKEN", "account", acc.Username, "("+acc.ID+")",
		fmt.Sprintf("token %d chars %s", len(acc.Token), tokenSource(envStore)))
}

// newSlashSender membuat pengirim slash command untuk mode prefix "/".
// guildId channel dicari sekali (di-cache) lalu dipakai untuk lookup command.
// Fungsi warm dipakai untuk mengisi cache lebih awal saat READY.
func newSlashSender(ctx context.Context, cfg *config.Config, restClient *rest.Client, st *state.Store) (farm.SlashSender, func(context.Context)) {
	var mu sync.Mutex
	guildByChannel := make(map[string]string)

	guildFor := func(ctx context.Context, channelID string) string {
		mu.Lock()
		g, ok := guildByChannel[channelID]
		mu.Unlock()
		if ok {
			return g
		}
		ch, err := restClient.Channel(ctx, channelID)
		if err != nil {
			return ""
		}
		mu.Lock()
		guildByChannel[channelID] = ch.GuildID
		mu.Unlock()
		return ch.GuildID
	}

	send := func(ctx context.Context, name string) (time.Duration, error) {
		channelID := cfg.ChannelID()
		guildID := guildFor(ctx, channelID)
		opts := rest.SlashOptions{
			ApplicationID: rest.OwoApplicationID,
			CommandName:   name,
			GuildID:       guildID,
			ChannelID:     channelID,
			SessionID:     st.SessionID(),
		}
		// Ukur latency hanya untuk POST interaksi (lookup sudah di-cache).
		start := time.Now()
		err := restClient.SendSlashCommand(ctx, opts)
		return time.Since(start), err
	}

	warm := func(ctx context.Context) {
		channelID := cfg.ChannelID()
		if channelID == "" || channelID == "-" {
			return
		}
		guildID := guildFor(ctx, channelID)
		for _, name := range []string{"hunt", "battle"} {
			_ = restClient.WarmSlashCommand(ctx, guildID, channelID, rest.OwoApplicationID, name)
		}
	}

	return send, warm
}

// newCaptchaStatusChecker membuat fungsi yang memeriksa status captcha akun
// lewat https://owobot.com/api/auth memakai cookie sesi OAuth. Cookie di-cache
// dan diperbarui ulang bila permintaan gagal.
func newCaptchaStatusChecker(cfg *config.Config, solverClient *solver.Client, accToken string) farm.CaptchaChecker {
	owoBase := "https://owobot.com"
	if solverClient != nil {
		owoBase = solverClient.OwoBase()
	}

	// Cookie OAuth OwO dibuat dari token akun yang sama.
	opts := owocookie.Options{Token: accToken, OwoBase: owoBase}

	var mu sync.Mutex
	var cached string

	getCookie := func(ctx context.Context) (string, error) {
		mu.Lock()
		c := cached
		mu.Unlock()
		if c != "" {
			return c, nil
		}
		fresh, err := owocookie.Get(ctx, opts)
		if err != nil {
			return "", err
		}
		mu.Lock()
		cached = fresh
		mu.Unlock()
		return fresh, nil
	}

	clearCookie := func() {
		mu.Lock()
		cached = ""
		mu.Unlock()
	}

	return func(ctx context.Context) (bool, error) {
		cookieValue, err := getCookie(ctx)
		if err != nil {
			return false, err
		}
		active, err := owocookie.CaptchaStatus(ctx, opts, cookieValue)
		if err != nil {
			clearCookie()
		}
		return active, err
	}
}

func buildSolver(cfg *config.Config, envStore *env.Store) *solver.Client {
	s := cfg.SolverSettings()
	apiKey := strings.TrimSpace(envStore.Get("CAPTOPIA_API_KEY"))
	if apiKey == "" {
		logger.Warn("CAPTCHA", "CAPTOPIA_API_KEY is empty in .env; captcha will wait for manual solving")
		return nil
	}
	return solver.New(solver.Options{
		APIBase:      s.APIBase,
		APIKey:       apiKey,
		SiteKey:      s.SiteKey,
		URL:          s.URL,
		Host:         s.Host,
		Timeout:      time.Duration(s.TimeoutMs) * time.Millisecond,
		ImageTimeout: time.Duration(s.ImageTimeoutMs) * time.Millisecond,
		ImageSamples: s.ImageSamples,
		QueueMax:     s.QueueMax,
		Debug:        s.Debug,
	})
}
