package events

import (
	"context"
	"strings"
	"sync"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

type owoHandler struct {
	d *Dispatcher

	mu      sync.Mutex
	waiting bool
	found   chan struct{}
}

func (h *owoHandler) onMessage(ctx context.Context, m rest.Message) {
	self, ok := h.d.state.Self()
	if !ok {
		return
	}

	if m.Author.ID == self.ID {
		content := strings.ToLower(strings.TrimSpace(m.Content))
		// Farming dikendalikan oleh channelId di config.json, bukan perintah
		// chat. Pesan "owoh" milik sendiri dipakai untuk memulai deteksi OwO
		// offline (collector).
		if m.ChannelID == h.d.cfg.ChannelID() &&
			content == strings.ToLower(h.d.cfg.Prefix()+"h") {
			h.startCollector(ctx)
		}
		return
	}

	// Deteksi OwO offline: hanya pesan OwO di kanal farming yang menandakan OwO
	// hidup. Pesan dari bot/user lain (mis. bot reaksi) tidak boleh membatalkan
	// collector, kalau tidak offline tidak pernah terdeteksi (lihat events/owo.js
	// Node: collector hanya "found" bila author.id == id OwO).
	if m.Author.ID == owoBotID && m.ChannelID == h.d.cfg.ChannelID() {
		h.markFound()
	}

	// Duel bisa datang dari OwO (Author.ID == owoBotID) maupun lewat webhook
	// yang Author.ID-nya berbeda, jadi pengirim tidak boleh menjadi syarat.
	// Penentu duel adalah isi embed: tombol battle_accept yang masih aktif.
	if m.Content != "<@"+self.ID+">" || len(m.Embeds) == 0 {
		return
	}

	embed := m.Embeds[0]
	if embed.Author == nil || !strings.Contains(embed.Author.Name, "challenges you to a duel!") {
		return
	}
	if btn, ok := m.FindButton("battle_accept"); ok && !btn.Disabled {
		safeGo("owo duel", func() { h.clickBattle(ctx, m) })
	}
}

func (h *owoHandler) startCollector(ctx context.Context) {
	h.mu.Lock()
	if h.waiting {
		h.mu.Unlock()
		return
	}
	h.waiting = true
	found := make(chan struct{})
	h.found = found
	h.mu.Unlock()

	safeGo("owo collector", func() {
		timer := time.NewTimer(h.d.timings.CollectorWindow)
		defer timer.Stop()
		defer func() {
			h.mu.Lock()
			h.waiting = false
			h.found = nil
			h.mu.Unlock()
		}()

		select {
		case <-found:
		case <-ctx.Done():
		case <-timer.C:
			logger.Warn("DOWN", "owo offline, farming paused, retry in 5m")
			h.d.stopLoop()
			h.d.scheduleRestart(h.d.timings.OwoOfflineDelay)
		}
	})
}

func (h *owoHandler) markFound() {
	h.mu.Lock()
	defer h.mu.Unlock()
	if h.found != nil {
		close(h.found)
		h.found = nil
	}
}

func (h *owoHandler) clickBattle(ctx context.Context, m rest.Message) {
	if !sleepCtx(ctx, h.d.timings.DuelClickDelay) {
		return
	}
	if h.d.rest == nil {
		logger.Warn("DISCORD", "rest client unavailable to click duel")
		return
	}

	// application_id harus milik pemilik komponen tombol, yaitu OwO. Jangan
	// memakai m.Author.ID: pada pesan webhook nilainya akun lain dan Discord
	// menolak interaksinya.
	appID := m.ApplicationID
	if appID == "" {
		appID = rest.OwoApplicationID
	}

	err := h.d.rest.ClickButton(ctx, rest.ButtonClick{
		GuildID:       m.GuildID,
		ChannelID:     m.ChannelID,
		MessageID:     m.ID,
		ApplicationID: appID,
		SessionID:     h.d.state.SessionID(),
		CustomID:      "battle_accept",
		MessageFlags:  m.Flags,
	})
	if err != nil {
		logger.Warn("DISCORD", "battle_accept click failed:", err)
		return
	}
	logger.Log("INFO", "duel accepted (battle_accept)")
}
