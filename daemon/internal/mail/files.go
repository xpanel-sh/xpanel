package mail

import (
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"

	xenv "xpanel/internal/env"
)

type FileWriter struct {
	basePath string
}

func NewFileWriter() *FileWriter {
	return &FileWriter{basePath: xenv.BasePath()}
}

func (w *FileWriter) Write(accounts map[string]map[string]any) error {
	mailDir := filepath.Join(w.basePath, "runtime", "daemon", "mail")
	if err := os.MkdirAll(mailDir, 0750); err != nil {
		return err
	}

	domains := map[string]bool{}
	mailboxes := []string{}
	quotas := []string{}

	emails := make([]string, 0, len(accounts))
	for email := range accounts {
		emails = append(emails, email)
	}
	sort.Strings(emails)

	for _, email := range emails {
		account := accounts[email]
		domain := strings.ToLower(strings.TrimSpace(asString(account["domain"])))
		address := strings.ToLower(strings.TrimSpace(asString(account["email"])))
		if domain == "" || address == "" || !strings.Contains(address, "@") {
			continue
		}

		localPart := strings.SplitN(address, "@", 2)[0]
		domains[domain] = true
		mailboxes = append(mailboxes, fmt.Sprintf("%s %s/%s/", address, domain, localPart))
		quotas = append(quotas, fmt.Sprintf("%s %dM", address, asInt(account["quota_mb"])))
	}

	domainList := make([]string, 0, len(domains))
	for domain := range domains {
		domainList = append(domainList, domain)
	}
	sort.Strings(domainList)

	if err := writeLines(filepath.Join(mailDir, "virtual_domains"), domainList); err != nil {
		return err
	}
	if err := writeLines(filepath.Join(mailDir, "virtual_mailboxes"), mailboxes); err != nil {
		return err
	}
	return writeLines(filepath.Join(mailDir, "virtual_quotas"), quotas)
}

func writeLines(path string, lines []string) error {
	tmp := path + ".tmp"
	if err := os.WriteFile(tmp, []byte(strings.Join(lines, "\n")+"\n"), 0640); err != nil {
		return err
	}
	return os.Rename(tmp, path)
}

func asString(value any) string {
	if value == nil {
		return ""
	}
	return fmt.Sprintf("%v", value)
}

func asInt(value any) int {
	switch typed := value.(type) {
	case int:
		return typed
	case int64:
		return int(typed)
	case float64:
		return int(typed)
	case string:
		var parsed int
		_, _ = fmt.Sscanf(typed, "%d", &parsed)
		return parsed
	default:
		return 0
	}
}
