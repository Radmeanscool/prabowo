// Package token mengambil token Discord langsung dari penyimpanan klien
// desktop yang sedang login, sehingga config cukup berisi channelId.
//
// Token dibaca dari LevelDB milik klien Discord (~/.config/discord/Local
// Storage/leveldb). Penyimpanan itu bisa memuat beberapa token sekaligus:
// setelah login ulang, token lama tetap tersimpan meski sudah mati. Karena itu
// setiap kandidat divalidasi ke Discord dan yang pertama valid yang dipakai.
//
// Token hanya disimpan di memori proses dan tidak pernah ditulis ke disk.
package token

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"time"
)

// ErrNotFound dikembalikan bila tidak ada token di penyimpanan klien.
var (
	// ErrNotFound dikembalikan bila tidak ada token di penyimpanan klien.
	ErrNotFound = errors.New("discord token not found; make sure the Discord app is logged in")
	// ErrInvalid dikembalikan bila semua kandidat ditolak Discord.
	ErrInvalid = errors.New("discord token invalid or expired")
	// ErrAccountNotFound dikembalikan bila accountId dikunci tetapi akun itu
	// tidak ada di penyimpanan klien.
	ErrAccountNotFound = errors.New("account not found in Discord session")
	// ErrAccountInvalid dikembalikan bila akun ditemukan tetapi tokennya mati.
	ErrAccountInvalid = errors.New("that account token is no longer valid")
)

// tokenRe mencocokkan bentuk token Discord: <base64 id>.<base64 ts>.<hmac>.
var tokenRe = regexp.MustCompile(`([A-Za-z0-9_-]{20,30})\.([A-Za-z0-9_-]{5,10})\.([A-Za-z0-9_-]{25,45})`)

// mfaTokenRe mencocokkan token ber-2FA yang berawalan "mfa.".
var mfaTokenRe = regexp.MustCompile(`mfa\.([A-Za-z0-9_-]{20,30})\.([A-Za-z0-9_-]{5,10})\.([A-Za-z0-9_-]{25,45})`)

// digitRe dipakai untuk memastikan tanda tangan token mengandung angka.
var digitRe = regexp.MustCompile(`[0-9]`)

// Options mengatur pencarian token.
type Options struct {
	// ConfigDir adalah akar penyimpanan klien Discord. Kosong = deteksi
	// otomatis (~/.config/discord, discordptb, discordcanary).
	ConfigDir string
	// UserAgent dipakai saat validasi token.
	UserAgent string
	// Client opsional untuk validasi.
	Client *http.Client
	// AccountID, bila diisi, mengunci pemilihan ke akun tersebut. Tanpa ini
	// bot memakai token pertama yang masih berlaku, yang bisa berpindah akun
	// bila beberapa akun hidup di penyimpanan yang sama.
	AccountID string
	// APIBase adalah base Discord API untuk validasi. Kosong = default.
	APIBase string
}

// defaultAPIBase adalah endpoint validasi token.
const defaultAPIBase = "https://discord.com/api/v9"

func (o Options) apiBase() string {
	base := strings.TrimRight(strings.TrimSpace(o.APIBase), "/")
	if base == "" {
		return defaultAPIBase
	}
	return base
}

// Account adalah hasil pembacaan token.
type Account struct {
	Token    string
	Username string
	ID       string
}

// Find membaca semua kandidat token lalu mengembalikan yang masih berlaku.
//
// Bila opt.AccountID diisi, hanya token milik ID itu yang dipertimbangkan.
// Ini mencegah bot diam-diam berpindah akun ketika beberapa akun masih hidup
// di penyimpanan yang sama.
func Find(ctx context.Context, opt Options) (Account, error) {
	cands := candidates(opt.ConfigDir)
	if len(cands) == 0 {
		return Account{}, ErrNotFound
	}
	client := opt.Client
	if client == nil {
		client = &http.Client{Timeout: 20 * time.Second}
	}
	base := opt.apiBase()

	want := strings.TrimSpace(opt.AccountID)
	if want != "" {
		return findLocked(ctx, client, base, cands, want, opt.UserAgent)
	}

	var lastErr error
	for _, tok := range cands {
		name, id, err := validate(ctx, client, base, tok, opt.UserAgent)
		if err == nil {
			return Account{Token: tok, Username: name, ID: id}, nil
		}
		lastErr = err
	}
	if lastErr == nil {
		lastErr = ErrInvalid
	}
	return Account{}, fmt.Errorf("%w (%d candidates tried)", lastErr, len(cands))
}

