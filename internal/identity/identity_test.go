package identity

import (
	"encoding/base64"
	"encoding/json"
	"strings"
	"testing"
)

// TestUserAgentCocokDenganOS memastikan User-Agent tidak lagi mengklaim
// Windows pada mesin Linux. Regresi: sebelumnya UA memakai "Windows NT 10.0"
// dan versi discord/1.0.9210 yang tidak sesuai aplikasi terpasang.
func TestUserAgentCocokDenganOS(t *testing.T) {
	p := DefaultProfil()
	ua := p.UserAgent()

	if !strings.Contains(ua, "discord/1.0.166") {
		t.Errorf("UA harus memuat versi klien terpasang: %q", ua)
	}
	if !strings.Contains(ua, "Electron/42.11.15") {
		t.Errorf("UA harus memuat versi Electron terpasang: %q", ua)
	}
	if strings.Contains(ua, "Windows NT") && p.OS != "Windows" {
		t.Errorf("UA mengklaim Windows padahal OS=%s: %q", p.OS, ua)
	}
}

// TestSuperPropertiesBuildNumberAsli memastikan build number bukan angka
// karangan seperti 300000 pada versi lama.
func TestSuperPropertiesBuildNumberAsli(t *testing.T) {
	p := DefaultProfil()
	enc := p.BuildSuperProperties()
	if enc == "" {
		t.Fatal("super properties kosong")
	}
	raw, err := base64.StdEncoding.DecodeString(enc)
	if err != nil {
		t.Fatalf("bukan base64: %v", err)
	}
	var props map[string]any
	if err := json.Unmarshal(raw, &props); err != nil {
		t.Fatalf("bukan JSON: %v", err)
	}
	build, _ := props["client_build_number"].(float64)
	if int(build) != 649389 {
		t.Errorf("client_build_number harus 649389, dapat %v", props["client_build_number"])
	}
	if props["browser"] != "Discord Client" {
		t.Errorf("browser harus \"Discord Client\", dapat %v", props["browser"])
	}
	ua, _ := props["browser_user_agent"].(string)
	if ua != p.UserAgent() {
		t.Errorf("browser_user_agent harus sama dengan User-Agent header")
	}
}

// TestSuperPropertiesOverride menghormati nilai yang diberikan pemanggil.
func TestSuperPropertiesOverride(t *testing.T) {
	p := DefaultProfil()
	p.SuperProperties = "bawaan"
	if got := p.BuildSuperProperties(); got != "bawaan" {
		t.Errorf("override tidak dipakai: %q", got)
	}
}

// TestProfilKosongTetapValid memastikan nilai cadangan terisi.
func TestProfilKosongTetapValid(t *testing.T) {
	p := Profil{}
	ua := p.UserAgent()
	if !strings.Contains(ua, "discord/") || !strings.Contains(ua, "Electron/") {
		t.Errorf("UA cadangan tidak valid: %q", ua)
	}
	enc := p.BuildSuperProperties()
	if enc == "" {
		t.Error("super properties cadangan kosong")
	}
}

// TestTimezoneTerbaca memastikan timezone tidak kosong bila sistem
// menyediakannya.
func TestTimezoneTerbaca(t *testing.T) {
	p := DefaultProfil()
	if p.Timezone == "" {
		t.Error("timezone kosong")
	}
	if strings.ContainsAny(p.Timezone, " \t\n") {
		t.Errorf("timezone mengandung whitespace: %q", p.Timezone)
	}
}
