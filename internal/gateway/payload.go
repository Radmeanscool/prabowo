package gateway

import (
	"crypto/rand"
	"encoding/binary"
	"encoding/json"
	"fmt"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

// OpCode adalah opcode payload gateway Discord.
type OpCode int

const (
	OpDispatch               OpCode = 0
	OpHeartbeat              OpCode = 1
	OpIdentify               OpCode = 2
	OpPresenceUpdate         OpCode = 3
	OpVoiceStateUpdate       OpCode = 4
	OpResume                 OpCode = 6
	OpReconnect              OpCode = 7
	OpRequestGuildMembers    OpCode = 8
	OpInvalidSession         OpCode = 9
	OpHello                  OpCode = 10
	OpHeartbeatAck           OpCode = 11
	OpGuildSubscriptions     OpCode = 14
	OpGuildSubscriptionsBulk OpCode = 37
)

// Payload adalah amplop semua frame gateway.
type Payload struct {
	Op OpCode          `json:"op"`
	D  json.RawMessage `json:"d"`
	S  *int            `json:"s,omitempty"`
	T  string          `json:"t,omitempty"`
}

// Hello adalah isi payload op 10.
type Hello struct {
	HeartbeatInterval int `json:"heartbeat_interval"`
}

// GuildInfo adalah subset guild pada READY/GUILD_CREATE.
type GuildInfo struct {
	ID          string         `json:"id"`
	Name        string         `json:"name"`
	Unavailable bool           `json:"unavailable"`
	Channels    []rest.Channel `json:"channels"`
}

// Ready adalah isi dispatch READY.
type Ready struct {
	Version          int            `json:"v"`
	SessionID        string         `json:"session_id"`
	ResumeGatewayURL string         `json:"resume_gateway_url"`
	User             rest.User      `json:"user"`
	Guilds           []GuildInfo    `json:"guilds"`
	PrivateChannels  []rest.Channel `json:"private_channels"`
	Shard            []int          `json:"shard"`
}

// Event adalah dispatch yang diteruskan ke lapisan events.
type Event struct {
	Type     string
	Data     json.RawMessage
	Sequence int
}

type resumeData struct {
	Token     string `json:"token"`
	SessionID string `json:"session_id"`
	Seq       int    `json:"seq"`
}

type clientState struct {
	GuildVersions map[string]string `json:"guild_versions"`
}

type presenceData struct {
	Activities []any  `json:"activities"`
	AFK        bool   `json:"afk"`
	Since      int    `json:"since"`
	Status     string `json:"status"`
}

type identifyData struct {
	Token        string         `json:"token"`
	Capabilities int            `json:"capabilities"`
	Properties   map[string]any `json:"properties"`
	Compress     bool           `json:"compress"`
	Presence     presenceData   `json:"presence"`
	ClientState  clientState    `json:"client_state"`
}

// defaultProperties meniru client properties discord.js-selfbot-v13.
func defaultProperties(userAgent string) map[string]any {
	return map[string]any{
		"os":                          "Windows",
		"browser":                     "Discord Client",
		"release_channel":             "stable",
		"client_version":              "1.0.9210",
		"os_version":                  "10.0.19044",
		"os_arch":                     "x64",
		"app_arch":                    "x64",
		"system_locale":               "en-US",
		"has_client_mods":             false,
		"client_launch_id":            newUUID(),
		"browser_user_agent":          userAgent,
		"browser_version":             "35.3.0",
		"os_sdk_version":              "19044",
		"client_build_number":         455964,
		"native_build_number":         69976,
		"client_event_source":         nil,
		"launch_signature":            newUUID(),
		"client_heartbeat_session_id": newUUID(),
		"client_app_state":            "focused",
	}
}

func newUUID() string {
	var b [16]byte
	if _, err := rand.Read(b[:]); err != nil {
		binary.BigEndian.PutUint64(b[:8], uint64(time.Now().UnixNano()))
	}
	b[6] = (b[6] & 0x0f) | 0x40
	b[8] = (b[8] & 0x3f) | 0x80
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:16])
}