// Validate memeriksa satu token yang diberikan langsung (mis. dari .env) dan
// mengembalikan akunnya. Token kosong ditolak; token yang ditolak Discord
// mengembalikan ErrInvalid.
func Validate(ctx context.Context, opt Options, tok string) (Account, error) {
	tok = strings.TrimSpace(tok)
	if tok == "" {
		return Account{}, ErrNotFound
	}
	client := opt.Client
	if client == nil {
		client = &http.Client{Timeout: 20 * time.Second}
	}
	name, id, err := validate(ctx, client, opt.apiBase(), tok, opt.UserAgent)
	if err != nil {
		return Account{}, err
	}
	return Account{Token: tok, Username: name, ID: id}, nil
}

// findLocked memilih token milik accountID tertentu.
//
// ID dibaca dari token itu sendiri tanpa jaringan, sehingga akun yang tidak
// ada bisa dilaporkan berbeda dari akun yang ada tetapi tokennya mati.
// Perbedaan ini penting: yang pertama berarti salah konfigurasi/tidak login,
// yang kedua berarti perlu login ulang.
func findLocked(ctx context.Context, client *http.Client, base string, cands []string, accountID, ua string) (Account, error) {
	found := false
	for _, tok := range cands {
		id, err := DiscordID(tok)
		if err != nil || id != accountID {
			continue
		}
		found = true
		name, gotID, err := validate(ctx, client, base, tok, ua)
		if err == nil {
			return Account{Token: tok, Username: name, ID: gotID}, nil
		}
	}
	if !found {
		return Account{}, fmt.Errorf("%w: %s (%d other accounts in storage)",
			ErrAccountNotFound, accountID, len(cands))
	}
	return Account{}, fmt.Errorf("%w: %s", ErrAccountInvalid, accountID)
}

// validate memanggil GET /users/@me untuk memastikan token masih hidup.
func validate(ctx context.Context, client *http.Client, base, tok, ua string) (username, id string, err error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, base+"/users/@me", nil)
	if err != nil {
		return "", "", err
	}
	// Token user dikirim MENTAH. Prefix "Bearer" ditolak dengan 401.
	req.Header.Set("Authorization", tok)
	if ua != "" {
		req.Header.Set("User-Agent", ua)
	}
	res, err := client.Do(req)
	if err != nil {
		return "", "", err
	}
	defer res.Body.Close()
	if res.StatusCode == http.StatusUnauthorized || res.StatusCode == http.StatusForbidden {
		return "", "", ErrInvalid
	}
	if res.StatusCode != http.StatusOK {
		return "", "", fmt.Errorf("token validation: HTTP %d", res.StatusCode)
	}
	var out struct {
		Username string `json:"username"`
		ID       string `json:"id"`
	}
	if err := json.NewDecoder(io.LimitReader(res.Body, 1<<16)).Decode(&out); err != nil {
		return "", "", err
	}
	return out.Username, out.ID, nil
}

// levelDBDirs mengembalikan kandidat direktori Local Storage/leveldb.
func levelDBDirs(root string) []string {
	if root != "" {
		return []string{filepath.Join(root, "Local Storage", "leveldb")}
	}
	home, err := os.UserHomeDir()
	if err != nil {
		return nil
	}
	base := filepath.Join(home, ".config")
	var dirs []string
	for _, name := range []string{"discord", "discordptb", "discordcanary", "Discord"} {
		dirs = append(dirs, filepath.Join(base, name, "Local Storage", "leveldb"))
	}
	return dirs
}

