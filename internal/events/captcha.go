package events

import (
	"context"
	"errors"
	"fmt"
	"strings"
	"sync/atomic"
	"time"

	owocookie "github.com/Pangleran/discordgo-owoselfbot-v1/internal/cookie"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/solver"
)

type captchaHandler struct {
	d       *Dispatcher
	solving atomic.Bool
}

func (h *captchaHandler) onMessage(ctx context.Context, m rest.Message) {
	self, ok := h.d.state.Self()
	if !ok {
		return
	}

	if m.ChannelID == h.d.cfg.ChannelID() {
		content := cleanZeroWidth(m.Content)
		isMentioned := strings.Contains(content, "<@"+self.ID+">") ||
			strings.Contains(content, "<@!"+self.ID+">")
		if !isMentioned {
			return
		}

		isCaptcha := containsAny(content,
			"real human", "captcha", "complete this within", "Beep Boop", "letter word")
		if !isCaptcha {
			return
		}

		logger.Warn("CAPTCHA", "https://owobot.com/captcha")
		if err := h.d.cfg.Set("captchaStatus", true); err != nil {
			logger.Error("CONFIG", err)
		}
		h.d.stopLoop()

		switch {
		case len(m.Components) > 0:
			safeGo("captcha website", func() { h.solveWebsite(ctx) })
		case len(m.Attachments) > 0:
			safeGo("captcha image", func() { h.solveImage(ctx, m) })
		default:
			logger.Warn("CAPTCHA", "no button/image, solve manually")
		}
		return
	}

	if m.GuildID != "" {
		return
	}

	if strings.Contains(m.Content, "Thank") {
		if err := h.d.cfg.Set("captchaStatus", false); err != nil {
			logger.Error("CONFIG", err)
		}
		safeGo("captcha thank", func() {
			if sleepCtx(ctx, jitterDur(h.d.timings.ThankDelayMin, h.d.timings.ThankDelayMax)) {
				h.d.startLoop()
			}
		})
		return
	}

	dmText := strings.ToLower(cleanZeroWidth(m.Content))
	looksCaptcha := h.d.cfg.CaptchaActive() || containsAny(dmText,
		"captcha", "real human", "following", "verify", "beep boop", "letter word", "complete this")

	if looksCaptcha && len(m.Attachments) > 0 {
		logger.Warn("CAPTCHA", "image captcha via DM detected, solving...")
		if err := h.d.cfg.Set("captchaStatus", true); err != nil {
			logger.Error("CONFIG", err)
		}
		h.d.stopLoop()
		safeGo("captcha image", func() { h.solveImage(ctx, m) })
	}
}

func (h *captchaHandler) solveWebsite(ctx context.Context) {
	if !h.solving.CompareAndSwap(false, true) {
		return
	}
	defer h.solving.Store(false)

	started := time.Now()

	if h.d.solver == nil {
		logger.Error("CAPTCHA", "solver not ready: CAPTOPIA_API_KEY is not set in .env")
		h.waitManual(ctx, started)
		return
	}
	if err := h.d.solver.Check(); err != nil {
		logger.Error("CAPTCHA", "solver not ready:", err)
		h.waitManual(ctx, started)
		return
	}

	s := h.d.cfg.SolverSettings()
	token, err := h.d.solver.SolveHCaptcha(ctx, s.URL, "")
	if err != nil || token == "" {
		if errors.Is(err, solver.ErrRateLimited) {
			logger.Error("CAPTCHA", "captopia rate limited; check account quota/limit")
		} else {
			logger.Error("CAPTCHA", "solver failed:", err)
		}
		h.waitManual(ctx, started)
		return
	}

	var sessionCookie string
	for i := 1; i <= 3; i++ {
		c, err := owocookie.Get(ctx, owocookie.Options{
			Token:   h.d.token,
			OwoBase: h.d.solver.OwoBase(),
		})
		if err == nil && c != "" {
			sessionCookie = c
			break
		}
		if i < 3 && !sleepCtx(ctx, h.d.timings.VerifyRetryDelay) {
			return
		}
	}

	if sessionCookie == "" {
		logger.Error("CAPTCHA", "verify failed: invalid cookie after 3 retries")
		if err := h.d.cfg.Set("captchaStatus", false); err != nil {
			logger.Error("CONFIG", err)
		}
		return
	}

	status, err := h.d.solver.VerifyHCaptcha(ctx, sessionCookie, token)
	if err != nil {
		logger.Error("CAPTCHA", "error:", err)
		if err := h.d.cfg.Set("captchaStatus", false); err != nil {
			logger.Error("CONFIG", err)
		}
		return
	}

	if status == 200 {
		if err := h.d.cfg.Set("captchaStatus", false); err != nil {
			logger.Error("CONFIG", err)
		}
		if sleepCtx(ctx, h.d.timings.CaptchaResumeDelay) {
			h.d.startLoop()
		}
		return
	}

	logger.Error("CAPTCHA", fmt.Sprintf("verify rejected by owobot (status %d)", status))
	h.waitManual(ctx, started)
}

