package events

import (
	"context"
	"regexp"
	"strings"
	"sync"
	"sync/atomic"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

// gemShapes mengelompokkan gem per bentuk, urut dari tier TERENDAH ke
// TERTINGGI. Ketiga bentuk ini dipasangkan bersama dalam satu "use".
//
//	diamond : 051..057  (c,u,r,e,m,l,f)
//	bulat   : 065..071  (c,u,r,e,m,l,f)
//	hati    : 072..078  (c,u,r,e,m,l,f)
//
// Tier ditentukan awalan emoji: c=common, u=uncommon, r=rare, e=epic,
// m=mythic, l=legend, f=fabled. Bentuk ditentukan akhiran: gem1=diamond,
// gem3=bulat, gem4=hati.
//
// Star (079..084) sengaja dilewatkan: itu gem event dan dipasang manual,
// bukan bagian dari set harian.
var gemShapes = [][]string{
	{"051", "052", "053", "054", "055", "056", "057"},
	{"065", "066", "067", "068", "069", "070", "071"},
	{"072", "073", "074", "075", "076", "077", "078"},
}

// pickGems memilih satu gem per bentuk: tier TERTINGGI yang benar-benar
// dimiliki, dibaca dari teks inventory. Bila satu bentuk tidak punya gem sama
// sekali, tidak ada set yang bisa dipakai.
//
// Kepemilikan diperiksa dari teks inventory, bukan diasumsikan lengkap. Contoh
// pada akun dengan 051..055 tetapi tanpa 056/057: diamond yang dipilih 055,
// bukan 057. Tanpa pemeriksaan ini perintah use akan ditolak OwO.
//
// Versi lama memakai pasangan tetap (051+065+072, 052+066+073, ...) sehingga
// urutannya dari terkecil dan gagal total saat mis. 065 habis padahal 066 ada.
func pickGems(text string) []string {
	return pickGemsFor(text, allSlots)
}

// pickGemsFor memilih gem hanya untuk bentuk yang diminta (akhiran slot:
// "1" diamond, "3" bulat, "4" hati). Bentuk di luar daftar tidak ikut, sehingga
// perintah use bisa berisi satu, dua, atau tiga gem sesuai yang kosong.
func pickGemsFor(text string, slots []string) []string {
	selected := make([]string, 0, len(slots))
	for _, slot := range slots {
		shape, ok := gemShapesBySlot[slot]
		if !ok {
			continue
		}
		picked := ""
		// Iterasi dari tingkat tertinggi agar angka terbesar menang.
		for i := len(shape) - 1; i >= 0; i-- {
			if strings.Contains(text, "`"+shape[i]+"`") {
				picked = shape[i]
				break
			}
		}
		if picked == "" {
			return nil
		}
		selected = append(selected, picked)
	}
	return selected
}

// gemSlotRe mencocokkan satu entri "empowered by", mis.
//
//	<a:fgem3:510366794792566785>`[199/1000]`
//
// Kelompok 1 = akhiran bentuk (1=diamond, 3=bulat, 4=hati). Slot yang tidak
// muncul pada baris itu berarti gem bentuk tersebut sudah habis.
var gemSlotRe = regexp.MustCompile(`gem([134]):[0-9]+>` + "`" + `\[(\d+)/(\d+)\]`)

// gemShapesBySlot memetakan akhiran emoji ke kumpulan kode gem bentuk itu.
// Dipakai untuk menyusun perintah use hanya bagi bentuk yang kosong.
var gemShapesBySlot = map[string][]string{
	"1": {"051", "052", "053", "054", "055", "056", "057"}, // diamond
	"3": {"065", "066", "067", "068", "069", "070", "071"}, // bulat
	"4": {"072", "073", "074", "075", "076", "077", "078"}, // hati
}

// activeSlots membaca bentuk gem yang sedang aktif dari baris "empowered by".
//
// Mengembalikan map akhiran bentuk -> true. Bila pesan bukan pesan hunt yang
// memuat baris itu, map kosong dikembalikan sehingga pemanggil tahu tidak ada
// informasi (bukan berarti semua kosong).
func activeSlots(text string) map[string]bool {
	slots := map[string]bool{}
	for _, m := range gemSlotRe.FindAllStringSubmatch(text, -1) {
		slots[m[1]] = true
	}
	return slots
}

// isHuntMessage mendeteksi pesan hasil hunt OwO, termasuk hunt yang TIDAK
// memuat satu pun gem (ketiga bentuk habis). Deteksi tidak boleh bergantung
// pada baris "empowered by" saja: saat semua gem habis, OwO bisa menghilangkan
// baris itu sehingga gejalanya justru tidak terbaca. Sebagai cadangan dipakai
// pasangan "you found" + "gained ...xp" yang selalu ada pada hasil hunt.
func isHuntMessage(lower string) bool {
	if strings.Contains(lower, "empowered by") {
		return true
	}
	return strings.Contains(lower, "you found") && strings.Contains(lower, "gained")
}

type gemsHandler struct {
	d    *Dispatcher
	busy atomic.Bool

	// slotMu melindungi pelacakan bentuk gem yang aktif. slotPrev adalah
	// pengamatan terakhir dari baris "empowered by"; pendingSlots berisi bentuk
	// yang baru saja habis dan perlu diisi ulang saat inventory berikutnya tiba.
	slotMu       sync.Mutex
	slotKnown    bool
	slotPrev     map[string]bool
	pendingSlots []string
	retryPending bool
}

func (h *gemsHandler) onMessage(ctx context.Context, m rest.Message) {
	if !h.d.cfg.StatusGems() || !h.d.cfg.Status() {
		return
	}
	if m.Author.ID != owoBotID {
		return
	}
	if m.ChannelID != h.d.cfg.ChannelID() {
		return
	}

	text := fullText(m)
	if text == "" {
		return
	}

	name := h.displayName(m.GuildID)
	if name == "" || !strings.Contains(text, name) {
		return
	}

	lower := strings.ToLower(text)
	if strings.Contains(lower, "spent") {
		safeGo("autogems spent", func() { h.handleSpent(ctx, m.ChannelID) })
		return
	}
	if strings.Contains(lower, "inventory") {
		safeGo("autogems inventory", func() { h.handleInventory(ctx, m.ChannelID, text) })
		return
	}

	// Pesan hunt memuat baris "empowered by" yang menyebut bentuk gem mana yang
	// masih aktif. Bentuk yang tidak muncul berarti sudah habis, dan hanya
	// bentuk itu yang boleh di-use ulang: mengirim gem yang masih aktif ditolak
	// OwO ("you already have an active ... gem").
	//
	// Pesan hunt tanpa satu pun gem tetap harus diolah (map kosong): itu berarti
	// ketiga bentuk habis. Karena itu pemicunya dipisah ke isHuntMessage.
	if isHuntMessage(lower) {
		slots := activeSlots(text)
		safeGo("autogems slot", func() { h.handleDeadSlots(ctx, m.ChannelID, slots) })
	}
}

func (h *gemsHandler) displayName(guildID string) string {
	self, ok := h.d.state.Self()
	if !ok {
		return ""
	}
	if name, ok := h.d.state.MemberDisplay(guildID, self.ID); ok && name != "" {
		return name
	}
	return self.Username
}

// allSlots adalah seluruh bentuk gem yang dipasangkan bersama.
var allSlots = []string{"1", "3", "4"}

// deadSlots mengembalikan bentuk yang aktif pada pengamatan sebelumnya tetapi
// hilang pada pengamatan sekarang. Ini menandakan gem bentuk tersebut habis.
//
// Dibandingkan dengan pengamatan sebelumnya, bukan dengan "ketiganya", supaya
// bentuk yang memang tidak pernah dipasang tidak dianggap mati.
func (h *gemsHandler) deadSlots(now map[string]bool) []string {
	h.slotMu.Lock()
	defer h.slotMu.Unlock()

	if !h.slotKnown {
		// Pengamatan pertama: bentuk yang SUDAH tidak ada ikut dianggap perlu
		// diisi. Tanpa ini, gem yang mati sebelum bot start (mis. bot baru
		// dijalankan setelah diamond habis) tidak akan pernah terdeteksi,
		// karena tidak ada "perubahan" yang bisa dibandingkan.
		h.slotKnown = true
		h.slotPrev = now
		var missing []string
		for _, slot := range allSlots {
			if !now[slot] {
				missing = append(missing, slot)
			}
		}
		return missing
	}

	var dead []string
	for _, slot := range allSlots {
		if h.slotPrev[slot] && !now[slot] {
			dead = append(dead, slot)
		}
	}
	h.slotPrev = now
	return dead
}

// mergeSlots menggabungkan slot baru ke daftar yang sudah ada tanpa duplikat,
// mempertahankan urutan asli.
func mergeSlots(base, extra []string) []string {
	if len(extra) == 0 {
		return base
	}
	for _, s := range extra {
		found := false
		for _, b := range base {
			if b == s {
				found = true
				break
			}
		}
		if !found {
			base = append(base, s)
		}
	}
	return base
}

// handleDeadSlots mengirim use hanya untuk bentuk yang baru saja habis.
//
// Gem yang dipilih adalah tier tertinggi yang dimiliki, dibaca ulang lewat
// inventory agar tidak memakai gem yang sudah ludes. Karena itu jalur ini
// meminta inventory lebih dulu, lalu handleInventory yang menentukan kode gem.
func (h *gemsHandler) handleDeadSlots(ctx context.Context, channelID string, active map[string]bool) {
	dead := h.deadSlots(active)

	h.slotMu.Lock()

	// Slot yang kembali aktif berarti use sebelumnya berhasil: keluarkan dari
	// daftar tunggu agar tidak di-use ulang (OwO menolaknya) dan buka blokir
	// supaya bentuk itu boleh diisi lagi saat mati nanti.
	kept := h.pendingSlots[:0]
	for _, slot := range h.pendingSlots {
		if active[slot] {
			h.retryPending = false
			continue
		}
		kept = append(kept, slot)
	}
	h.pendingSlots = kept

	// Akumulasi: bila beberapa bentuk mati beruntun sebelum inventory sempat
	// dibalas, semuanya harus ikut diisi. Versi lama menimpa pendingSlots
	// sehingga slot mati terakhir menang dan sisanya tak pernah di-use.
	before := len(h.pendingSlots)
	h.pendingSlots = mergeSlots(h.pendingSlots, dead)
	gained := len(h.pendingSlots) > before

	// Tidak ada slot baru mati dan permintaan sebelumnya masih menggantung:
	// jangan kirim inventory lagi. "Gem habis" bisa berarti stok benar-benar
	// ludes (bukan sekadar tidak aktif) sehingga use akan gagal berulang.
	if !gained && (len(dead) == 0 || h.retryPending) {
		h.slotMu.Unlock()
		return
	}
	h.retryPending = true
	h.slotMu.Unlock()

	if !sleepCtx(ctx, jitterDur(h.d.timings.GemSpentMin, h.d.timings.GemSpentMax)) {
		return
	}
	h.send(ctx, channelID, h.d.cfg.Prefix()+"inv")
}

func (h *gemsHandler) handleSpent(ctx context.Context, channelID string) {
	if !sleepCtx(ctx, jitterDur(h.d.timings.GemSpentMin, h.d.timings.GemSpentMax)) {
		return
	}
	h.send(ctx, channelID, h.d.cfg.Prefix()+"inv")
}

func (h *gemsHandler) handleInventory(ctx context.Context, channelID, text string) {
	if !h.busy.CompareAndSwap(false, true) {
		return
	}
	h.d.stopLoop()

	defer func() {
		if sleepCtx(ctx, h.d.timings.GemCooldown) {
			h.busy.Store(false)
			if !h.d.cfg.CaptchaActive() && validChannelID(h.d.cfg.ChannelID()) {
				h.d.startLoop()
			}
			return
		}
		h.busy.Store(false)
	}()

	if !sleepCtx(ctx, jitterDur(h.d.timings.GemSelectMin, h.d.timings.GemSelectMax)) {
		return
	}

	// Bentuk yang perlu diisi: yang baru saja habis. Kosong berarti tidak ada
	// slot yang mati (mis. dipicu pesan "spent" biasa), dalam hal itu ketiga
	// bentuk diisi seperti perilaku lama.
	h.slotMu.Lock()
	wanted := append([]string(nil), h.pendingSlots...)
	h.pendingSlots = nil
	h.slotMu.Unlock()

	if len(wanted) == 0 {
		wanted = allSlots
	}

	selected := pickGemsFor(text, wanted)

	if len(selected) == 0 {
		if err := h.d.cfg.Set("statusgems", false); err != nil {
			logger.Error("CONFIG", err)
		}
		logger.Warn("AUTOGEMS", "no complete gem set; autogems disabled")
		return
	}

	gems := make([]string, 0, len(selected))
	for _, gem := range selected {
		if len(gem) >= 2 {
			gems = append(gems, gem[len(gem)-2:])
		} else {
			gems = append(gems, gem)
		}
	}

	command := h.d.cfg.Prefix() + "use " + strings.Join(gems, " ")
	h.send(ctx, channelID, command)
}

func (h *gemsHandler) send(ctx context.Context, channelID, content string) {
	if h.d.rest == nil {
		return
	}
	if _, err := h.d.rest.SendMessage(ctx, channelID, content); err != nil {
		logger.Warn("DISCORD", "send failed:", err)
	}
}