// candidates mengumpulkan token unik, terurut dari yang termuda.
func candidates(root string) []string {
	seen := map[string]bool{}
	type cand struct {
		tok string
		ts  int64
	}
	var list []cand
	for _, dir := range levelDBDirs(root) {
		entries, err := os.ReadDir(dir)
		if err != nil {
			continue
		}
		for _, e := range entries {
			if e.IsDir() {
				continue
			}
			name := e.Name()
			if !strings.HasSuffix(name, ".ldb") && !strings.HasSuffix(name, ".log") {
				continue
			}
			data, err := os.ReadFile(filepath.Join(dir, name))
			if err != nil {
				continue
			}
			for _, tok := range extractAll(data) {
				if seen[tok] {
					continue
				}
				seen[tok] = true
				list = append(list, cand{tok: tok, ts: timestamp(tok)})
			}
		}
	}
	// Urut menurun berdasarkan timestamp di dalam token.
	for i := 1; i < len(list); i++ {
		for j := i; j > 0 && list[j].ts > list[j-1].ts; j-- {
			list[j], list[j-1] = list[j-1], list[j]
		}
	}
	out := make([]string, 0, len(list))
	for _, c := range list {
		out = append(out, c.tok)
	}
	return out
}

// timestamp membaca timestamp pembuatan token (bagian tengah).
func timestamp(tok string) int64 {
	tok = strings.TrimPrefix(tok, "mfa.")
	parts := strings.Split(tok, ".")
	if len(parts) < 2 {
		return 0
	}
	raw, err := b64decode(parts[1])
	if err != nil {
		return 0
	}
	var v int64
	for _, b := range raw {
		if b < '0' || b > '9' {
			return 0
		}
		v = v*10 + int64(b-'0')
	}
	return v
}

// extractAll mengembalikan semua token yang lolos pemeriksaan bentuk.
func extractAll(data []byte) []string {
	var out []string
	seen := map[string]bool{}
	add := func(tok string) {
		if tok != "" && !seen[tok] {
			seen[tok] = true
			out = append(out, tok)
		}
	}
	for _, m := range mfaTokenRe.FindAllSubmatch(data, -1) {
		tok := "mfa." + string(m[1]) + "." + string(m[2]) + "." + string(m[3])
		if _, err := DiscordID(tok); err == nil {
			add(tok)
		}
	}
	for _, m := range tokenRe.FindAllSubmatch(data, -1) {
		first := string(m[1])
		// Regex bisa menelan karakter sebelum token (mis. nama kunci LevelDB),
		// jadi buang karakter awal satu per satu sampai ID terbaca numerik.
		for i := 0; i < len(first); i++ {
			cand := first[i:] + "." + string(m[2]) + "." + string(m[3])
			if _, err := DiscordID(cand); err != nil {
				continue
			}
			if plausible(cand) {
				add(cand)
				break
			}
		}
	}
	return out
}

// plausible menolak pola mirip token yang sebenarnya bukan.
func plausible(tok string) bool {
	parts := strings.Split(tok, ".")
	if len(parts) != 3 {
		return false
	}
	if len(parts[0]) < 20 || len(parts[1]) < 5 || len(parts[2]) < 25 {
		return false
	}
	return digitRe.MatchString(parts[2])
}

// DiscordID mengembalikan ID akun dari bagian pertama token.
func DiscordID(tok string) (string, error) {
	tok = strings.TrimPrefix(tok, "mfa.")
	parts := strings.Split(tok, ".")
	if len(parts) == 0 {
		return "", errors.New("invalid token")
	}
	raw, err := b64decode(parts[0])
	if err != nil {
		return "", err
	}
	id := string(raw)
	for _, r := range id {
		if r < '0' || r > '9' {
			return "", errors.New("token first part is not a numeric ID")
		}
	}
	return id, nil
}

// b64decode menerima alfabet URL-safe maupun standar.
func b64decode(s string) ([]byte, error) {
	s = strings.TrimRight(s, "=")
	if d, err := base64.RawURLEncoding.DecodeString(s); err == nil {
		return d, nil
	}
	return base64.RawStdEncoding.DecodeString(s)
}
