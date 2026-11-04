package rest

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"sync/atomic"
	"time"
)

const (
	discordEpochMs = 1420070400000

	// InteractionResponseTimeout adalah batas menunggu balasan (MESSAGE_CREATE
	// dengan nonce yang sama) setelah mengirim interaksi slash. Meniru
	// createPromiseInteraction di discord.js-selfbot-v13 (5000 ms).
	InteractionResponseTimeout = 5 * time.Second
)

// OwoApplicationID adalah application id bot OwO (sama dengan bot user id).
const OwoApplicationID = "408785106942164992"

// ErrInteractionFailed menandakan interaksi dikirim (HTTP 2xx) tetapi tidak ada
// pesan balasan dengan nonce yang sama dalam batas waktu. Ini yang menandakan
// bot penerima (OwO) tidak merespons, bukan error HTTP.
var ErrInteractionFailed = errors.New("rest: interaction failed (bot did not respond)")

type appCommandOption struct {
	Name    string             `json:"name"`
	Type    int                `json:"type"`
	Options []appCommandOption `json:"options"`
}

type appCommand struct {
	ID            string             `json:"id"`
	Name          string             `json:"name"`
	NameDefault   string             `json:"name_default"`
	Description   string             `json:"description"`
	ApplicationID string             `json:"application_id"`
	GuildID       string             `json:"guild_id"`
	Version       string             `json:"version"`
	Type          int                `json:"type"`
	Options       []appCommandOption `json:"options"`
}

type appCommandIndex struct {
	ApplicationCommands []appCommand `json:"application_commands"`
}

// SlashOptions mengatur pengiriman slash command.
type SlashOptions struct {
	ApplicationID string
	CommandName   string
	GuildID       string
	ChannelID     string
	SessionID     string
	// ResponseTimeout adalah batas menunggu balasan ber-nonce. 0 = default
	// InteractionResponseTimeout.
	ResponseTimeout time.Duration
}

// SendSlashCommand mencari command lewat application-command-index lalu
// mengirim interaksi APPLICATION_COMMAND (type 2), meniru
// TextChannel#sendSlash di discord.js-selfbot-v13.
//
// Setelah POST diterima Discord (2xx), metode ini menunggu pesan balasan
// dengan nonce yang sama sampai InteractionResponseTimeout. Bila tidak ada
// balasan, ErrInteractionFailed dikembalikan: itu menandakan bot penerima
// (mis. OwO) sedang tidak merespons, walau HTTP sukses.
func (c *Client) SendSlashCommand(ctx context.Context, opts SlashOptions) error {
	name := strings.TrimSpace(opts.CommandName)
	if opts.ChannelID == "" || name == "" {
		return errors.New("rest: empty channel/slash command")
	}
	if opts.SessionID == "" {
		return errors.New("rest: gateway session id not ready")
	}

	wantApp := opts.ApplicationID
	if wantApp == "" {
		wantApp = OwoApplicationID
	}

	cmd, err := c.resolveSlashCommand(ctx, opts.ChannelID, opts.GuildID, wantApp, name)
	if err != nil {
		return err
	}

	nonce := newNonce()

	// application_id diambil dari command-index agar selalu cocok, apa pun
	// nilainya (application id bisa berbeda dari bot user id).
	payload := map[string]any{
		"type":           2,
		"application_id": cmd.ApplicationID,
		"channel_id":     opts.ChannelID,
		"session_id":     opts.SessionID,
		"nonce":          nonce,
		"data": map[string]any{
			"version": cmd.Version,
			"id":      cmd.ID,
			"name":    cmd.Name,
			"type":    cmd.Type,
			"options": []any{},
		},
	}
	if opts.GuildID != "" {
		payload["guild_id"] = opts.GuildID
	}
	if cmd.GuildID != "" {
		payload["data"].(map[string]any)["guild_id"] = opts.GuildID
	}

	// Daftarkan penantian SEBELUM POST supaya balasan yang tiba cepat tidak
	// terlewat.
	waiter := c.registerNonce(nonce)
	defer c.unregisterNonce(nonce)

	if err := c.do(ctx, http.MethodPost, "/interactions", payload, nil); err != nil {
		return err
	}

	timeout := opts.ResponseTimeout
	if timeout <= 0 {
		timeout = InteractionResponseTimeout
	}

	select {
	case <-waiter:
		return nil
	case <-time.After(timeout):
		return ErrInteractionFailed
	case <-ctx.Done():
		return ctx.Err()
	}
}

// NotifyMessage dipanggil untuk setiap MESSAGE_CREATE. Bila pesan membawa
// nonce yang sedang dinanti, penantiannya dibangunkan.
func (c *Client) NotifyMessage(m Message) {
	nonce := strings.TrimSpace(m.Nonce.String())
	if nonce == "" {
		return
	}
	c.nonceMu.Lock()
	waiter, ok := c.nonceWaiters[nonce]
	if ok {
		delete(c.nonceWaiters, nonce)
	}
	c.nonceMu.Unlock()
	if ok {
		close(waiter)
	}
}

