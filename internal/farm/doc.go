// Package farm mengatur loop farming dan orkestrasi restart.
//
// Menggantikan setInterval/setTimeout + pendingRestartTimer di index.js:
//   - ticker interval + jitter acak
//   - kirim command (hunt/battle/owo/action) lewat rest
//   - jeda otomatis saat captchaStatus aktif
//   - restart aman (destroy + login ulang) dengan single-flight
package farm
