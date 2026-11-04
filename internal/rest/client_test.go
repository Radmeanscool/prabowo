package rest

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"sync/atomic"
	"testing"
	"time"
)

const testToken = "TESTTOKEN.abcd.efgh"

func testClient(t *testing.T, baseURL string, maxRetries int) *Client {
	t.Helper()
	c, err := New(Options{Token: testToken, BaseURL: baseURL, MaxRetries: maxRetries})
	if err != nil {
		t.Fatalf("New: %v", err)
	}
	return c
}

func writeJSON(w http.ResponseWriter, status int, body string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_, _ = io.WriteString(w, body)
}

func TestNewValidation(t *testing.T) {
	if _, err := New(Options{}); err == nil {
		t.Fatal("expected error untuk token kosong")
	}
	if _, err := New(Options{Token: "a b"}); err == nil {
		t.Fatal("expected error untuk token ber-whitespace")
	}
	if _, err := New(Options{Token: testToken, BaseURL: "::::"}); err == nil {
		t.Fatal("expected error untuk base url tidak valid")
	}
	c, err := New(Options{Token: testToken})
	if err != nil {
		t.Fatalf("New default: %v", err)
	}
	if c.BaseURL() != DefaultBaseURL {
		t.Fatalf("base url default salah: %q", c.BaseURL())
	}
}

func TestMeHeadersAndDecode(t *testing.T) {
	var gotAuth, gotUA, gotSP string
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		gotAuth = r.Header.Get("Authorization")
		gotUA = r.Header.Get("User-Agent")
		gotSP = r.Header.Get("X-Super-Properties")

		if r.Method != http.MethodGet || r.URL.Path != "/users/@me" {
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
		writeJSON(w, http.StatusOK, `{"id":"42","username":"tester","discriminator":"0","global_name":"Tester","bot":false}`)
	}))
	defer srv.Close()

	user, err := testClient(t, srv.URL, 1).Me(context.Background())
	if err != nil {
		t.Fatalf("Me: %v", err)
	}

	if user.ID != "42" || user.Tag() != "tester" || user.DisplayName() != "Tester" {
		t.Fatalf("user salah: %+v", user)
	}
	if gotAuth != testToken {
		t.Fatalf("Authorization harus token mentah, dapat %q", gotAuth)
	}
	if strings.HasPrefix(gotAuth, "Bot ") {
		t.Fatal("Authorization tidak boleh memakai prefix Bot")
	}
	if gotUA == "" {
		t.Fatal("User-Agent kosong")
	}
	if gotSP == "" {
		t.Fatal("X-Super-Properties kosong")
	}

	raw, err := base64.StdEncoding.DecodeString(gotSP)
	if err != nil {
		t.Fatalf("super properties bukan base64: %v", err)
	}
	var props map[string]any
	if err := json.Unmarshal(raw, &props); err != nil {
		t.Fatalf("super properties bukan json: %v", err)
	}
	if props["browser_user_agent"] != gotUA {
		t.Fatalf("browser_user_agent tidak sinkron: %v vs %q", props["browser_user_agent"], gotUA)
	}
}

func TestSendMessage(t *testing.T) {
	var gotContent, gotCT string
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost || r.URL.Path != "/channels/123/messages" {
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
		gotCT = r.Header.Get("Content-Type")
		var body map[string]any
		if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
			t.Errorf("decode body: %v", err)
		}
		gotContent, _ = body["content"].(string)
		writeJSON(w, http.StatusOK, `{"id":"999","channel_id":"123","content":"owo h","author":{"id":"42"}}`)
	}))
	defer srv.Close()

	msg, err := testClient(t, srv.URL, 1).SendMessage(context.Background(), "123", "owo h")
	if err != nil {
		t.Fatalf("SendMessage: %v", err)
	}
	if gotContent != "owo h" {
		t.Fatalf("content terkirim salah: %q", gotContent)
	}
	if gotCT != "application/json" {
		t.Fatalf("Content-Type salah: %q", gotCT)
	}
	if msg.ID != "999" || msg.ChannelID != "123" {
		t.Fatalf("message salah: %+v", msg)
	}
}

func TestSendMessageValidation(t *testing.T) {
	c := testClient(t, "http://127.0.0.1:1", 0)
	if _, err := c.SendMessage(context.Background(), "123", "   "); err == nil {
		t.Fatal("expected error untuk content kosong")
	}
	if _, err := c.SendMessage(context.Background(), "", "owo"); err == nil {
		t.Fatal("expected error untuk channel kosong")
	}
}

func TestChannel(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet || r.URL.Path != "/channels/123" {
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
		writeJSON(w, http.StatusOK, `{"id":"123","type":0,"name":"farming","guild_id":"7"}`)
	}))
	defer srv.Close()

	ch, err := testClient(t, srv.URL, 1).Channel(context.Background(), "123")
	if err != nil {
		t.Fatalf("Channel: %v", err)
	}
	if ch.Name != "farming" || ch.GuildID != "7" || ch.IsDM() {
		t.Fatalf("channel salah: %+v", ch)
	}
}

