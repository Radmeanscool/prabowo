package instance

import (
	"errors"
	"path/filepath"
	"testing"
)

func TestAcquireRejectsSecond(t *testing.T) {
	path := filepath.Join(t.TempDir(), "bot.lock")

	first, err := Acquire(path)
	if err != nil {
		t.Fatalf("Acquire pertama: %v", err)
	}

	second, err := Acquire(path)
	if !errors.Is(err, ErrAlreadyRunning) {
		t.Fatalf("expected ErrAlreadyRunning, dapat %v", err)
	}
	if second != nil {
		t.Fatal("lock kedua tidak boleh didapat")
	}

	if err := first.Release(); err != nil {
		t.Fatalf("Release: %v", err)
	}

	third, err := Acquire(path)
	if err != nil {
		t.Fatalf("Acquire setelah release: %v", err)
	}
	if err := third.Release(); err != nil {
		t.Fatalf("Release kedua: %v", err)
	}
}

func TestAcquireEmptyPath(t *testing.T) {
	if _, err := Acquire(""); err == nil {
		t.Fatal("expected error path kosong")
	}
}

func TestReleaseTwiceAndNil(t *testing.T) {
	path := filepath.Join(t.TempDir(), "bot.lock")
	l, err := Acquire(path)
	if err != nil {
		t.Fatalf("Acquire: %v", err)
	}
	if err := l.Release(); err != nil {
		t.Fatalf("Release: %v", err)
	}
	if err := l.Release(); err != nil {
		t.Fatalf("Release kedua harus aman: %v", err)
	}
	var nilLock *Lock
	if err := nilLock.Release(); err != nil {
		t.Fatalf("Release nil harus aman: %v", err)
	}
}
