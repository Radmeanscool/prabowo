// Package discordapp membuka aplikasi Discord desktop dan mengarahkannya ke
// kanal tertentu memakai deep link resmi.
//
// Deep link lebih andal daripada menebak posisi kanal di sidebar: daftar server
// berubah, sedangkan ID kanal tetap.
package discordapp

import (
	"errors"
	"fmt"
	"os/exec"
	"strings"
	"syscall"
)

// ChannelURL menyusun deep link menuju sebuah kanal.
//
// Bentuk yang benar adalah "discord://-/channels/@me/<id>". Tanpa "-/" (yakni
// "discord://channels/...") klien tetap terbuka tetapi mendarat di halaman
// Friends, bukan di kanal tujuan. Ini pernah terjadi dan membuat pesan terkirim
// ke tempat yang salah, jadi "-/" tidak boleh dihapus tanpa menguji ulang.
func ChannelURL(channelID string) (string, error) {
	id := strings.TrimSpace(channelID)
	if id == "" {
		return "", errors.New("empty channelId")
	}
	for _, r := range id {
		if r < '0' || r > '9' {
			return "", fmt.Errorf("channelId must be numeric: %q", id)
		}
	}
	return "discord://-/channels/@me/" + id, nil
}

// OpenChannel membuka Discord dan mengarahkannya ke kanal channelID.
//
// Aplikasi yang sudah berjalan akan memakai jendela yang ada (Discord menangani
// deep link sebagai single-instance), jadi pemanggilan ini tidak menumpuk
// jendela baru.
func OpenChannel(channelID string) error {
	url, err := ChannelURL(channelID)
	if err != nil {
		return err
	}
	return openURL(url)
}

// openURL menyerahkan URL ke penangan sistem.
//
// Proses dijalankan terpisah (detached) supaya bot tidak ikut menunggu atau
// mati bersama Discord.
func openURL(url string) error {
	for _, cand := range [][]string{{"xdg-open", url}, {"gio", "open", url}} {
		bin := cand[0]
		if _, err := exec.LookPath(bin); err != nil {
			continue
		}
		cmd := exec.Command(cand[0], cand[1:]...)
		cmd.SysProcAttr = &syscall.SysProcAttr{Setsid: true}
		cmd.Stdin = nil
		cmd.Stdout = nil
		cmd.Stderr = nil
		if err := cmd.Start(); err != nil {
			return fmt.Errorf("%s: %w", bin, err)
		}
		// Lepaskan proses; tidak perlu menunggu xdg-open selesai.
		_ = cmd.Process.Release()
		return nil
	}
	return errors.New("no URL opener available (xdg-open/gio)")
}
