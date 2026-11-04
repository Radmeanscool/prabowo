package state

import (
	"fmt"
	"sync"
	"testing"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/rest"
)

func TestSelf(t *testing.T) {
	s := New()
	if _, ok := s.Self(); ok {
		t.Fatal("Store baru tidak boleh punya self")
	}

	s.SetSelf(rest.User{ID: "42", Username: "tester"})
	self, ok := s.Self()
	if !ok || self.ID != "42" || self.Username != "tester" {
		t.Fatalf("Self salah: %+v ok=%v", self, ok)
	}
}

func TestChannels(t *testing.T) {
	s := New()
	s.SetChannel(rest.Channel{})
	if _, ok := s.Channel(""); ok {
		t.Fatal("channel dengan id kosong tidak boleh disimpan")
	}

	s.SetChannel(rest.Channel{ID: "123", Name: "farming", Type: 0})
	ch, ok := s.Channel("123")
	if !ok || ch.Name != "farming" {
		t.Fatalf("Channel salah: %+v ok=%v", ch, ok)
	}

	s.SetChannel(rest.Channel{ID: "123", Name: "baru", Type: 0})
	ch, _ = s.Channel("123")
	if ch.Name != "baru" {
		t.Fatalf("channel tidak diperbarui: %+v", ch)
	}
}

func TestGuildAndMember(t *testing.T) {
	s := New()
	s.SetGuild("7", "Server Uji")
	if name, ok := s.GuildName("7"); !ok || name != "Server Uji" {
		t.Fatalf("GuildName salah: %q ok=%v", name, ok)
	}
	if _, ok := s.GuildName("8"); ok {
		t.Fatal("guild tak dikenal harus false")
	}

	s.SetMemberDisplay("7", "42", "Tester Nick")
	if name, ok := s.MemberDisplay("7", "42"); !ok || name != "Tester Nick" {
		t.Fatalf("MemberDisplay salah: %q ok=%v", name, ok)
	}
	if _, ok := s.MemberDisplay("8", "42"); ok {
		t.Fatal("member guild lain harus false")
	}

	s.SetMemberDisplay("", "42", "x")
	s.SetMemberDisplay("7", "", "x")
	s.SetMemberDisplay("7", "42", "")
}

func TestConcurrentAccess(t *testing.T) {
	s := New()
	var wg sync.WaitGroup
	for i := 0; i < 50; i++ {
		wg.Add(1)
		go func(n int) {
			defer wg.Done()
			id := fmt.Sprintf("%d", n)
			s.SetChannel(rest.Channel{ID: id, Name: "ch" + id})
			s.SetMemberDisplay("7", id, "user"+id)
			s.SetGuild(id, "guild"+id)
			s.SetSelf(rest.User{ID: id})
			s.Channel(id)
			s.MemberDisplay("7", id)
			s.GuildName(id)
			s.Self()
		}(i)
	}
	wg.Wait()
}
