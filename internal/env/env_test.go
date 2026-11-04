package env

import (
	"os"
	"path/filepath"
	"testing"
)

func write(t *testing.T, content string) string {
	t.Helper()
	path := filepath.Join(t.TempDir(), ".env")
	if err := os.WriteFile(path, []byte(content), 0o600); err != nil {
		t.Fatalf("tulis .env: %v", err)
	}
	return path
}

func TestLoadParsing(t *testing.T) {
	path := write(t, `
# komentar
DISCORD_TOKEN=abc.def.ghi
export DISCORD_ACCOUNT_ID = 591974031352135690
CAPTOPIA_API_KEY="rahasia-123"
QUOTED_SINGLE='nilai satu'
INLINE=nilai # ini komentar
KOSONG=
`)
	st, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	cases := map[string]string{
		"DISCORD_TOKEN":      "abc.def.ghi",
		"DISCORD_ACCOUNT_ID": "591974031352135690",
		"CAPTOPIA_API_KEY":   "rahasia-123",
		"QUOTED_SINGLE":      "nilai satu",
		"INLINE":             "nilai",
		"KOSONG":             "",
	}
	for key, want := range cases {
		if got := st.Get(key); got != want {
			t.Errorf("Get(%s) = %q, want %q", key, got, want)
		}
	}
}

func TestLoadMissingFile(t *testing.T) {
	st, err := Load(filepath.Join(t.TempDir(), "tidak-ada.env"))
	if err != nil {
		t.Fatalf("file tidak ada harus tanpa error, dapat %v", err)
	}
	if _, ok := st.Lookup("APA"); ok {
		t.Fatal("key tidak boleh ditemukan di file kosong")
	}
}

func TestEnvironmentMenangAtasFile(t *testing.T) {
	path := write(t, "DISCORD_TOKEN=dari-file\n")
	st, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}

	t.Setenv("DISCORD_TOKEN", "dari-env")
	if got := st.Get("DISCORD_TOKEN"); got != "dari-env" {
		t.Fatalf("environment harus menang, dapat %q", got)
	}
}

func TestParseLineMenolakSampah(t *testing.T) {
	for _, line := range []string{"", "   ", "# komentar", "TANPA_EQUALS", "=nilai", "1KEY=val"} {
		if _, _, ok := parseLine(line); ok {
			t.Errorf("baris %q seharusnya ditolak", line)
		}
	}
}
