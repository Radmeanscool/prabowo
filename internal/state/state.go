package state

import (
	"sync"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

const memberKeySep = "\x1f"

// Store adalah cache minimal yang aman dipakai lintas goroutine.
type Store struct {
	mu        sync.RWMutex
	self      rest.User
	hasSelf   bool
	sessionID string
	channels  map[string]rest.Channel
	members   map[string]string
	guilds    map[string]string
}

// New membuat Store kosong.
func New() *Store {
	return &Store{
		channels: make(map[string]rest.Channel),
		members:  make(map[string]string),
		guilds:   make(map[string]string),
	}
}

// SetSelf menyimpan akun yang sedang login.
func (s *Store) SetSelf(user rest.User) {
	s.mu.Lock()
	s.self = user
	s.hasSelf = true
	s.mu.Unlock()
}

// Self mengembalikan akun yang sedang login.
func (s *Store) Self() (rest.User, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.self, s.hasSelf
}

// SetSessionID menyimpan session gateway (dipakai untuk klik tombol).
func (s *Store) SetSessionID(id string) {
	s.mu.Lock()
	s.sessionID = id
	s.mu.Unlock()
}

// SessionID mengembalikan session gateway terakhir.
func (s *Store) SessionID() string {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.sessionID
}

// SetChannel menyimpan/memperbarui metadata channel.
func (s *Store) SetChannel(ch rest.Channel) {
	if ch.ID == "" {
		return
	}
	s.mu.Lock()
	s.channels[ch.ID] = ch
	s.mu.Unlock()
}

// Channel mengambil metadata channel dari cache.
func (s *Store) Channel(id string) (rest.Channel, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	ch, ok := s.channels[id]
	return ch, ok
}

// SetGuild menyimpan nama guild.
func (s *Store) SetGuild(id, name string) {
	if id == "" {
		return
	}
	s.mu.Lock()
	s.guilds[id] = name
	s.mu.Unlock()
}

// GuildName mengambil nama guild dari cache.
func (s *Store) GuildName(id string) (string, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	name, ok := s.guilds[id]
	return name, ok
}

// SetMemberDisplay menyimpan nama tampilan member per guild.
func (s *Store) SetMemberDisplay(guildID, userID, name string) {
	if guildID == "" || userID == "" || name == "" {
		return
	}
	s.mu.Lock()
	s.members[guildID+memberKeySep+userID] = name
	s.mu.Unlock()
}

// MemberDisplay mengambil nama tampilan member per guild.
func (s *Store) MemberDisplay(guildID, userID string) (string, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	name, ok := s.members[guildID+memberKeySep+userID]
	return name, ok
}