func (h *captchaHandler) solveImage(ctx context.Context, m rest.Message) {
	if !h.solving.CompareAndSwap(false, true) {
		return
	}
	defer h.solving.Store(false)

	started := time.Now()

	if h.d.solver == nil {
		logger.Error("CAPTCHA", "solver not ready: CAPTOPIA_API_KEY is not set in .env")
		h.waitManual(ctx, started)
		return
	}
	if err := h.d.solver.Check(); err != nil {
		logger.Error("CAPTCHA", "solver not ready:", err)
		h.waitManual(ctx, started)
		return
	}

	url := attachmentURL(m)
	if url == "" {
		logger.Warn("CAPTCHA", "image captcha without a readable attachment")
		h.waitManual(ctx, started)
		return
	}

	logger.Log("CAPTCHA", "image captcha, calling captopia API (imagetotext)...")
	image, err := h.d.solver.FetchImageBase64(ctx, url)
	if err != nil {
		logger.Error("CAPTCHA", "failed to fetch image:", err)
		h.waitManual(ctx, started)
		return
	}

	s := h.d.cfg.SolverSettings()
	text, err := h.d.solver.SolveImageToText(ctx, image, s.ImageSamples)
	if err != nil || text == "" {
		logger.Error("CAPTCHA", "reader failed:", err)
		h.waitManual(ctx, started)
		return
	}

	logger.Log("CAPTCHA", fmt.Sprintf("read %q, sending answer to OwO DM...", text))

	if h.d.rest == nil {
		logger.Error("CAPTCHA", "rest client unavailable to send DM")
		h.waitManual(ctx, started)
		return
	}
	if _, err := h.d.rest.SendDM(ctx, owoBotID, text); err != nil {
		logger.Error("CAPTCHA", "failed to send DM to OwO:", err)
		h.waitManual(ctx, started)
		return
	}

	confirmStart := time.Now()
	for time.Since(confirmStart) < h.d.timings.CaptchaImageConfirm {
		if !sleepCtx(ctx, h.d.timings.CaptchaConfirmPoll) {
			return
		}
		if !h.d.cfg.CaptchaActive() {
			return
		}
	}

	logger.Warn("CAPTCHA", "no confirmation from OwO, resuming farming (fallback)")
	if err := h.d.cfg.Set("captchaStatus", false); err != nil {
		logger.Error("CONFIG", err)
	}
	if sleepCtx(ctx, h.d.timings.CaptchaFallbackWait) {
		h.d.startLoop()
	}
}

func (h *captchaHandler) waitManual(ctx context.Context, started time.Time) {
	window := time.Duration(h.d.cfg.SolverSettings().CaptchaManualWindowMs) * time.Millisecond
	logger.Warn("CAPTCHA", fmt.Sprintf("waiting for manual solve (max %d min)", int(window.Minutes())))

	for time.Since(started) < window {
		if !h.d.cfg.CaptchaActive() {
			return
		}
		if !sleepCtx(ctx, h.d.timings.CaptchaPollEvery) {
			return
		}
	}

	if h.d.cfg.CaptchaActive() {
		if err := h.d.cfg.Set("captchaStatus", false); err != nil {
			logger.Error("CONFIG", err)
		}
		if err := h.d.cfg.Set("channelId", "-"); err != nil {
			logger.Error("CONFIG", err)
		}
		logger.Warn("CAPTCHA", "unsolved; farming stopped")
	}
}
