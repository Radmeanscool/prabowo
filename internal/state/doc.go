// Package state menyimpan cache minimal di memori, pengganti cache besar
// discord.js-selfbot-v13.
//
// Isi:
//   - current user (id, username, tag)
//   - channel seperlunya (id, name, type, guild id)
//   - member display name per guild (fallback: username)
//
// Semua akses harus aman dari goroutine berbeda (sync.RWMutex).
package state
