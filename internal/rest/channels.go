package rest

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"strings"
)

// Channel mengambil metadata channel berdasarkan ID.
func (c *Client) Channel(ctx context.Context, channelID string) (*Channel, error) {
	path, err := channelPath(channelID, "")
	if err != nil {
		return nil, err
	}
	var ch Channel
	if err := c.do(ctx, http.MethodGet, path, nil, &ch); err != nil {
		return nil, err
	}
	return &ch, nil
}

// SendMessage mengirim pesan teks ke channel.
func (c *Client) SendMessage(ctx context.Context, channelID, content string) (*Message, error) {
	if strings.TrimSpace(content) == "" {
		return nil, errors.New("rest: empty message content")
	}
	path, err := channelPath(channelID, "/messages")
	if err != nil {
		return nil, err
	}

	var msg Message
	body := map[string]any{"content": content}
	if err := c.do(ctx, http.MethodPost, path, body, &msg); err != nil {
		return nil, err
	}
	return &msg, nil
}

// SendTyping memicu indikator "sedang mengetik" di channel.
func (c *Client) SendTyping(ctx context.Context, channelID string) error {
	path, err := channelPath(channelID, "/typing")
	if err != nil {
		return err
	}
	return c.do(ctx, http.MethodPost, path, nil, nil)
}

// CreateDM membuka (atau mengambil) channel DM dengan seorang user.
func (c *Client) CreateDM(ctx context.Context, recipientID string) (*Channel, error) {
	id := strings.TrimSpace(recipientID)
	if id == "" {
		return nil, errors.New("rest: empty recipient id")
	}

	var ch Channel
	body := map[string]any{"recipient_id": id}
	if err := c.do(ctx, http.MethodPost, "/users/@me/channels", body, &ch); err != nil {
		return nil, err
	}
	return &ch, nil
}

// SendDM mengirim pesan langsung ke seorang user (buat DM lalu kirim).
func (c *Client) SendDM(ctx context.Context, userID, content string) (*Message, error) {
	ch, err := c.CreateDM(ctx, userID)
	if err != nil {
		return nil, err
	}
	if ch.ID == "" {
		return nil, errors.New("rest: empty DM channel")
	}
	msg, err := c.SendMessage(ctx, ch.ID, content)
	if err != nil {
		return nil, fmt.Errorf("rest: send DM: %w", err)
	}
	return msg, nil
}

// MessageURL membentuk link web untuk sebuah pesan (dipakai untuk log).
func MessageURL(guildID, channelID, messageID string) string {
	if guildID == "" {
		return ""
	}
	return "https://discord.com/channels/" +
		url.PathEscape(guildID) + "/" +
		url.PathEscape(channelID) + "/" +
		url.PathEscape(messageID)
}
