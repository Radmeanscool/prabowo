// Package solver adalah klien layanan captcha (menggantikan solver.js).
//
// Tanggung jawab:
//   - submit challenge (sitekey/url/host) lalu polling job sampai selesai
//   - job yang "failed" dicoba sekali lagi dengan job baru
//   - timeout per request
//
// API key diambil dari config/env, tidak pernah di-log.
package solver
