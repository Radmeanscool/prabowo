package events

import (
	"context"
	"path/filepath"
	"testing"
	"time"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/config"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/gateway"
	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/state"
)

func testConfig(t *testing.T) *config.Config {
	t.Helper()
	cfg, err := config.Load(filepath.Join(t.TempDir(), "config.json"))
	if err != nil {
		t.Fatalf("config.Load: %v", err)
	}
	return cfg
}

func TestMessageCreateUpdatesState(t *testing.T) {
	st := state.New()
	d := New(Options{Config: testConfig(t), State: st})

	withNick := `{"id":"m1","channel_id":"123","guild_id":"7","content":"owo h",
		"author":{"id":"42","username":"tester","global_name":"Tester"},
		"member":{"nick":"Nick"},"type":0}`
	if err := d.handleMessageCreate(context.Background(), gateway.Event{
		Type: "MESSAGE_CREATE", Data: []byte(withNick),
	}); err != nil {
		t.Fatalf("handleMessageCreate: %v", err)
	}
	if name, ok := st.MemberDisplay("7", "42"); !ok || name != "Nick" {
		t.Fatalf("display name tidak memakai nick: %q ok=%v", name, ok)
	}

	noNick := `{"id":"m2","channel_id":"123","guild_id":"7","content":"owo",
		"author":{"id":"42","username":"tester","global_name":"Tester"},"type":0}`
	if err := d.handleMessageCreate(context.Background(), gateway.Event{
		Type: "MESSAGE_CREATE", Data: []byte(noNick),
	}); err != nil {
		t.Fatalf("handleMessageCreate: %v", err)
	}
	if name, _ := st.MemberDisplay("7", "42"); name != "Tester" {
		t.Fatalf("display name tidak memakai global name: %q", name)
	}

	invalid := `{oops`
	if err := d.handleMessageCreate(context.Background(), gateway.Event{
		Type: "MESSAGE_CREATE", Data: []byte(invalid),
	}); err == nil {
		t.Fatal("expected error untuk payload rusak")
	}
}

func TestGuildCreateCachesChannels(t *testing.T) {
	st := state.New()
	d := New(Options{Config: testConfig(t), State: st})

	payload := `{"id":"7","name":"Server Uji","channels":[
		{"id":"123","name":"farming","type":0},
		{"id":"456","name":"umum","type":0}
	]}`
	if err := d.handleGuildCreate(context.Background(), gateway.Event{
		Type: "GUILD_CREATE", Data: []byte(payload),
	}); err != nil {
		t.Fatalf("handleGuildCreate: %v", err)
	}

	if name, ok := st.GuildName("7"); !ok || name != "Server Uji" {
		t.Fatalf("guild tidak tersimpan: %q ok=%v", name, ok)
	}
	ch, ok := st.Channel("123")
	if !ok || ch.Name != "farming" || ch.GuildID != "7" {
		t.Fatalf("channel tidak tersimpan: %+v ok=%v", ch, ok)
	}
}

func TestChannelUpsert(t *testing.T) {
	st := state.New()
	d := New(Options{Config: testConfig(t), State: st})

	payload := `{"id":"999","name":"dm","type":1,"recipients":[{"id":"42"}]}`
	if err := d.handleChannelUpsert(context.Background(), gateway.Event{
		Type: "CHANNEL_CREATE", Data: []byte(payload),
	}); err != nil {
		t.Fatalf("handleChannelUpsert: %v", err)
	}

	ch, ok := st.Channel("999")
	if !ok || ch.Name != "dm" || !ch.IsDM() {
		t.Fatalf("channel DM tidak tersimpan: %+v ok=%v", ch, ok)
	}
}

func TestEnqueueRunDispatches(t *testing.T) {
	d := New(Options{Config: testConfig(t), State: state.New()})

	got := make(chan gateway.Event, 1)
	d.On("CUSTOM", func(_ context.Context, e gateway.Event) error {
		got <- e
		return nil
	})

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	go d.Run(ctx)

	d.Enqueue(gateway.Event{Type: "CUSTOM", Data: []byte(`{"x":1}`)})
	select {
	case e := <-got:
		if e.Type != "CUSTOM" {
			t.Fatalf("event salah: %+v", e)
		}
	case <-time.After(time.Second):
		t.Fatal("handler tidak dipanggil")
	}

	d.Enqueue(gateway.Event{Type: ""})
	d.Enqueue(gateway.Event{Type: "TIDAK_DIKENAL"})
	time.Sleep(20 * time.Millisecond)
}

func TestHandlerPanicDoesNotStopDispatcher(t *testing.T) {
	d := New(Options{Config: testConfig(t), State: state.New()})

	ok := make(chan struct{}, 1)
	d.On("BOOM", func(context.Context, gateway.Event) error { panic("boom") })
	d.On("OK", func(context.Context, gateway.Event) error {
		ok <- struct{}{}
		return nil
	})

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	go d.Run(ctx)

	d.Enqueue(gateway.Event{Type: "BOOM"})
	d.Enqueue(gateway.Event{Type: "OK"})

	select {
	case <-ok:
	case <-time.After(time.Second):
		t.Fatal("dispatcher berhenti setelah handler panic")
	}
}