func (c *Client) registerNonce(nonce string) <-chan struct{} {
	ch := make(chan struct{})
	c.nonceMu.Lock()
	c.nonceWaiters[nonce] = ch
	c.nonceMu.Unlock()
	return ch
}

func (c *Client) unregisterNonce(nonce string) {
	c.nonceMu.Lock()
	delete(c.nonceWaiters, nonce)
	c.nonceMu.Unlock()
}

// resolveSlashCommand mengembalikan command dari cache, atau lookup lalu
// menyimpannya. Cache memangkas GET command-index pada tiap kirim slash.
func (c *Client) resolveSlashCommand(ctx context.Context, channelID, guildID, wantApp, name string) (*appCommand, error) {
	key := guildID + "|" + channelID + "|" + wantApp + "|" + name

	c.slashMu.Lock()
	if cmd, ok := c.slashCache[key]; ok {
		c.slashMu.Unlock()
		return &cmd, nil
	}
	c.slashMu.Unlock()

	cmd, err := c.findApplicationCommand(ctx, channelID, guildID, wantApp, name)
	if err != nil {
		return nil, err
	}

	c.slashMu.Lock()
	c.slashCache[key] = *cmd
	c.slashMu.Unlock()
	return cmd, nil
}

// WarmSlashCommand mengisi cache command lebih awal agar kiriman pertama
// tidak menanggung biaya lookup. Tidak mengirim interaksi apa pun.
func (c *Client) WarmSlashCommand(ctx context.Context, guildID, channelID, applicationID, name string) error {
	if channelID == "" || name == "" {
		return nil
	}
	if applicationID == "" {
		applicationID = OwoApplicationID
	}
	_, err := c.resolveSlashCommand(ctx, channelID, guildID, applicationID, name)
	return err
}

// InvalidateSlashCache menghapus cache command (mis. setelah versi command
// berubah dan interaksi ditolak).
func (c *Client) InvalidateSlashCache() {
	c.slashMu.Lock()
	c.slashCache = make(map[string]appCommand)
	c.slashMu.Unlock()
}

// findApplicationCommand mencari command bernama name pada command-index.
// Utamakan command dari aplikasi wantApp; bila tidak ada, pakai command mana
// pun yang namanya cocok (application_id tetap diambil dari hasil index).
func (c *Client) findApplicationCommand(ctx context.Context, channelID, guildID, wantApp, name string) (*appCommand, error) {
	paths := []string{"/channels/" + channelID + "/application-command-index"}
	if guildID != "" {
		paths = append([]string{"/guilds/" + guildID + "/application-command-index"}, paths...)
	}
	paths = append(paths, "/users/@me/application-command-index")

	var fallback *appCommand
	for _, p := range paths {
		var idx appCommandIndex
		if err := c.do(ctx, http.MethodGet, p, nil, &idx); err != nil {
			continue
		}
		for _, cmd := range idx.ApplicationCommands {
			if cmd.Name != name && cmd.NameDefault != name {
				continue
			}
			if cmd.ApplicationID == wantApp {
				found := cmd
				return &found, nil
			}
			if fallback == nil {
				found := cmd
				fallback = &found
			}
		}
	}
	if fallback != nil {
		return fallback, nil
	}
	return nil, fmt.Errorf("rest: slash command %q not found in command-index", name)
}

// ButtonClick berisi data untuk menekan tombol komponen pesan.
type ButtonClick struct {
	GuildID       string
	ChannelID     string
	MessageID     string
	ApplicationID string
	SessionID     string
	CustomID      string
	MessageFlags  int
}

// ClickButton mengirim interaksi MESSAGE_COMPONENT (tombol) seperti
// Message#clickButton di discord.js-selfbot-v13.
func (c *Client) ClickButton(ctx context.Context, b ButtonClick) error {
	if b.ChannelID == "" || b.MessageID == "" || b.CustomID == "" {
		return errors.New("rest: empty button channel/message/custom id")
	}
	if b.SessionID == "" {
		return errors.New("rest: gateway session id not ready")
	}

	payload := map[string]any{
		"type":          3,
		"nonce":         newNonce(),
		"channel_id":    b.ChannelID,
		"message_id":    b.MessageID,
		"session_id":    b.SessionID,
		"message_flags": b.MessageFlags,
		"data": map[string]any{
			"component_type": 2,
			"custom_id":      b.CustomID,
		},
	}
	if b.GuildID != "" {
		payload["guild_id"] = b.GuildID
	}
	if b.ApplicationID != "" {
		payload["application_id"] = b.ApplicationID
	}

	return c.do(ctx, http.MethodPost, "/interactions", payload, nil)
}

// nonceSeq memberi increment per nonce agar dua panggilan pada milidetik yang
// sama tidak menghasilkan nonce kembar (meniru INCREMENT di SnowflakeUtil).
var nonceSeq atomic.Uint64

func newNonce() string {
	ms := time.Now().UnixMilli() - discordEpochMs
	if ms < 0 {
		ms = 0
	}
	inc := nonceSeq.Add(1) & 0xFFF
	return strconv.FormatUint((uint64(ms)<<22)|(1<<17)|inc, 10)
}
