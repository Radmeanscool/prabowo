package config

import (
	"context"
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"
)

func TestLoadCreatesFileWithDefaults(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")

	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}
	if _, err := os.Stat(path); err != nil {
		t.Fatalf("config file tidak dibuat: %v", err)
	}

	got := cfg.Snapshot()
	if got.Prefix != "owo" || got.Action != "pray" || got.ChannelID != "" {
		t.Fatalf("default dasar salah: %+v", got)
	}
	if got.Gateway.APIVersion != 9 || got.Gateway.Compress || got.Gateway.Encoding != "json" {
		t.Fatalf("default gateway salah: %+v", got.Gateway)
	}
	if got.Farm.IntervalMs != 16000 || got.Farm.JitterMs != 4000 {
		t.Fatalf("default farm salah: %+v", got.Farm)
	}
	if got.Solver.APIBase != "https://captopia.xyz" || got.Solver.Host != "owobot.com" {
		t.Fatalf("default solver salah: %+v", got.Solver)
	}
	if got.Solver.CaptchaManualWindowMs != 540000 {
		t.Fatalf("default solver numerik salah: %+v", got.Solver)
	}
}

func TestLoadKeepsDefaultsForMissingFields(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	writeFile(t, path, `{"channelId":"123","status":true,"gateway":{"compress":false}}`)

	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	got := cfg.Snapshot()
	if got.ChannelID != "123" || !got.Status {
		t.Fatalf("nilai dari file tidak dipakai: %+v", got)
	}
	if got.Gateway.Compress {
		t.Fatal("compress=false eksplisit tertimpa default")
	}
	if got.Gateway.APIVersion != 9 {
		t.Fatalf("apiVersion hilang tidak diisi default: %d", got.Gateway.APIVersion)
	}
	if got.Farm.IntervalMs != 16000 {
		t.Fatalf("farm hilang tidak diisi default: %d", got.Farm.IntervalMs)
	}
	if got.Solver.SiteKey == "" {
		t.Fatal("solver.sitekey hilang tidak diisi default")
	}
	if got.ChannelID != "123" {
		t.Fatalf("channelId dari file salah: %q", got.ChannelID)
	}
}

func TestLoadInvalidJSON(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	writeFile(t, path, `{oops`)

	if _, err := Load(path); err == nil || !strings.Contains(err.Error(), "invalid") {
		t.Fatalf("expected error tidak valid, dapat %v", err)
	}
}

func TestValidate(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	// channelId opsional: kosong berarti bot idle (menunggu hot-reload).
	if err := cfg.Validate(); err != nil {
		t.Fatalf("channelId kosong harus valid (idle), dapat %v", err)
	}

	if err := cfg.Set("channelId", "1477403035792834573"); err != nil {
		t.Fatalf("Set channelId: %v", err)
	}
	if err := cfg.Validate(); err != nil {
		t.Fatalf("Validate setelah channelId diisi: %v", err)
	}

	if err := cfg.Set("channelId", "-"); err != nil {
		t.Fatalf("Set channelId: %v", err)
	}
	if err := cfg.Validate(); err != nil {
		t.Fatalf("placeholder \"-\" berarti idle, harus valid, dapat %v", err)
	}
}

func TestSetPersistsAndReloads(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	values := map[string]any{
		"channelId":     "1477403035792834573",
		"prefix":        "owo",
		"action":        "pray",
		"status":        true,
		"statusgems":    "true",
		"captchaStatus": 1,
	}
	for k, v := range values {
		if err := cfg.Set(k, v); err != nil {
			t.Fatalf("Set(%s): %v", k, err)
		}
	}

	reloaded, err := Load(path)
	if err != nil {
		t.Fatalf("Load ulang: %v", err)
	}
	got := reloaded.Snapshot()
	if got.ChannelID != "1477403035792834573" {
		t.Fatalf("string tidak tersimpan: %+v", got)
	}
	if !got.Status || !got.StatusGems || !got.CaptchaStatus {
		t.Fatalf("bool tidak tersimpan: %+v", got)
	}
}

func TestSetUnknownKey(t *testing.T) {
	cfg, err := Load(filepath.Join(t.TempDir(), "config.json"))
	if err != nil {
		t.Fatalf("Load: %v", err)
	}
	if err := cfg.Set("nope", 1); err == nil {
		t.Fatal("expected error untuk key tidak dikenal")
	}
	if err := cfg.Set("status", "maybe"); err == nil {
		t.Fatal("expected error untuk bool tidak valid")
	}
}

