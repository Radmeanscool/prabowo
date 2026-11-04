package gateway

import (
	"context"
	"encoding/json"
	"fmt"
)

const subscriptionChunk = 80

type guildSubscription struct {
	Channels          map[string]any `json:"channels"`
	Members           []string       `json:"members"`
	ThreadMemberLists []string       `json:"thread_member_lists"`
	Typing            bool           `json:"typing"`
	Threads           bool           `json:"threads"`
	Activities        bool           `json:"activities"`
	MemberUpdates     bool           `json:"member_updates"`
}

// subscribeGuilds mengirim op 37 (GUILD_SUBSCRIPTIONS_BULK) seperti
// discord.js-selfbot-v13 agar event guild (termasuk MESSAGE_CREATE) mengalir.
func (c *Client) subscribeGuilds(ctx context.Context, guilds []GuildInfo) error {
	if len(guilds) == 0 {
		return nil
	}

	for start := 0; start < len(guilds); start += subscriptionChunk {
		end := start + subscriptionChunk
		if end > len(guilds) {
			end = len(guilds)
		}

		subs := make(map[string]guildSubscription, end-start)
		for _, g := range guilds[start:end] {
			if g.ID == "" {
				continue
			}
			subs[g.ID] = guildSubscription{
				Channels:          map[string]any{},
				Members:           []string{},
				ThreadMemberLists: []string{},
				Typing:            true,
				Threads:           true,
				Activities:        true,
				MemberUpdates:     true,
			}
		}
		if len(subs) == 0 {
			continue
		}

		body := map[string]any{"subscriptions": subs}
		raw, err := json.Marshal(body)
		if err != nil {
			return fmt.Errorf("encode guild subscriptions: %w", err)
		}
		if err := c.sendPayload(ctx, Payload{Op: OpGuildSubscriptionsBulk, D: raw}); err != nil {
			return err
		}
	}

	return nil
}
