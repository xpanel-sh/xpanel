package auth

import (
	"os"
	"strings"
)

func Valid(rToken string) bool {
	rToken = strings.TrimSpace(rToken)
	if rToken == "" {
		return false
	}

	envToken := strings.TrimSpace(os.Getenv("XPANEL_DAEMON_TOKEN"))
	if envToken != "" {
		return rToken == envToken
	}

	data, _ := os.ReadFile("/opt/xpanel/config/daemon.key")
	fileToken := strings.TrimSpace(string(data))
	return fileToken != "" && fileToken == rToken
}