func writeFile(t *testing.T, path, content string) {
	t.Helper()
	if err := os.WriteFile(path, []byte(content), 0o600); err != nil {
		t.Fatalf("write %s: %v", path, err)
	}
}

func TestReloadDetectsChanges(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	ch, err := cfg.Reload()
	if err != nil {
		t.Fatalf("Reload tanpa perubahan: %v", err)
	}
	if ch.Changed {
		t.Fatal("tidak boleh ada perubahan")
	}

	writeFile(t, path, `{"channelId":"999","prefix":"owo","statusgems":true}`)

	ch, err = cfg.Reload()
	if err != nil {
		t.Fatalf("Reload: %v", err)
	}
	if !ch.Changed {
		t.Fatalf("perubahan tidak terdeteksi: %+v", ch)
	}
	if cfg.ChannelID() != "999" || !cfg.StatusGems() {
		t.Fatalf("nilai baru tidak dipakai: %+v", cfg.Snapshot())
	}
	if cfg.Action() != "pray" {
		t.Fatalf("default tidak diisi ulang: %+v", cfg.Snapshot())
	}

	ch, err = cfg.Reload()
	if err != nil || ch.Changed {
		t.Fatalf("reload kedua harus tanpa perubahan: %+v %v", ch, err)
	}
}

func TestReloadIgnoresBrokenFile(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}
	if err := cfg.Set("channelId", "123"); err != nil {
		t.Fatalf("Set: %v", err)
	}

	writeFile(t, path, `{rusak`)
	if _, err := cfg.Reload(); err == nil {
		t.Fatal("expected error untuk config rusak")
	}
	if cfg.ChannelID() != "123" {
		t.Fatalf("nilai lama harus dipertahankan: %q", cfg.ChannelID())
	}
}

func TestWatchCallsOnChange(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	changes := make(chan Change, 4)
	go cfg.Watch(ctx, 10*time.Millisecond, func(c Change) { changes <- c })

	time.Sleep(30 * time.Millisecond)
	writeFile(t, path, `{"channelId":"555"}`)

	select {
	case ch := <-changes:
		if !ch.Changed {
			t.Fatalf("perubahan salah: %+v", ch)
		}
	case <-time.After(15 * time.Second):
		t.Fatal("watcher tidak mendeteksi perubahan")
	}
}

// TestLoadTidakMenimpaFileYangAda mengunci perilaku yang pernah menyebabkan
// config pengguna kembali ke format lama: dulu Load() selalu menulis ulang
// file, sehingga field yang sudah dibuang (mis. "token") bisa muncul lagi.
func TestLoadTidakMenimpaFileYangAda(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	// Komentar/urutan/field tak dikenal harus dibiarkan apa adanya.
	original := "{\n  \"channelId\": \"123\",\n  \"fieldTakDikenal\": true\n}\n"
	writeFile(t, path, original)

	if _, err := Load(path); err != nil {
		t.Fatalf("Load: %v", err)
	}

	after, err := os.ReadFile(path)
	if err != nil {
		t.Fatalf("baca ulang: %v", err)
	}
	if string(after) != original {
		t.Fatalf("file berubah setelah Load:\n sebelum %q\n sesudah %q", original, string(after))
	}
}

// TestLoadMembuatFileBilaBelumAda memastikan file baru tetap dibuat.
func TestLoadMembuatFileBilaBelumAda(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	if _, err := Load(path); err != nil {
		t.Fatalf("Load: %v", err)
	}
	raw, err := os.ReadFile(path)
	if err != nil {
		t.Fatalf("file tidak dibuat: %v", err)
	}
	var got map[string]any
	if err := json.Unmarshal(raw, &got); err != nil {
		t.Fatalf("file baru bukan JSON valid: %v", err)
	}
	if _, ada := got["token"]; ada {
		t.Error("file baru tidak boleh memuat field token")
	}
}

// TestReloadTidakMemicuPerubahanSetelahLoad memastikan lastFile terisi,
// sehingga Watch tidak melaporkan perubahan palsu tepat setelah start.
func TestReloadTidakMemicuPerubahanSetelahLoad(t *testing.T) {
	path := filepath.Join(t.TempDir(), "config.json")
	writeFile(t, path, `{"channelId":"123"}`)
	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}
	ch, err := cfg.Reload()
	if err != nil {
		t.Fatalf("Reload: %v", err)
	}
	if ch.Changed {
		t.Fatal("Reload tepat setelah Load tidak boleh melaporkan perubahan")
	}
}
