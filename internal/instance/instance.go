// Package instance mencegah lebih dari satu proses bot berjalan bersamaan.
//
// Dua proses yang jalan bareng menyebabkan farming dobel dan race saat
// menulis config.json (rename file temp yang sama).
package instance

import (
	"errors"
	"fmt"
	"os"
	"strconv"

	"github.com/gofrs/flock"
)

// ErrAlreadyRunning dikembalikan bila lock sudah dipegang proses lain.
var ErrAlreadyRunning = errors.New("instance: another process is already running")

// Lock adalah file lock yang dilepas saat Release.
type Lock struct {
	fl   *flock.Flock
	path string
}

// Acquire mencoba mengunci file lock. Bila sudah dipegang proses lain,
// mengembalikan ErrAlreadyRunning.
func Acquire(path string) (*Lock, error) {
	if path == "" {
		return nil, errors.New("instance: empty lock path")
	}

	fl := flock.New(path)
	ok, err := fl.TryLock()
	if err != nil {
		return nil, fmt.Errorf("instance: lock %s: %w", path, err)
	}
	if !ok {
		return nil, ErrAlreadyRunning
	}

	// Tulis PID untuk memudahkan debugging (best-effort).
	_ = os.WriteFile(path, []byte(strconv.Itoa(os.Getpid())+"\n"), 0o600)
	return &Lock{fl: fl, path: path}, nil
}

// Path mengembalikan lokasi file lock.
func (l *Lock) Path() string {
	if l == nil {
		return ""
	}
	return l.path
}

// Release melepas lock. Aman dipanggil lebih dari sekali.
func (l *Lock) Release() error {
	if l == nil || l.fl == nil {
		return nil
	}
	fl := l.fl
	l.fl = nil
	return fl.Unlock()
}
