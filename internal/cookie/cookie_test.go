package cookie

import (
	"context"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestGetSuccess(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch r.URL.Path {
		case "/api/auth/discord":
			w.Header().Set("Location", "http://"+r.Host+"/oauth")
			w.WriteHeader(http.StatusFound)
		case "/oauth":
			if r.Method == http.MethodPost {
				if r.Header.Get("Authorization") != "tok.en" {
					t.Errorf("authorization salah: %q", r.Header.Get("Authorization"))
				}
				var body map[string]any
				_ = json.NewDecoder(r.Body).Decode(&body)
				if body["authorize"] != true || body["permissions"] != "0" {
					t.Errorf("body authorize salah: %+v", body)
				}
				_, _ = io.WriteString(w, `{"location":"http://`+r.Host+`/callback"}`)
				return
			}
			_, _ = io.WriteString(w, `<html><a href="/authorize">Authorize</a></html>`)
		case "/callback":
			http.SetCookie(w, &http.Cookie{Name: "session", Value: "abc"})
			w.WriteHeader(http.StatusFound)
		default:
			http.NotFound(w, r)
		}
	}))
	defer srv.Close()

	got, err := Get(context.Background(), Options{Token: "tok.en", OwoBase: srv.URL})
	if err != nil {
		t.Fatalf("Get: %v", err)
	}
	if got != "session=abc" {
		t.Fatalf("cookie salah: %q", got)
	}
}

func TestGetInvalidFlows(t *testing.T) {
	cases := map[string]http.HandlerFunc{
		"tanpa location": func(w http.ResponseWriter, r *http.Request) {
			w.WriteHeader(http.StatusOK)
		},
		"tanpa link": func(w http.ResponseWriter, r *http.Request) {
			if r.URL.Path == "/api/auth/discord" {
				w.Header().Set("Location", "/oauth")
				w.WriteHeader(http.StatusFound)
				return
			}
			_, _ = io.WriteString(w, `<html>tidak ada link</html>`)
		},
		"authorize ditolak": func(w http.ResponseWriter, r *http.Request) {
			switch r.URL.Path {
			case "/api/auth/discord":
				w.Header().Set("Location", "/oauth")
				w.WriteHeader(http.StatusFound)
			case "/oauth":
				if r.Method == http.MethodPost {
					w.WriteHeader(http.StatusForbidden)
					return
				}
				_, _ = io.WriteString(w, `<a href="/authorize">x</a>`)
			}
		},
		"tanpa location hasil": func(w http.ResponseWriter, r *http.Request) {
			switch r.URL.Path {
			case "/api/auth/discord":
				w.Header().Set("Location", "/oauth")
				w.WriteHeader(http.StatusFound)
			case "/oauth":
				if r.Method == http.MethodPost {
					_, _ = io.WriteString(w, `{}`)
					return
				}
				_, _ = io.WriteString(w, `<a href="/authorize">x</a>`)
			}
		},
		"callback bukan redirect": func(w http.ResponseWriter, r *http.Request) {
			switch r.URL.Path {
			case "/api/auth/discord":
				w.Header().Set("Location", "/oauth")
				w.WriteHeader(http.StatusFound)
			case "/oauth":
				if r.Method == http.MethodPost {
					_, _ = io.WriteString(w, `{"location":"/callback"}`)
					return
				}
				_, _ = io.WriteString(w, `<a href="/authorize">x</a>`)
			case "/callback":
				http.SetCookie(w, &http.Cookie{Name: "session", Value: "abc"})
				w.WriteHeader(http.StatusOK)
			}
		},
	}

	for name, handler := range cases {
		t.Run(name, func(t *testing.T) {
			srv := httptest.NewServer(handler)
			defer srv.Close()

			_, err := Get(context.Background(), Options{Token: "tok", OwoBase: srv.URL})
			if !errors.Is(err, ErrInvalid) {
				t.Fatalf("expected ErrInvalid, dapat %v", err)
			}
		})
	}
}

func TestGetEmptyToken(t *testing.T) {
	if _, err := Get(context.Background(), Options{}); err == nil {
		t.Fatal("expected error token kosong")
	}
}

func TestResolve(t *testing.T) {
	got, err := resolve("https://owobot.com", "/oauth")
	if err != nil || got != "https://owobot.com/oauth" {
		t.Fatalf("resolve relatif: %q %v", got, err)
	}
	got, err = resolve("https://owobot.com", "https://discord.com/oauth2/authorize")
	if err != nil || got != "https://discord.com/oauth2/authorize" {
		t.Fatalf("resolve absolut: %q %v", got, err)
	}
	if got, err := resolve("https://owobot.com", ""); err != nil || got != "" {
		t.Fatalf("resolve kosong: %q %v", got, err)
	}
}

func TestCaptchaStatus(t *testing.T) {
	tests := []struct {
		name   string
		body   string
		status int
		want   bool
	}{
		{"inactive", `{"discordId":"1","captcha":{"active":false}}`, http.StatusOK, false},
		{"active", `{"discordId":"1","captcha":{"active":true}}`, http.StatusOK, true},
		{"unauthorized", `{"error":"unauthorized"}`, http.StatusUnauthorized, false},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				if r.URL.Path != "/api/auth" {
					t.Errorf("path tak terduga: %s", r.URL.Path)
				}
				if got := r.Header.Get("Cookie"); got != "connect.sid=abc" {
					t.Errorf("cookie tidak diteruskan: %q", got)
				}
				w.WriteHeader(tc.status)
				_, _ = io.WriteString(w, tc.body)
			}))
			defer srv.Close()

			active, err := CaptchaStatus(context.Background(), Options{OwoBase: srv.URL}, "connect.sid=abc")
			if tc.status != http.StatusOK {
				if err == nil {
					t.Fatal("harus error untuk status non-OK")
				}
				return
			}
			if err != nil {
				t.Fatalf("CaptchaStatus: %v", err)
			}
			if active != tc.want {
				t.Fatalf("active=%v want %v", active, tc.want)
			}
		})
	}
}
