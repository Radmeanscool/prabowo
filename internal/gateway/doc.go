// Package gateway mengimplementasikan Discord Gateway (WebSocket) minimal
// khusus user token.
//
// Cakupan:
//   - handshake: HELLO -> IDENTIFY -> READY
//   - heartbeat berkala (op 1) + ACK (op 11)
//   - reconnect + RESUME (op 6)
//   - zlib-stream decompression (compress/zlib)
//   - dispatch event yang dipakai: READY, MESSAGE_CREATE
//
// Sengaja tidak mencakup voice, sharding, presence rotation, atau cache
// guild penuh.
package gateway
