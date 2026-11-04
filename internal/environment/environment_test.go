package environment

import "testing"

func envFrom(m map[string]string) func(string) string {
	return func(k string) string { return m[k] }
}

func noFiles(string) bool { return false }

func TestDetectTermux(t *testing.T) {
	cases := []map[string]string{
		{"TERMUX_VERSION": "0.118"},
		{"PREFIX": "/data/data/com.termux/files/usr"},
	}
	for _, env := range cases {
		if got := detect(envFrom(env), "linux", noFiles); got != Termux {
			t.Errorf("env %v harus Termux, dapat %s", env, got)
		}
	}

	if got := detect(envFrom(nil), "linux", func(p string) bool { return p == "/data/data/com.termux" }); got != Termux {
		t.Errorf("keberadaan /data/data/com.termux harus Termux, dapat %s", got)
	}
}

func TestDetectDesktop(t *testing.T) {
	if got := detect(envFrom(nil), "windows", noFiles); got != Desktop {
		t.Errorf("windows harus Desktop, dapat %s", got)
	}
	if got := detect(envFrom(nil), "darwin", noFiles); got != Desktop {
		t.Errorf("darwin harus Desktop, dapat %s", got)
	}
	if got := detect(envFrom(map[string]string{"DISPLAY": ":0"}), "linux", noFiles); got != Desktop {
		t.Errorf("Linux dengan DISPLAY harus Desktop, dapat %s", got)
	}
	if got := detect(envFrom(map[string]string{"WAYLAND_DISPLAY": "wayland-0"}), "linux", noFiles); got != Desktop {
		t.Errorf("Linux dengan WAYLAND_DISPLAY harus Desktop, dapat %s", got)
	}
}

func TestDetectServer(t *testing.T) {
	if got := detect(envFrom(nil), "linux", noFiles); got != Server {
		t.Errorf("Linux headless harus Server, dapat %s", got)
	}
	if got := detect(envFrom(map[string]string{"DISPLAY": "  "}), "linux", noFiles); got != Server {
		t.Errorf("DISPLAY kosong harus Server, dapat %s", got)
	}
}

func TestKindString(t *testing.T) {
	cases := map[Kind]string{Server: "server", Desktop: "desktop", Termux: "termux"}
	for k, want := range cases {
		if k.String() != want {
			t.Errorf("%d.String() = %q, want %q", k, k.String(), want)
		}
	}
}

func TestHints(t *testing.T) {
	if n := Server.Hints(); n.TokenEmpty == "" || n.TokenInvalid == "" {
		t.Error("hints server tidak boleh kosong")
	}
	if n := Desktop.Hints(); n.TokenEmpty == "" || n.TokenInvalid == "" {
		t.Error("hints desktop tidak boleh kosong")
	}
}
