package main

import (
	"os"
	"syscall"

	"github.com/Pangleran/discordgo-owoselfbot-v1/internal/logger"
)

// reexec mengganti proses saat ini dengan binary yang sama. Dipakai untuk
// login ulang setelah token di config.json diganti (meniru relogin Node).
func reexec() {
	exe, err := os.Executable()
	if err != nil {
		logger.Error("TOKEN", "failed to resolve binary path:", err)
		os.Exit(1)
	}

	logger.Log("TOKEN", "re-login with new token...")
	if err := syscall.Exec(exe, os.Args, os.Environ()); err != nil {
		logger.Error("TOKEN", "failed to restart process:", err)
		os.Exit(1)
	}
}
