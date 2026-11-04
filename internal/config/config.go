package config

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
)

// Solver menyimpan konfigurasi layanan captcha. API key TIDAK di sini; ia
// disimpan sebagai CAPTOPIA_API_KEY di .env.
type Solver struct {
	APIBase               string `json:"apiBase"`
	SiteKey               string `json:"sitekey"`
	URL                   string `json:"url"`
	Host                  string `json:"host"`
	TimeoutMs             int    `json:"timeoutMs"`
	ImageTimeoutMs        int    `json:"imageTimeoutMs"`
	ImageSamples          int    `json:"imageSamples"`
	QueueMax              int    `json:"queueMax"`
	Debug                 bool   `json:"debug"`
	CaptchaManualWindowMs int    `json:"captchaManualWindowMs"`
}

// Gateway menyimpan parameter koneksi Discord Gateway.
type Gateway struct {
	APIVersion int    `json:"apiVersion"`
	Encoding   string `json:"encoding"`
	Compress   bool   `json:"compress"`
	Intents    int    `json:"intents"`
}

// Farm menyimpan parameter loop farming.
type Farm struct {
	IntervalMs int `json:"intervalMs"`
	JitterMs   int `json:"jitterMs"`
}

// fileConfig adalah representasi di disk. Semua field di sini yang
// di-serialize; struct Config menyimpan salinan aman untuk runtime.
type fileConfig struct {
	ChannelID     string  `json:"channelId"`
	Prefix        string  `json:"prefix"`
	Action        string  `json:"action"`
	Status        bool    `json:"status"`
	StatusGems    bool    `json:"statusgems"`
	CaptchaStatus bool    `json:"captchaStatus"`
	Gateway       Gateway `json:"gateway"`
	Farm          Farm    `json:"farm"`
	Solver        Solver  `json:"solver"`
}

// Config adalah konfigurasi runtime yang aman dipakai lintas goroutine.
type Config struct {
	mu       sync.RWMutex
	path     string
	data     fileConfig
	lastFile []byte
}

// Default mengembalikan nilai default yang sama dengan config.js versi Node.
func Default() fileConfig {
	return fileConfig{
		ChannelID:     "",
		Prefix:        "owo",
		Action:        "pray",
		Status:        false,
		StatusGems:    false,
		CaptchaStatus: false,
		Gateway: Gateway{
			APIVersion: 9,
			Encoding:   "json",
			Compress:   false,
			Intents:    0,
		},
		Farm: Farm{
			IntervalMs: 16000,
			JitterMs:   4000,
		},
		Solver: Solver{
			APIBase:               "https://captopia.xyz",
			SiteKey:               "a6a1d5ce-612d-472d-8e37-7601408fbc09",
			URL:                   "https://owobot.com/captcha",
			Host:                  "owobot.com",
			TimeoutMs:             480000,
			ImageTimeoutMs:        180000,
			ImageSamples:          3,
			QueueMax:              1,
			Debug:                 false,
			CaptchaManualWindowMs: 9 * 60 * 1000,
		},
	}
}

// Load membaca config.json. Bila file belum ada, file dibuat dari default.
// Field yang hilang diisi default; field tak dikenal diabaikan.
func Load(path string) (*Config, error) {
	if strings.TrimSpace(path) == "" {
		return nil, errors.New("empty config path")
	}

	cfg := &Config{path: path, data: Default()}

	raw, err := os.ReadFile(path)
	switch {
	case err == nil && len(bytes.TrimSpace(raw)) > 0:
		// Unmarshal di atas default: field yang hilang mempertahankan nilai
		// default, sedangkan bool false yang ditulis eksplisit tetap dihormati.
		if err := json.Unmarshal(raw, &cfg.data); err != nil {
			return nil, fmt.Errorf("config %s is invalid: %w", path, err)
		}
		cfg.applyDefaults()
		cfg.lastFile = raw
		return cfg, nil
	case err == nil:
		// File ada tetapi kosong: perlakukan seperti belum ada.
	case !errors.Is(err, os.ErrNotExist):
		return nil, fmt.Errorf("failed to read config %s: %w", path, err)
	}

	// Hanya file yang BELUM ADA yang dibuat. Jangan menulis ulang file yang
	// sudah ada: dulu Load() selalu menulis, sehingga binary versi lama bisa
	// menimpa config dengan format lamanya (mis. mengembalikan field "token").
	cfg.applyDefaults()
	if err := cfg.saveLocked(); err != nil {
		return nil, err
	}
	return cfg, nil
}

