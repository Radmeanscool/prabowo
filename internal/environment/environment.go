// Package environment mendeteksi jenis lingkungan tempat bot dijalankan
// (desktop, Termux, atau server) agar pesan error dan arahan bisa disesuaikan.
//
// Deteksi bersifat best-effort; kegagalan tidak fatal.
package environment

import (
	"os"
	"runtime"
	"strings"
)

// Kind adalah jenis lingkungan.
type Kind int

const (
	// Server adalah default: VPS, container, hosting, atau mesin tanpa sesi
	// grafis. Bot mengandalkan token di .env.
	Server Kind = iota
	// Desktop adalah mesin dengan sesi grafis (Windows, macOS, atau Linux
	// ber-DISPLAY), tempat aplikasi Discord desktop biasanya tersedia.
	Desktop
	// Termux adalah lingkungan Termux di Android.
	Termux
)

func (k Kind) String() string {
	switch k {
	case Desktop:
		return "desktop"
	case Termux:
		return "termux"
	default:
		return "server"
	}
}

// Detect menebak lingkungan saat ini.
func Detect() Kind {
	return detect(os.Getenv, runtime.GOOS, pathExists)
}

// detect memisahkan logika dari sumber data agar mudah diuji.
func detect(getenv func(string) string, goos string, exists func(string) bool) Kind {
	// Termux: penanda paling khas adalah TERMUX_VERSION atau PREFIX yang
	// menunjuk ke direktori paket Termux.
	for _, key := range []string{"TERMUX_VERSION", "TERMUX_APP_PID"} {
		if strings.TrimSpace(getenv(key)) != "" {
			return Termux
		}
	}
	if strings.Contains(getenv("PREFIX"), "com.termux") {
		return Termux
	}
	if exists("/data/data/com.termux") {
		return Termux
	}

	// Desktop: OS grafis, atau Linux dengan sesi tampilan aktif.
	if goos == "windows" || goos == "darwin" {
		return Desktop
	}
	if strings.TrimSpace(getenv("DISPLAY")) != "" || strings.TrimSpace(getenv("WAYLAND_DISPLAY")) != "" {
		return Desktop
	}
	return Server
}

func pathExists(path string) bool {
	_, err := os.Stat(path)
	return err == nil
}

// Hints berisi arahan yang disesuaikan dengan lingkungan.
type Hints struct {
	// TokenEmpty: arahan saat token tidak tersedia dan auto-baca gagal.
	TokenEmpty string
	// TokenInvalid: arahan saat token ditolak Discord.
	TokenInvalid string
}

// Hints mengembalikan arahan untuk lingkungan ini.
func (k Kind) Hints() Hints {
	switch k {
	case Desktop:
		return Hints{
			TokenEmpty:   "set DISCORD_TOKEN in .env, or log in to the Discord desktop app so the token is read automatically",
			TokenInvalid: "update DISCORD_TOKEN in .env, or log in again to the Discord desktop app and restart the bot",
		}
	default: // Server & Termux
		return Hints{
			TokenEmpty:   "set DISCORD_TOKEN in .env (required in an environment without the Discord desktop app)",
			TokenInvalid: "update DISCORD_TOKEN in .env",
		}
	}
}
