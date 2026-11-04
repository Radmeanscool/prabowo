// Package events memetakan dispatch gateway ke handler, menggantikan
// client.on(...) di workspace Node.
//
// Handler yang diport bertahap:
//   - ready     (events/ready.js)
//   - owo       (events/owo.js: ah/ab, duel, collector 20s)
//   - autogems  (events/autogems.js)
//   - captcha   (events/captcha.js: DM, attachment, embed)
package events