// DefaultPath mengembalikan path config default di direktori kerja.
func DefaultPath() string {
	return "config.json"
}

func (c *Config) applyDefaults() {
	d := Default()

	if c.data.ChannelID == "" {
		c.data.ChannelID = d.ChannelID
	}
	if c.data.Prefix == "" {
		c.data.Prefix = d.Prefix
	}
	if c.data.Action == "" {
		c.data.Action = d.Action
	}

	if c.data.Gateway.APIVersion <= 0 {
		c.data.Gateway.APIVersion = d.Gateway.APIVersion
	}
	if c.data.Gateway.Encoding == "" {
		c.data.Gateway.Encoding = d.Gateway.Encoding
	}

	if c.data.Farm.IntervalMs <= 0 {
		c.data.Farm.IntervalMs = d.Farm.IntervalMs
	}
	if c.data.Farm.JitterMs < 0 {
		c.data.Farm.JitterMs = d.Farm.JitterMs
	}

	s := &c.data.Solver
	if s.APIBase == "" {
		s.APIBase = d.Solver.APIBase
	}
	if s.SiteKey == "" {
		s.SiteKey = d.Solver.SiteKey
	}
	if s.URL == "" {
		s.URL = d.Solver.URL
	}
	if s.Host == "" {
		s.Host = d.Solver.Host
	}
	if s.TimeoutMs <= 0 {
		s.TimeoutMs = d.Solver.TimeoutMs
	}
	if s.ImageTimeoutMs <= 0 {
		s.ImageTimeoutMs = d.Solver.ImageTimeoutMs
	}
	if s.ImageSamples <= 0 {
		s.ImageSamples = d.Solver.ImageSamples
	}
	if s.QueueMax <= 0 {
		s.QueueMax = d.Solver.QueueMax
	}
	if s.CaptchaManualWindowMs <= 0 {
		s.CaptchaManualWindowMs = d.Solver.CaptchaManualWindowMs
	}
}

// Change merangkum hasil pemuatan ulang config.json.
type Change struct {
	Changed bool
}

// Reload membaca ulang config.json bila isinya berubah. Tidak menulis file.
func (c *Config) Reload() (Change, error) {
	raw, err := os.ReadFile(c.path)
	if err != nil {
		if errors.Is(err, os.ErrNotExist) {
			return Change{}, nil
		}
		return Change{}, fmt.Errorf("failed to read config %s: %w", c.path, err)
	}
	if len(bytes.TrimSpace(raw)) == 0 {
		return Change{}, nil
	}

	c.mu.Lock()
	defer c.mu.Unlock()

	if bytes.Equal(raw, c.lastFile) {
		return Change{}, nil
	}

	var parsed fileConfig
	if err := json.Unmarshal(raw, &parsed); err != nil {
		return Change{}, fmt.Errorf("config %s is invalid: %w", c.path, err)
	}

	c.data = parsed
	c.applyDefaults()
	c.lastFile = raw

	return Change{Changed: true}, nil
}

// Watch memantau config.json dan memanggil on setiap perubahan (tanpa
// menulis ulang file). Berhenti saat ctx dibatalkan.
func (c *Config) Watch(ctx context.Context, interval time.Duration, on func(Change)) {
	if interval <= 0 {
		interval = time.Second
	}
	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			ch, err := c.Reload()
			if err != nil {
				logger.Error("CONFIG", err)
				continue
			}
			if ch.Changed && on != nil {
				on(ch)
			}
		}
	}
}

