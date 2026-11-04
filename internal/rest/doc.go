// Package rest adalah klien HTTP Discord yang dipakai bot.
//
// Endpoint minimum:
//   - GET  /users/@me
//   - GET  /channels/{id}
//   - POST /channels/{id}/messages
//   - POST /channels/{id}/typing
//   - POST /users/@me/channels  (buat DM)
//
// Tanggung jawab tambahan: header autentikasi user token, penanganan
// rate limit (429 + retry_after), dan timeout request.
package rest
