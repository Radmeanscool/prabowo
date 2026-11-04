package events

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/gateway"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

type guildCreate struct {
	ID       string         `json:"id"`
	Name     string         `json:"name"`
	Channels []rest.Channel `json:"channels"`
}

func (d *Dispatcher) handleGuildCreate(_ context.Context, e gateway.Event) error {
	var g guildCreate
	if err := json.Unmarshal(e.Data, &g); err != nil {
		return fmt.Errorf("decode GUILD_CREATE: %w", err)
	}
	if g.ID == "" {
		return nil
	}
	d.state.SetGuild(g.ID, g.Name)
	for _, ch := range g.Channels {
		ch.GuildID = g.ID
		d.state.SetChannel(ch)
	}
	return nil
}

func (d *Dispatcher) handleChannelUpsert(_ context.Context, e gateway.Event) error {
	var ch rest.Channel
	if err := json.Unmarshal(e.Data, &ch); err != nil {
		return fmt.Errorf("decode %s: %w", e.Type, err)
	}
	d.state.SetChannel(ch)
	return nil
}

func (d *Dispatcher) handleMessageCreate(ctx context.Context, e gateway.Event) error {
	var m rest.Message
	if err := json.Unmarshal(e.Data, &m); err != nil {
		return fmt.Errorf("decode MESSAGE_CREATE: %w", err)
	}

	display := m.Author.DisplayName()
	if m.Member != nil && m.Member.Nick != "" {
		display = m.Member.Nick
	}
	d.state.SetMemberDisplay(m.GuildID, m.Author.ID, display)

	// Bangunkan penantian balasan slash command (nonce yang sama). Ini yang
	// membuat SendSlashCommand tahu OwO merespons, bukan HTTP 2xx semata.
	if d.rest != nil {
		d.rest.NotifyMessage(m)
	}

	d.owo.onMessage(ctx, m)
	d.gems.onMessage(ctx, m)
	d.captcha.onMessage(ctx, m)
	return nil
}
