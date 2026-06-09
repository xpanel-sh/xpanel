package env

import (
	"bufio"
	"os"
	"strings"
)

func BasePath() string {
	base := os.Getenv("XPANEL_BASE")
	if base == "" {
		base = "/opt/xpanel"
	}
	return base
}

func ReadFile(path string) map[string]string {
	values := map[string]string{}

	file, err := os.Open(path)
	if err != nil {
		return values
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if line == "" || strings.HasPrefix(line, "#") || !strings.Contains(line, "=") {
			continue
		}

		parts := strings.SplitN(line, "=", 2)
		key := strings.TrimSpace(parts[0])
		value := strings.TrimSpace(parts[1])
		value = strings.Trim(value, `"'`)

		if key != "" {
			values[key] = value
		}
	}

	return values
}
