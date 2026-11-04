package events

import (
	"context"
	"fmt"
	"math/rand/v2"
	"strings"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

const owoBotID = "408785106942164992"

// sleepCtx menunggu d atau sampai ctx dibatalkan. Mengembalikan false bila
// ctx dibatalkan.
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

// jitterDur menghasilkan durasi acak antara min dan max (presisi milidetik).
func jitterDur(min, max time.Duration) time.Duration {
	if max < min {
		max = min
	}
	span := int64(max-min) / int64(time.Millisecond)
	if span <= 0 {
		return min
	}
	return min + time.Duration(rand.Int64N(span+1))*time.Millisecond
}

func cleanZeroWidth(s string) string {
	replacer := strings.NewReplacer("\u200b", "", "\u200c", "", "\u200d", "")
	return replacer.Replace(s)
}

func containsAny(text string, needles ...string) bool {
	for _, n := range needles {
		if strings.Contains(text, n) {
			return true
		}
	}
	return false
}

// fullText menggabungkan content + teks embed, seperti fullText() di
// events/autogems.js.
func fullText(m rest.Message) string {
	parts := []string{m.Content}
	for _, e := range m.Embeds {
		if e.Author != nil && e.Author.Name != "" {
			parts = append(parts, e.Author.Name)
		}
		if e.Title != "" {
			parts = append(parts, e.Title)
		}
		if e.Description != "" {
			parts = append(parts, e.Description)
		}
		if e.Footer != nil && e.Footer.Text != "" {
			parts = append(parts, e.Footer.Text)
		}
		for _, f := range e.Fields {
			parts = append(parts, f.Name+" "+f.Value)
		}
	}
	return strings.Join(parts, "\n")
}

// attachmentURL memilih attachment gambar (atau yang pertama) dan
// mengembalikan URL yang bisa diunduh.
func attachmentURL(m rest.Message) string {
	if len(m.Attachments) == 0 {
		return ""
	}

	chosen := m.Attachments[0]
	for _, a := range m.Attachments {
		if strings.HasPrefix(a.ContentType, "image/") {
			chosen = a
			break
		}
	}

	if chosen.URL != "" {
		return chosen.URL
	}
	return chosen.ProxyURL
}

func validChannelID(id string) bool {
	return id != "" && id != "-"
}

func (d *Dispatcher) channelLabel(id string) string {
	if ch, ok := d.state.Channel(id); ok && ch.Name != "" {
		return "#" + ch.Name
	}
	return id
}

// safeGo menjalankan goroutine dengan pemulihan panic agar satu error tidak
// menjatuhkan seluruh proses (meniru handler EXCEPTION di Node).
func safeGo(name string, fn func()) {
	go func() {
		defer func() {
			if r := recover(); r != nil {
				logger.Error("EXCEPTION", name+":", fmt.Sprint(r))
			}
		}()
		fn()
	}()
}
