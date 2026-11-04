// Package env memuat variabel dari file .env sederhana (KEY=VALUE) untuk
// menyimpan data sensitif di luar config.json.
//
// Aturan parsing:
//   - baris kosong dan baris diawali '#' diabaikan
//   - awalan "export " opsional
//   - KEY=VALUE, spasi di sekitar key/value dipangkas
//   - nilai boleh dibungkus tanda kutip tunggal/ganda
//   - komentar inline " #..." pada nilai TANPA kutip ikut dipangkas
//
// Nilai dari environment proses selalu menang atas isi file, sehingga systemd
// atau platform hosting bisa menimpa .env tanpa mengubah file.
package env

import (
	"os"
	"strings"
)

// Store menyimpan pasangan key/value dari .env.
type Store struct {
	values map[string]string
}

// Load membaca file .env. File tidak ada diperlakukan sebagai kosong (bukan
// error) agar bot tetap bisa jalan memakai environment sistem saja.
func Load(path string) (*Store, error) {
	store := &Store{values: map[string]string{}}
	if strings.TrimSpace(path) == "" {
		return store, nil
	}

	raw, err := os.ReadFile(path)
	if err != nil {
		if os.IsNotExist(err) {
			return store, nil
		}
		return store, err
	}

	for _, line := range strings.Split(string(raw), "\n") {
		key, value, ok := parseLine(line)
		if ok {
			store.values[key] = value
		}
	}
	return store, nil
}

// parseLine mengurai satu baris .env menjadi key/value.
func parseLine(line string) (key, value string, ok bool) {
	line = strings.TrimSpace(strings.TrimSuffix(line, "\r"))
	if line == "" || strings.HasPrefix(line, "#") {
		return "", "", false
	}
	line = strings.TrimPrefix(line, "export ")

	eq := strings.IndexByte(line, '=')
	if eq < 0 {
		return "", "", false
	}
	key = strings.TrimSpace(line[:eq])
	if key == "" || !validKey(key) {
		return "", "", false
	}
	value = strings.TrimSpace(line[eq+1:])

	switch {
	case len(value) >= 2 && value[0] == '"' && value[len(value)-1] == '"':
		value = value[1 : len(value)-1]
	case len(value) >= 2 && value[0] == '\'' && value[len(value)-1] == '\'':
		value = value[1 : len(value)-1]
	default:
		// Komentar inline hanya untuk nilai tanpa kutip.
		if i := strings.Index(value, " #"); i >= 0 {
			value = strings.TrimSpace(value[:i])
		}
	}
	return key, value, true
}

func validKey(key string) bool {
	for i, r := range key {
		switch {
		case r == '_':
		case r >= 'A' && r <= 'Z':
		case r >= 'a' && r <= 'z':
		case r >= '0' && r <= '9' && i > 0:
		default:
			return false
		}
	}
	return true
}

// Get mengembalikan nilai untuk key. Environment proses menang atas file.
func (s *Store) Get(key string) string {
	if v, ok := os.LookupEnv(key); ok {
		return strings.TrimSpace(v)
	}
	if s == nil {
		return ""
	}
	return strings.TrimSpace(s.values[key])
}

// Lookup seperti Get tetapi melaporkan apakah key ditemukan.
func (s *Store) Lookup(key string) (string, bool) {
	if v, ok := os.LookupEnv(key); ok {
		return strings.TrimSpace(v), true
	}
	if s == nil {
		return "", false
	}
	v, ok := s.values[key]
	return strings.TrimSpace(v), ok
}
