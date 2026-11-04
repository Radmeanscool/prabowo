// Package config memuat, memvalidasi, dan menyimpan konfigurasi bot.
//
// Sumber utama: config.json di root workspace. Data sensitif (token Discord,
// accountId, API key captopia) TIDAK disimpan di sini, melainkan di .env
// (lihat internal/env). Gunakan config.example.json sebagai template.
//
// Tanggung jawab:
//   - default value (prefix, action, gateway version, interval farm)
//   - error jelas jika channelId tidak valid
//   - set/get runtime seperti channelId dan captchaStatus
package config