func TestSendTyping(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost || r.URL.Path != "/channels/123/typing" {
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	if err := testClient(t, srv.URL, 1).SendTyping(context.Background(), "123"); err != nil {
		t.Fatalf("SendTyping: %v", err)
	}
}

func TestCreateDMAndSendDM(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		n := calls.Add(1)
		switch n {
		case 1:
			if r.Method != http.MethodPost || r.URL.Path != "/users/@me/channels" {
				t.Errorf("request DM salah: %s %s", r.Method, r.URL.Path)
			}
			var body map[string]any
			_ = json.NewDecoder(r.Body).Decode(&body)
			if body["recipient_id"] != "456" {
				t.Errorf("recipient_id salah: %v", body["recipient_id"])
			}
			writeJSON(w, http.StatusOK, `{"id":"dm1","type":1,"recipients":[{"id":"456"}]}`)
		case 2:
			if r.Method != http.MethodPost || r.URL.Path != "/channels/dm1/messages" {
				t.Errorf("request kirim DM salah: %s %s", r.Method, r.URL.Path)
			}
			writeJSON(w, http.StatusOK, `{"id":"m1","channel_id":"dm1","content":"jawaban"}`)
		default:
			t.Errorf("request tak terduga #%d", n)
		}
	}))
	defer srv.Close()

	msg, err := testClient(t, srv.URL, 1).SendDM(context.Background(), "456", "jawaban")
	if err != nil {
		t.Fatalf("SendDM: %v", err)
	}
	if msg.ChannelID != "dm1" || calls.Load() != 2 {
		t.Fatalf("DM salah: msg=%+v calls=%d", msg, calls.Load())
	}
}

func TestRateLimitRetry(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if calls.Add(1) == 1 {
			writeJSON(w, http.StatusTooManyRequests, `{"message":"You are being rate limited.","retry_after":0.01,"global":false}`)
			return
		}
		writeJSON(w, http.StatusOK, `{"id":"1","channel_id":"123"}`)
	}))
	defer srv.Close()

	start := time.Now()
	msg, err := testClient(t, srv.URL, 2).SendMessage(context.Background(), "123", "owo")
	if err != nil {
		t.Fatalf("SendMessage setelah retry: %v", err)
	}
	if msg.ID != "1" {
		t.Fatalf("message salah: %+v", msg)
	}
	if calls.Load() != 2 {
		t.Fatalf("expected 2 request, dapat %d", calls.Load())
	}
	if time.Since(start) < 10*time.Millisecond {
		t.Fatal("retry_after tidak dihormati")
	}
}

func TestRateLimitExhausted(t *testing.T) {
	var calls atomic.Int32
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		calls.Add(1)
		writeJSON(w, http.StatusTooManyRequests, `{"message":"rate limited","retry_after":0}`)
	}))
	defer srv.Close()

	_, err := testClient(t, srv.URL, 2).SendMessage(context.Background(), "123", "owo")
	if err == nil {
		t.Fatal("expected error setelah retry habis")
	}

	var apiErr *APIError
	if !errors.As(err, &apiErr) || apiErr.Status != http.StatusTooManyRequests {
		t.Fatalf("expected APIError 429, dapat %v", err)
	}
	if calls.Load() != 3 {
		t.Fatalf("expected 3 request (1+retry 2), dapat %d", calls.Load())
	}
}

func TestAPIError(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		writeJSON(w, http.StatusForbidden, `{"message":"Missing Access","code":50001}`)
	}))
	defer srv.Close()

	_, err := testClient(t, srv.URL, 1).Channel(context.Background(), "123")
	var apiErr *APIError
	if !errors.As(err, &apiErr) {
		t.Fatalf("expected APIError, dapat %v", err)
	}
	if apiErr.Status != 403 || apiErr.Code != 50001 || apiErr.Message != "Missing Access" {
		t.Fatalf("APIError salah: %+v", apiErr)
	}
}

func TestContextCancel(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		select {
		case <-r.Context().Done():
		case <-time.After(15 * time.Second):
		}
	}))
	defer srv.Close()

	ctx, cancel := context.WithTimeout(context.Background(), 20*time.Millisecond)
	defer cancel()

	start := time.Now()
	_, err := testClient(t, srv.URL, 1).Me(ctx)
	if err == nil {
		t.Fatal("expected error saat context dibatalkan")
	}
	if time.Since(start) > time.Second {
		t.Fatal("context cancel tidak menghentikan request")
	}
}

