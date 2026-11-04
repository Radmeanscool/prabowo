package token

import (
	"context"
	"encoding/base64"
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"
)

// fakeToken membuat token berbentuk benar dari ID numerik.
func fakeToken(id, ts, sig string) string {
	enc := func(s string) string { return base64.RawURLEncoding.EncodeToString([]byte(s)) }
	return enc(id) + "." + enc(ts) + "." + sig
}

// TestDiscordIDMembacaID memastikan ID akun terbaca dari bagian pertama.
func TestDiscordIDMembacaID(t *testing.T) {
	tok := fakeToken("591974031352135690", "17000", "abcdefghij0123456789ABCDEFGHIJ")
	id, err := DiscordID(tok)
	if err != nil {
		t.Fatalf("DiscordID error: %v", err)
	}
	if id != "591974031352135690" {
		t.Errorf("ID salah: %q", id)
	}
}

// TestDiscordIDMenolakBukanID menolak token yang bagian pertamanya bukan angka.
func TestDiscordIDMenolakBukanID(t *testing.T) {
	enc := base64.RawURLEncoding.EncodeToString([]byte("bukan-angka"))
	if _, err := DiscordID(enc + ".MTcwMA.sig"); err == nil {
		t.Error("token dengan bagian pertama non-numerik harus ditolak")
	}
}

// TestPlausible menolak pola yang mirip token tetapi bukan.
func TestPlausible(t *testing.T) {
	good := fakeToken("591974031352135690", "17000", "abcdefghij0123456789ABCDEFGHIJ")
	if !plausible(good) {
		t.Error("token valid ditolak")
	}
	if plausible("pendek.pendek.pendek") {
		t.Error("token terlalu pendek harus ditolak")
	}
	// Tanda tangan tanpa angka.
	noDigit := fakeToken("591974031352135690", "17000", "abcdefghijABCDEFGHIJKLMNOPQRST")
	if plausible(noDigit) {
		t.Error("tanda tangan tanpa angka harus ditolak")
	}
}

// TestCandidatesMengurutkanDariTermuda memastikan token terbaru dipilih lebih
// dulu, karena penyimpanan klien bisa memuat token lama yang sudah mati.
func TestCandidatesMengurutkanDariTermuda(t *testing.T) {
	root := t.TempDir()
	dir := filepath.Join(root, "Local Storage", "leveldb")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	old := fakeToken("591974031352135690", "16000", "abcdefghij0123456789ABCDEFGHIJ")
	newer := fakeToken("591974031352135690", "17000", "zyxwvutsr9876543210ABCDEFGHIJ")
	logPath := filepath.Join(dir, "000003.log")
	content := []byte("key1\x00" + old + "\x00key2\x00" + newer + "\x00")
	if err := os.WriteFile(logPath, content, 0o600); err != nil {
		t.Fatal(err)
	}
	got := candidates(root)
	if len(got) != 2 {
		t.Fatalf("harus 2 kandidat, dapat %d: %v", len(got), got)
	}
	if got[0] != newer {
		t.Errorf("kandidat pertama harus token termuda")
	}
}

// TestCandidatesTanpaFile mengembalikan kosong, bukan panik.
func TestCandidatesTanpaFile(t *testing.T) {
	root := t.TempDir()
	if got := candidates(root); len(got) != 0 {
		t.Errorf("harus kosong, dapat %v", got)
	}
}

// TestFindTanpaToken mengembalikan ErrNotFound bila direktori kosong.
func TestFindTanpaToken(t *testing.T) {
	dir := t.TempDir()
	_, err := Find(context.Background(), Options{ConfigDir: dir})
	if err == nil {
		t.Fatal("harus error")
	}
	if !strings.Contains(err.Error(), "not found") {
		t.Errorf("pesan salah: %v", err)
	}
}

// TestFindLockedMenolakAkunLain memastikan accountId benar-benar mengunci
// pilihan ke ID tersebut, bukan sekadar mengurutkan ulang.
func TestFindLockedMenolakAkunLain(t *testing.T) {
	root := t.TempDir()
	dir := filepath.Join(root, "Local Storage", "leveldb")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	tok := fakeToken("591974031352135690", "17000", "zyxwvutsr9876543210ABCDEFGHIJ")
	if err := os.WriteFile(filepath.Join(dir, "000003.log"),
		[]byte("k\x00"+tok+"\x00"), 0o600); err != nil {
		t.Fatal(err)
	}

	// ID yang tidak ada di penyimpanan: harus gagal, bukan memakai token lain.
	_, err := Find(context.Background(), Options{
		ConfigDir: root,
		AccountID: "999999999999999999",
		Client:    &http.Client{Timeout: time.Second},
	})
	if !errors.Is(err, ErrAccountNotFound) {
		t.Fatalf("harus ErrAccountNotFound, dapat %v", err)
	}
}

// TestFindLockedTanpaJaringan memastikan ID dicocokkan dari isi token, sehingga
// akun yang tidak ada terdeteksi tanpa perlu menghubungi Discord.
func TestFindLockedTanpaJaringan(t *testing.T) {
	root := t.TempDir()
	dir := filepath.Join(root, "Local Storage", "leveldb")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	tok := fakeToken("591974031352135690", "17000", "zyxwvutsr9876543210ABCDEFGHIJ")
	if err := os.WriteFile(filepath.Join(dir, "000003.log"),
		[]byte("k\x00"+tok+"\x00"), 0o600); err != nil {
		t.Fatal(err)
	}
	// Klien dengan timeout sangat pendek: bila kode mencoba jaringan untuk ID
	// yang tidak cocok, test ini akan lambat. Yang diuji hanya pesan errornya.
	_, err := Find(context.Background(), Options{
		ConfigDir: root,
		AccountID: "111111111111111111",
		Client:    &http.Client{Timeout: 10 * time.Millisecond},
	})
	if !errors.Is(err, ErrAccountNotFound) {
		t.Fatalf("harus ErrAccountNotFound, dapat %v", err)
	}
	if !strings.Contains(err.Error(), "111111111111111111") {
		t.Errorf("pesan harus menyebut ID yang diminta: %v", err)
	}
}

// TestValidateTokenManual mengunci: token yang diberikan langsung divalidasi ke
// Discord dan mengembalikan akun; token kosong ditolak; token mati -> ErrInvalid.
func TestValidateTokenManual(t *testing.T) {
	tok := fakeToken("591974031352135690", "17000", "abcdefghij0123456789ABCDEFGHIJ")

	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("Authorization") != tok {
			w.WriteHeader(http.StatusUnauthorized)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = io.WriteString(w, `{"username":"tester","id":"591974031352135690"}`)
	}))
	defer srv.Close()

	acc, err := Validate(context.Background(), Options{APIBase: srv.URL, Client: srv.Client()}, tok)
	if err != nil {
		t.Fatalf("Validate: %v", err)
	}
	if acc.Username != "tester" || acc.ID != "591974031352135690" || acc.Token != tok {
		t.Fatalf("akun salah: %+v", acc)
	}

	if _, err := Validate(context.Background(), Options{APIBase: srv.URL, Client: srv.Client()}, "   "); err == nil {
		t.Fatal("token kosong harus error")
	}

	if _, err := Validate(context.Background(), Options{APIBase: srv.URL, Client: srv.Client()}, "tok.salah.x"); !errors.Is(err, ErrInvalid) {
		t.Fatalf("token ditolak harus ErrInvalid, dapat %v", err)
	}
}
