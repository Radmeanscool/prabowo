package logger

import (
	"fmt"
	"io"
	"os"
	"sync"
	"time"
)

const (
	reset   = "\x1b[0m"
	dim     = "\x1b[2m"
	red     = "\x1b[31m"
	green   = "\x1b[32m"
	yellow  = "\x1b[33m"
	blue    = "\x1b[34m"
	magenta = "\x1b[35m"
	cyan    = "\x1b[36m"
	gray    = "\x1b[90m"
)

const (
	latencyOK   = 1000
	latencyWarn = 2000
)

var tagColor = map[string]string{
	"INFO":        cyan,
	"LOGIN":       cyan,
	"READY":       green,
	"SERVER":      blue,
	"FARM":        blue,
	"LATENCY":     yellow,
	"CONFIG":      blue,
	"DISCORD":     blue,
	"CAPTCHA":     magenta,
	"SOLVER":      magenta,
	"AUTOGEMS":    magenta,
	"WARN":        yellow,
	"DOWN":        yellow,
	"DOUBLE-LOOP": yellow,
	"RATE LIMIT":  yellow,
	"ERROR":       red,
	"AUTH":        red,
	"LOOP":        red,
	"TOKEN":       red,
}

// Logger menulis log berformat [HH:MM:SS] [TAG] pesan.
type Logger struct {
	mu    sync.Mutex
	out   io.Writer
	err   io.Writer
	color bool
}

// New membuat Logger dengan writer dan kontrol warna eksplisit.
func New(out, errOut io.Writer, color bool) *Logger {
	if out == nil {
		out = io.Discard
	}
	if errOut == nil {
		errOut = out
	}
	return &Logger{out: out, err: errOut, color: color}
}

// NewDefault membuat Logger untuk stdout/stderr dengan deteksi warna.
func NewDefault() *Logger {
	return New(os.Stdout, os.Stderr, detectColor(os.Stdout))
}

func detectColor(f *os.File) bool {
	if os.Getenv("NO_COLOR") != "" {
		return false
	}
	if v := os.Getenv("FORCE_COLOR"); v == "1" || v == "true" {
		return true
	}
	if f == nil {
		return false
	}
	fi, err := f.Stat()
	if err != nil {
		return false
	}
	return fi.Mode()&os.ModeCharDevice != 0
}

var std = NewDefault()

func (l *Logger) paint(text, color string) string {
	if !l.color || color == "" {
		return text
	}
	return color + text + reset
}

func (l *Logger) stamp() string {
	return l.paint("["+Time()+"]", gray)
}

func (l *Logger) build(tag string, args ...any) string {
	color := tagColor[tag]
	if color == "" && tag != "" {
		color = blue
	}
	label := ""
	if tag != "" {
		label = l.paint("["+tag+"]", color) + " "
	}
	return l.stamp() + " " + label + toText(args)
}

// Log menulis log level biasa ke stdout.
func (l *Logger) Log(tag string, args ...any) {
	line := l.build(tag, args...)
	l.mu.Lock()
	defer l.mu.Unlock()
	fmt.Fprintln(l.out, line)
}

// Info adalah alias Log.
func (l *Logger) Info(tag string, args ...any) { l.Log(tag, args...) }

// Warn menulis log peringatan ke stderr.
func (l *Logger) Warn(tag string, args ...any) {
	line := l.build(tag, args...)
	l.mu.Lock()
	defer l.mu.Unlock()
	fmt.Fprintln(l.err, line)
}

// Error menulis log error ke stderr.
func (l *Logger) Error(tag string, args ...any) {
	line := l.build(tag, args...)
	l.mu.Lock()
	defer l.mu.Unlock()
	fmt.Fprintln(l.err, line)
}

// Command mencatat command yang dikirim ke Discord, dengan warna latency.
func (l *Logger) Command(text string, ms int) {
	color := LatencyColor(ms)
	line := l.stamp() + " " + l.paint("[CMD]", gray) + " " + l.paint(text, color)
	if ms >= 0 {
		line += " " + l.paint(fmt.Sprintf("%dms", ms), color)
	}
	l.mu.Lock()
	defer l.mu.Unlock()
	fmt.Fprintln(l.out, line)
}

// Cycle mencatat satu siklus farming selesai.
func (l *Logger) Cycle(n, totalMs, worstMs int) {
	color := LatencyColor(worstMs)
	label := l.paint("[CYCLE]", gray) + " " + l.paint(fmt.Sprintf("#%d", n), color)
	total := l.paint(fmt.Sprintf("%.1fs", float64(totalMs)/1000), dim)
	line := l.stamp() + " " + label + " " + total
	if worstMs >= 0 {
		line += " " + l.paint(fmt.Sprintf("worst %dms", worstMs), color)
	}
	l.mu.Lock()
	defer l.mu.Unlock()
	fmt.Fprintln(l.out, line)
}

// LatencyColor mengembalikan warna ANSI berdasarkan latency (ms).
func LatencyColor(ms int) string {
	switch {
	case ms < 0:
		return ""
	case ms <= latencyOK:
		return green
	case ms <= latencyWarn:
		return yellow
	default:
		return red
	}
}

// Time mengembalikan jam lokal berformat HH:MM:SS.
func Time() string {
	return time.Now().Format("15:04:05")
}

func toText(args []any) string {
	if len(args) == 0 {
		return ""
	}
	text := ""
	for i, a := range args {
		if i > 0 {
			text += " "
		}
		if err, ok := a.(error); ok {
			text += err.Error()
			continue
		}
		text += fmt.Sprint(a)
	}
	return text
}

// Fungsi package-level memakai logger default.

func Log(tag string, args ...any)   { std.Log(tag, args...) }
func Info(tag string, args ...any)  { std.Log(tag, args...) }
func Warn(tag string, args ...any)  { std.Warn(tag, args...) }
func Error(tag string, args ...any) { std.Error(tag, args...) }
func Command(text string, ms int)   { std.Command(text, ms) }
func Cycle(n, totalMs, worstMs int) { std.Cycle(n, totalMs, worstMs) }