// Validate memastikan konfigurasi cukup untuk mulai login.
func (c *Config) Validate() error {
	c.mu.RLock()
	defer c.mu.RUnlock()

	// channelId opsional: kosong (atau "-") berarti bot idle — login ke gateway
	// tanpa farming, menunggu channelId diisi lewat hot-reload.
	if c.data.Prefix == "" {
		return errors.New("prefix must not be empty")
	}
	return nil
}

// Save menulis konfigurasi ke disk secara atomik.
func (c *Config) Save() error {
	c.mu.Lock()
	defer c.mu.Unlock()
	return c.saveLocked()
}

func (c *Config) saveLocked() error {
	content, err := json.MarshalIndent(c.data, "", "  ")
	if err != nil {
		return fmt.Errorf("failed to encode config: %w", err)
	}
	content = append(content, '\n')

	if bytes.Equal(content, c.lastFile) {
		return nil
	}

	dir := filepath.Dir(c.path)
	if dir != "" && dir != "." {
		if err := os.MkdirAll(dir, 0o755); err != nil {
			return fmt.Errorf("failed to create config directory: %w", err)
		}
	}

	tmp := c.path + ".tmp"
	if err := os.WriteFile(tmp, content, 0o600); err != nil {
		return fmt.Errorf("failed to write config: %w", err)
	}
	if err := os.Rename(tmp, c.path); err != nil {
		_ = os.Remove(tmp)
		return fmt.Errorf("failed to save config: %w", err)
	}

	c.lastFile = content
	return nil
}

// Set mengubah satu nilai runtime lalu menyimpannya. Key yang didukung sama
// dengan key publik di config.js.
func (c *Config) Set(key string, value any) error {
	c.mu.Lock()
	defer c.mu.Unlock()

	switch key {
	case "channelId":
		s, ok := value.(string)
		if !ok {
			return fmt.Errorf("channelId must be a string, got %T", value)
		}
		c.data.ChannelID = s
	case "prefix":
		s, ok := value.(string)
		if !ok {
			return fmt.Errorf("prefix must be a string, got %T", value)
		}
		c.data.Prefix = s
	case "action":
		s, ok := value.(string)
		if !ok {
			return fmt.Errorf("action must be a string, got %T", value)
		}
		c.data.Action = s
	case "status":
		b, err := toBool(value)
		if err != nil {
			return fmt.Errorf("status: %w", err)
		}
		c.data.Status = b
	case "statusgems":
		b, err := toBool(value)
		if err != nil {
			return fmt.Errorf("statusgems: %w", err)
		}
		c.data.StatusGems = b
	case "captchaStatus":
		b, err := toBool(value)
		if err != nil {
			return fmt.Errorf("captchaStatus: %w", err)
		}
		c.data.CaptchaStatus = b
	default:
		return fmt.Errorf("unknown config key: %q", key)
	}

	c.applyDefaults()
	return c.saveLocked()
}

func toBool(value any) (bool, error) {
	switch v := value.(type) {
	case bool:
		return v, nil
	case int:
		return v != 0, nil
	case string:
		switch strings.ToLower(strings.TrimSpace(v)) {
		case "1", "true", "yes", "on":
			return true, nil
		case "0", "false", "no", "off", "":
			return false, nil
		}
	}
	return false, fmt.Errorf("invalid boolean value: %v", value)
}

// Path mengembalikan lokasi file config.
func (c *Config) Path() string { return c.path }

// Snapshot mengembalikan salinan nilai runtime untuk keperluan baca/log.
func (c *Config) Snapshot() fileConfig {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data
}

// Getter runtime.

func (c *Config) ChannelID() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.ChannelID
}

func (c *Config) Prefix() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Prefix
}

func (c *Config) Action() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Action
}

func (c *Config) Status() bool {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Status
}

func (c *Config) StatusGems() bool {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.StatusGems
}

func (c *Config) CaptchaActive() bool {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.CaptchaStatus
}

func (c *Config) GatewaySettings() Gateway {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Gateway
}

func (c *Config) FarmSettings() Farm {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Farm
}

func (c *Config) SolverSettings() Solver {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.data.Solver
}
