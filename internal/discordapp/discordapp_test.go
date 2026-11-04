package discordapp

import (
	"strings"
	"testing"
)

// TestChannelURLMemakaiPrefixBenar mengunci bentuk deep link. Regresi: tanpa
// "-/" Discord mendarat di halaman Friends, bukan kanal tujuan, sehingga pesan
// terkirim ke tempat yang salah.
func TestChannelURLMemakaiPrefixBenar(t *testing.T) {
	got, err := ChannelURL("1365383873420066856")
	if err != nil {
		t.Fatalf("ChannelURL: %v", err)
	}
	const want = "discord://-/channels/@me/1365383873420066856"
	if got != want {
		t.Fatalf("deep link salah:\n got  %q\n want %q", got, want)
	}
	if !strings.HasPrefix(got, "discord://-/") {
		t.Fatal("prefix discord://-/ hilang; Discord akan membuka halaman Friends")
	}
}

// TestChannelURLMemangkasSpasi menerima ID dengan spasi di tepi.
func TestChannelURLMemangkasSpasi(t *testing.T) {
	got, err := ChannelURL("  123456789  ")
	if err != nil {
		t.Fatalf("ChannelURL: %v", err)
	}
	if got != "discord://-/channels/@me/123456789" {
		t.Fatalf("spasi tidak dipangkas: %q", got)
	}
}

// TestChannelURLMenolakKosong menolak channelId kosong.
func TestChannelURLMenolakKosong(t *testing.T) {
	if _, err := ChannelURL("   "); err == nil {
		t.Fatal("channelId kosong harus ditolak")
	}
}

// TestChannelURLMenolakBukanAngka mencegah ID aneh masuk ke URL.
func TestChannelURLMenolakBukanAngka(t *testing.T) {
	for _, bad := range []string{"abc", "-", "12a34", "12/34", "1 2"} {
		if _, err := ChannelURL(bad); err == nil {
			t.Errorf("channelId %q harus ditolak", bad)
		}
	}
}
