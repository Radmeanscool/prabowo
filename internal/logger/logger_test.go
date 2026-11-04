package logger

import (
	"bytes"
	"errors"
	"regexp"
	"strings"
	"testing"
)

var logLine = regexp.MustCompile(`^\[\d{2}:\d{2}:\d{2}\] \[(\w+)\] (.*)\n$`)

func TestLogFormat(t *testing.T) {
	var out, errOut bytes.Buffer
	l := New(&out, &errOut, false)

	l.Log("INFO", "halo", 42)
	line := out.String()
	m := logLine.FindStringSubmatch(line)
	if m == nil {
		t.Fatalf("format log tidak cocok: %q", line)
	}
	if m[1] != "INFO" || m[2] != "halo 42" {
		t.Fatalf("isi log salah: tag=%q pesan=%q", m[1], m[2])
	}
	if errOut.Len() != 0 {
		t.Fatalf("log biasa tidak boleh ke stderr: %q", errOut.String())
	}
}

func TestWarnAndErrorToStderr(t *testing.T) {
	var out, errOut bytes.Buffer
	l := New(&out, &errOut, false)

	l.Warn("WARN", "awas")
	l.Error("ERROR", errors.New("rusak"))

	if out.Len() != 0 {
		t.Fatalf("warn/error tidak boleh ke stdout: %q", out.String())
	}
	lines := strings.Split(strings.TrimSpace(errOut.String()), "\n")
	if len(lines) != 2 {
		t.Fatalf("expected 2 baris stderr, dapat %d: %q", len(lines), errOut.String())
	}
	if !strings.Contains(lines[0], "[WARN] awas") {
		t.Fatalf("baris warn salah: %q", lines[0])
	}
	if !strings.Contains(lines[1], "[ERROR] rusak") {
		t.Fatalf("baris error salah: %q", lines[1])
	}
}

func TestColorToggle(t *testing.T) {
	var plain bytes.Buffer
	New(&plain, &plain, false).Log("INFO", "x")

	if bytes.Contains(plain.Bytes(), []byte("\x1b[")) {
		t.Fatalf("warna muncul saat dimatikan: %q", plain.String())
	}

	var withColor bytes.Buffer
	New(&withColor, &withColor, true).Log("ERROR", "x")
	if !bytes.Contains(withColor.Bytes(), []byte("\x1b[31m")) {
		t.Fatalf("warna ERROR tidak muncul: %q", withColor.String())
	}
}

func TestCommand(t *testing.T) {
	var out bytes.Buffer
	l := New(&out, &out, false)

	l.Command("owoh", 620)
	line := out.String()
	if !strings.Contains(line, "[CMD]") || !strings.Contains(line, "owoh") || !strings.Contains(line, "620ms") {
		t.Fatalf("format command salah: %q", line)
	}
	if strings.Contains(line, "·") {
		t.Fatalf("command tidak boleh memakai pemisah dekoratif: %q", line)
	}

	out.Reset()
	l.Command("owo", -1)
	if strings.Contains(out.String(), "ms") {
		t.Fatalf("latency negatif tidak boleh dicetak: %q", out.String())
	}
}

func TestCycle(t *testing.T) {
	var out bytes.Buffer
	l := New(&out, &out, false)

	l.Cycle(12, 17100, 620)
	line := out.String()
	for _, want := range []string{"[CYCLE]", "#12", "17.1s", "worst 620ms"} {
		if !strings.Contains(line, want) {
			t.Fatalf("cycle tidak memuat %q: %q", want, line)
		}
	}
	if strings.Contains(line, "·") {
		t.Fatalf("cycle tidak boleh memakai pemisah dekoratif: %q", line)
	}
}

func TestLatencyColor(t *testing.T) {
	cases := []struct {
		ms   int
		want string
	}{
		{620, green},
		{1000, green},
		{1500, yellow},
		{2000, yellow},
		{3000, red},
		{-1, ""},
	}
	for _, c := range cases {
		if got := LatencyColor(c.ms); got != c.want {
			t.Errorf("LatencyColor(%d) = %q, want %q", c.ms, got, c.want)
		}
	}
}
