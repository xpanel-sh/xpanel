package config

import (
	"os"
	"strings"
)

type Config struct {
	Port string
}

func Load() Config {
	port := strings.TrimSpace(os.Getenv("XPANEL_DAEMON_PORT"))
	if port == "" {
		port = "7070"
	}

	return Config{
		Port: port,
	}
}
