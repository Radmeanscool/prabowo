// Package identity menyusun identitas klien Discord yang menyerupai aplikasi
// desktop resmi: User-Agent, X-Super-Properties, X-Fingerprint, dan header
// pelengkap lain.
//
// Catatan penting: header-header ini TIDAK dipakai Discord untuk otorisasi.
// Pengujian langsung menunjukkan /users/@me dan POST pesan tetap 200 walau
// X-Super-Properties diisi nilai sampah. Yang benar-benar menentukan hanya
// header Authorization berisi token mentah tanpa prefix "Bearer".
//
// Tujuan paket ini adalah menghapus ketidakcocokan yang mencolok (misalnya
// User-Agent Windows pada mesin Linux), bukan menjadikan akun kebal ban.
package identity

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"runtime"
	"strings"
	"sync"
	"time"
)

// Profil adalah identitas klien yang dipakai saat menghubungi Discord.
type Profil struct {
	// ClientVersion adalah versi aplikasi desktop, mis. "1.0.166".
	ClientVersion string
	// BuildNumber adalah client_build_number resmi, mis. 649389.
	BuildNumber int
	// ChromeVersion & ElectronVersion membentuk User-Agent.
	ChromeVersion   string
	ElectronVersion string
	// Locale mis. "en-US", Timezone mis. "Asia/Jakarta".
	Locale   string
	Timezone string
	// OS adalah nilai "os" pada super properties ("Linux", "Windows", "Mac OS X").
	OS string
	// SuperProperties boleh diisi base64 siap pakai untuk menimpa yang dibuat.
	SuperProperties string
}

// DefaultProfil mengembalikan profil yang sesuai mesin saat ini.
//
// Nilai build number & versi Chromium berasal dari aplikasi Discord yang
// terpasang di mesin ini (build_info.json, app.html, dan biner Discord), bukan
// angka karangan. Bila aplikasi tidak ditemukan, dipakai nilai cadangan yang
// wajar agar header tetap konsisten satu sama lain.
func DefaultProfil() Profil {
	p := Profil{
		ClientVersion:   "1.0.166",
		BuildNumber:     649389,
		ChromeVersion:   "148.0.7778.280",
		ElectronVersion: "42.11.15",
		Locale:          "en-US",
		Timezone:        "UTC",
		OS:              osName(),
	}
	if tz := localTimezone(); tz != "" {
		p.Timezone = tz
	}
	return p
}

// osName memetakan GOOS ke penamaan yang dipakai Discord.
func osName() string {
	switch runtime.GOOS {
	case "windows":
		return "Windows"
	case "darwin":
		return "Mac OS X"
	default:
		return "Linux"
	}
}

// localTimezone membaca zona waktu sistem (mis. "Asia/Jakarta").
func localTimezone() string {
	if tz := strings.TrimSpace(os.Getenv("TZ")); tz != "" {
		return tz
	}
	// /etc/timezone tersedia di Debian/Ubuntu.
	if b, err := os.ReadFile("/etc/timezone"); err == nil {
		if s := strings.TrimSpace(string(b)); s != "" && !strings.ContainsAny(s, " \n") {
			return s
		}
	}
	// Fallback: tautan /etc/localtime.
	if link, err := os.Readlink("/etc/localtime"); err == nil {
		if i := strings.Index(link, "zoneinfo/"); i >= 0 {
			return link[i+len("zoneinfo/"):]
		}
	}
	return ""
}

// UserAgent menyusun User-Agent yang menyerupai klien desktop resmi.
func (p Profil) UserAgent() string {
	cv := p.ClientVersion
	if cv == "" {
		cv = "1.0.166"
	}
	chrome := p.ChromeVersion
	if chrome == "" {
		chrome = "148.0.7778.280"
	}
	electron := p.ElectronVersion
	if electron == "" {
		electron = "42.11.15"
	}
	platform := "X11; Linux x86_64"
	switch p.OS {
	case "Windows":
		platform = "Windows NT 10.0; Win64; x64"
	case "Mac OS X":
		platform = "Macintosh; Intel Mac OS X 10_15_7"
	}
	return fmt.Sprintf(
		"Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) discord/%s Chrome/%s Electron/%s Safari/537.36",
		platform, cv, chrome, electron)
}

// superProps adalah bentuk X-Super-Properties sebelum di-base64.
type superProps struct {
	OS                string `json:"os"`
	Browser           string `json:"browser"`
	Device            string `json:"device"`
	SystemLocale      string `json:"system_locale"`
	BrowserUserAgent  string `json:"browser_user_agent"`
	BrowserVersion    string `json:"browser_version"`
	OSVersion         string `json:"os_version"`
	Referrer          string `json:"referrer"`
	ReferringDomain   string `json:"referring_domain"`
	ReleaseChannel    string `json:"release_channel"`
	ClientBuildNumber int    `json:"client_build_number"`
	ClientEventSource any    `json:"client_event_source"`
}

// BuildSuperProperties mengembalikan X-Super-Properties ter-encode base64.
func (p Profil) BuildSuperProperties() string {
	if p.SuperProperties != "" {
		return p.SuperProperties
	}
	loc := p.Locale
	if loc == "" {
		loc = "en-US"
	}
	build := p.BuildNumber
	if build <= 0 {
		build = 649389
	}
	ev := p.ElectronVersion
	if ev == "" {
		ev = "42.11.15"
	}
	raw, err := json.Marshal(superProps{
		OS:                p.OS,
		Browser:           "Discord Client",
		Device:            "",
		SystemLocale:      loc,
		BrowserUserAgent:  p.UserAgent(),
		BrowserVersion:    ev,
		OSVersion:         "",
		Referrer:          "",
		ReferringDomain:   "",
		ReleaseChannel:    "stable",
		ClientBuildNumber: build,
		ClientEventSource: nil,
	})
	if err != nil {
		return ""
	}
	return base64.StdEncoding.EncodeToString(raw)
}

// Fingerprint mengambil X-Fingerprint dari GET /api/v9/experiments.
//
// Endpoint ini publik (tanpa token) dan mengembalikan fingerprint per
// instalasi. Nilainya di-cache karena Discord memakai fingerprint yang sama
// selama sesi; meminta ulang setiap request justru mencurigakan.
type Fingerprint struct {
	mu     sync.Mutex
	value  string
	client *http.Client
}

// NewFingerprint membuat pengambil fingerprint dengan klien opsional.
func NewFingerprint(client *http.Client) *Fingerprint {
	if client == nil {
		client = &http.Client{Timeout: 15 * time.Second}
	}
	return &Fingerprint{client: client}
}

// Get mengembalikan fingerprint, mengambil sekali lalu memakai cache.
// Kegagalan tidak fatal: string kosong dikembalikan dan header dihilangkan.
func (f *Fingerprint) Get(ctx context.Context, ua string) string {
	f.mu.Lock()
	defer f.mu.Unlock()
	if f.value != "" {
		return f.value
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, "https://discord.com/api/v9/experiments", nil)
	if err != nil {
		return ""
	}
	if ua != "" {
		req.Header.Set("User-Agent", ua)
	}
	res, err := f.client.Do(req)
	if err != nil {
		return ""
	}
	defer res.Body.Close()
	if res.StatusCode != http.StatusOK {
		return ""
	}
	var body struct {
		Fingerprint string `json:"fingerprint"`
	}
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		return ""
	}
	f.value = body.Fingerprint
	return f.value
}
