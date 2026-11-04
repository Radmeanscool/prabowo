package rest

import (
	"bytes"
	"encoding/json"
	"strings"
)

// Nonce adalah nilai nonce pesan. Discord bisa mengirimnya sebagai string
// atau angka, jadi tipe ini menerima keduanya agar dekode pesan tidak gagal.
type Nonce string

// UnmarshalJSON menerima string, angka, atau null.
func (n *Nonce) UnmarshalJSON(data []byte) error {
	data = bytes.TrimSpace(data)
	if len(data) == 0 || string(data) == "null" {
		*n = ""
		return nil
	}
	if data[0] == '"' {
		var s string
		if err := json.Unmarshal(data, &s); err != nil {
			return err
		}
		*n = Nonce(s)
		return nil
	}
	*n = Nonce(string(data))
	return nil
}

// String mengembalikan nonce sebagai string tanpa tanda kutip.
func (n Nonce) String() string { return string(n) }

// User adalah subset objek user Discord.
type User struct {
	ID            string `json:"id"`
	Username      string `json:"username"`
	Discriminator string `json:"discriminator"`
	GlobalName    string `json:"global_name"`
	Avatar        string `json:"avatar"`
	Bot           bool   `json:"bot"`
}

// DisplayName mengembalikan nama tampilan terbaik yang tersedia.
func (u User) DisplayName() string {
	if u.GlobalName != "" {
		return u.GlobalName
	}
	return u.Username
}

// Tag mengembalikan username#discriminator lama, atau username saja untuk
// akun dengan sistem username baru (discriminator "0").
func (u User) Tag() string {
	if u.Discriminator == "" || u.Discriminator == "0" {
		return u.Username
	}
	return u.Username + "#" + u.Discriminator
}

// Member adalah subset objek guild member pada sebuah pesan.
type Member struct {
	Nick     string   `json:"nick"`
	Roles    []string `json:"roles"`
	JoinedAt string   `json:"joined_at"`
}

// Attachment adalah subset lampiran pesan.
type Attachment struct {
	ID          string `json:"id"`
	Filename    string `json:"filename"`
	URL         string `json:"url"`
	ProxyURL    string `json:"proxy_url"`
	ContentType string `json:"content_type"`
	Size        int    `json:"size"`
	Width       *int   `json:"width"`
	Height      *int   `json:"height"`
}

// EmbedAuthor adalah subset author pada embed.
type EmbedAuthor struct {
	Name    string `json:"name"`
	URL     string `json:"url"`
	IconURL string `json:"icon_url"`
}

// EmbedField adalah satu field pada embed.
type EmbedField struct {
	Name   string `json:"name"`
	Value  string `json:"value"`
	Inline bool   `json:"inline"`
}

// EmbedImage adalah gambar pada embed.
type EmbedImage struct {
	URL      string `json:"url"`
	ProxyURL string `json:"proxy_url"`
	Width    int    `json:"width"`
	Height   int    `json:"height"`
}

// EmbedFooter adalah footer pada embed.
type EmbedFooter struct {
	Text    string `json:"text"`
	IconURL string `json:"icon_url"`
}

// Embed adalah subset embed pesan.
type Embed struct {
	Title       string       `json:"title"`
	Description string       `json:"description"`
	URL         string       `json:"url"`
	Color       int          `json:"color"`
	Author      *EmbedAuthor `json:"author"`
	Fields      []EmbedField `json:"fields"`
	Image       *EmbedImage  `json:"image"`
	Footer      *EmbedFooter `json:"footer"`
}

// Channel adalah subset objek channel Discord.
type Channel struct {
	ID         string `json:"id"`
	Type       int    `json:"type"`
	Name       string `json:"name"`
	GuildID    string `json:"guild_id"`
	ParentID   string `json:"parent_id"`
	Recipients []User `json:"recipients"`
}

// IsDM melaporkan apakah channel adalah DM (type 1) atau group DM (type 3).
func (c Channel) IsDM() bool {
	return c.Type == 1 || c.Type == 3
}

// Component adalah satu komponen interaktif (mis. button).
type Component struct {
	Type     int    `json:"type"`
	CustomID string `json:"custom_id"`
	Label    string `json:"label"`
	Style    int    `json:"style"`
	Disabled bool   `json:"disabled"`
}

// ComponentRow adalah baris komponen (action row).
type ComponentRow struct {
	Type       int         `json:"type"`
	Components []Component `json:"components"`
}

// Message adalah subset objek pesan Discord yang dipakai bot.
type Message struct {
	ID            string         `json:"id"`
	ChannelID     string         `json:"channel_id"`
	GuildID       string         `json:"guild_id"`
	ApplicationID string         `json:"application_id"`
	Nonce         Nonce          `json:"nonce"`
	Content       string         `json:"content"`
	Author        User           `json:"author"`
	Member        *Member        `json:"member"`
	Attachments   []Attachment   `json:"attachments"`
	Embeds        []Embed        `json:"embeds"`
	Components    []ComponentRow `json:"components"`
	Timestamp     string         `json:"timestamp"`
	Type          int            `json:"type"`
	Flags         int            `json:"flags"`
	Mentions      []User         `json:"mentions"`
}

// FindButton mencari button berdasarkan custom_id.
func (m Message) FindButton(customID string) (Component, bool) {
	for _, row := range m.Components {
		for _, c := range row.Components {
			if c.CustomID == customID {
				return c, true
			}
		}
	}
	return Component{}, false
}

// ContentTrimmed mengembalikan isi pesan tanpa spasi di ujung.
func (m Message) ContentTrimmed() string {
	return strings.TrimSpace(m.Content)
}