func TestClickButton(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost || r.URL.Path != "/interactions" {
			t.Errorf("request salah: %s %s", r.Method, r.URL.Path)
		}
		var body map[string]any
		if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
			t.Errorf("decode body: %v", err)
		}
		if body["type"] != float64(3) {
			t.Errorf("type interaksi salah: %v", body["type"])
		}
		if body["channel_id"] != "123" || body["message_id"] != "999" {
			t.Errorf("id interaksi salah: %+v", body)
		}
		if body["session_id"] != "sess" {
			t.Errorf("session_id salah: %v", body["session_id"])
		}
		if _, ok := body["guild_id"]; ok {
			t.Errorf("guild_id DM tidak boleh dikirim: %v", body["guild_id"])
		}
		data, _ := body["data"].(map[string]any)
		if data["custom_id"] != "battle_accept" || data["component_type"] != float64(2) {
			t.Errorf("data tombol salah: %+v", data)
		}
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	err := testClient(t, srv.URL, 1).ClickButton(context.Background(), ButtonClick{
		ChannelID: "123", MessageID: "999", SessionID: "sess",
		CustomID: "battle_accept", MessageFlags: 0,
	})
	if err != nil {
		t.Fatalf("ClickButton: %v", err)
	}

	c := testClient(t, "http://127.0.0.1:1", 0)
	if err := c.ClickButton(context.Background(), ButtonClick{}); err == nil {
		t.Fatal("expected error untuk data tombol kosong")
	}
}

// TestNonceAcceptsNumberAndString mengunci: nonce dari Discord bisa berupa
// angka atau string, dan keduanya tidak boleh menggagalkan dekode pesan.
func TestNonceAcceptsNumberAndString(t *testing.T) {
	var num Message
	if err := json.Unmarshal([]byte(`{"id":"1","nonce":123456789}`), &num); err != nil {
		t.Fatalf("nonce angka gagal decode: %v", err)
	}
	if num.Nonce.String() != "123456789" {
		t.Fatalf("nonce angka salah: %q", num.Nonce.String())
	}

	var str Message
	if err := json.Unmarshal([]byte(`{"id":"1","nonce":"987654321"}`), &str); err != nil {
		t.Fatalf("nonce string gagal decode: %v", err)
	}
	if str.Nonce.String() != "987654321" {
		t.Fatalf("nonce string salah: %q", str.Nonce.String())
	}

	var empty Message
	if err := json.Unmarshal([]byte(`{"id":"1"}`), &empty); err != nil {
		t.Fatalf("nonce hilang gagal decode: %v", err)
	}
	if empty.Nonce.String() != "" {
		t.Fatalf("nonce kosong salah: %q", empty.Nonce.String())
	}
}

// TestSendSlashCommandWaitsForReply memastikan slash sukses hanya setelah ada
// MESSAGE_CREATE dengan nonce yang sama — bukan karena HTTP 2xx semata.
func TestSendSlashCommandWaitsForReply(t *testing.T) {
	c := testClient(t, "http://127.0.0.1:1", 0)
	// Kosongkan cache command & isi langsung agar tidak butuh lookup HTTP.
	c.slashCache["|123|408785106942164992|hunt"] = appCommand{
		ID: "cmd1", Name: "hunt", ApplicationID: "408785106942164992", Version: "1", Type: 1,
	}

	nonceCh := make(chan string, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var body map[string]any
		_ = json.NewDecoder(r.Body).Decode(&body)
		if n, ok := body["nonce"].(string); ok {
			nonceCh <- n
		}
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	// Ganti baseURL ke server yang menangkap nonce.
	c.baseURL = srv.URL

	errCh := make(chan error, 1)
	go func() {
		errCh <- c.SendSlashCommand(context.Background(), SlashOptions{
			ApplicationID: "408785106942164992", CommandName: "hunt",
			ChannelID: "123", SessionID: "sess", ResponseTimeout: 3 * time.Second,
		})
	}()

	// Tunggu nonce yang dikirim, lalu balas seperti OwO.
	var nonce string
	select {
	case nonce = <-nonceCh:
	case <-time.After(time.Second):
		t.Fatal("slash tidak dikirim")
	}
	c.NotifyMessage(Message{Nonce: Nonce(nonce)})

	select {
	case err := <-errCh:
		if err != nil {
			t.Fatalf("slash harus sukses setelah balasan nonce: %v", err)
		}
	case <-time.After(time.Second):
		t.Fatal("SendSlashCommand tidak selesai setelah balasan")
	}
}

// TestSendSlashCommandTimeout memastikan tanpa balasan ber-nonce, slash gagal
// dengan ErrInteractionFailed walau POST sukses.
func TestSendSlashCommandTimeout(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	c := testClient(t, srv.URL, 0)
	c.slashCache["|123|408785106942164992|battle"] = appCommand{
		ID: "cmd2", Name: "battle", ApplicationID: "408785106942164992", Version: "1", Type: 1,
	}

	err := c.SendSlashCommand(context.Background(), SlashOptions{
		ApplicationID: "408785106942164992", CommandName: "battle",
		ChannelID: "123", SessionID: "sess", ResponseTimeout: 30 * time.Millisecond,
	})
	if !errors.Is(err, ErrInteractionFailed) {
		t.Fatalf("expected ErrInteractionFailed, dapat %v", err)
	}
}
