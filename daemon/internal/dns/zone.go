package dns

import (
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"time"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

type ZoneWriter struct {
	basePath string
}

func NewZoneWriter() *ZoneWriter {
	return &ZoneWriter{basePath: xenv.BasePath()}
}

func (z *ZoneWriter) WriteZone(domain string, records map[string]map[string]any) error {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	if domain == "" {
		return fmt.Errorf("domain is required")
	}

	zoneDir := filepath.Join(z.basePath, "runtime", "daemon", "dns", "zones")
	if err := os.MkdirAll(zoneDir, 0750); err != nil {
		return err
	}

	lines := []string{
		"$ORIGIN " + domain + ".",
		"$TTL 3600",
		fmt.Sprintf("@ IN SOA ns1.%s. admin.%s. (%s 3600 900 1209600 3600)", domain, domain, time.Now().UTC().Format("2006010215")),
	}

	for _, record := range recordsForDomain(domain, records) {
		line, err := renderRecord(record)
		if err != nil {
			return err
		}
		if line != "" {
			lines = append(lines, line)
		}
	}

	path := filepath.Join(zoneDir, safeFileName(domain)+".zone")
	tmp := path + ".tmp"
	if err := os.WriteFile(tmp, []byte(strings.Join(lines, "\n")+"\n"), 0640); err != nil {
		return err
	}

	return os.Rename(tmp, path)
}

func (z *ZoneWriter) DeleteZoneIfEmpty(domain string, records map[string]map[string]any) error {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	if domain == "" || len(recordsForDomain(domain, records)) > 0 {
		return nil
	}

	path := filepath.Join(z.basePath, "runtime", "daemon", "dns", "zones", safeFileName(domain)+".zone")
	if err := os.Remove(path); err != nil && !os.IsNotExist(err) {
		return err
	}

	return nil
}

func HasRecords(domain string, records map[string]map[string]any) bool {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	return len(recordsForDomain(domain, records)) > 0
}

func recordsForDomain(domain string, records map[string]map[string]any) []map[string]any {
	filtered := []map[string]any{}
	for _, record := range records {
		if strings.TrimSuffix(strings.ToLower(asString(record["domain"])), ".") == domain {
			filtered = append(filtered, record)
		}
	}

	sort.Slice(filtered, func(i, j int) bool {
		left := asString(filtered[i]["type"]) + asString(filtered[i]["name"]) + asString(filtered[i]["value"])
		right := asString(filtered[j]["type"]) + asString(filtered[j]["name"]) + asString(filtered[j]["value"])
		return left < right
	})

	return filtered
}

func renderRecord(record map[string]any) (string, error) {
	req := model.DNSRecordRequest{
		Domain: asString(record["domain"]),
		Type:   strings.ToUpper(asString(record["type"])),
		Name:   asString(record["name"]),
		Value:  asString(record["value"]),
		TTL:    asInt(record["ttl"]),
	}

	if req.Name == "" || req.Type == "" || req.Value == "" {
		return "", fmt.Errorf("dns record requires name, type and value")
	}
	if req.TTL <= 0 {
		req.TTL = 3600
	}

	name := normalizeName(req.Name)
	value := normalizeValue(req.Type, req.Value)
	if req.Type == "MX" {
		priority := asInt(record["priority"])
		if priority <= 0 {
			priority = 10
		}
		value = fmt.Sprintf("%d %s", priority, normalizeValue(req.Type, req.Value))
	}

	return fmt.Sprintf("%s %d IN %s %s", name, req.TTL, req.Type, value), nil
}

func normalizeName(name string) string {
	name = strings.TrimSpace(name)
	if name == "@" {
		return "@"
	}
	return strings.TrimSuffix(name, ".")
}

func normalizeValue(recordType string, value string) string {
	value = strings.TrimSpace(value)
	switch strings.ToUpper(recordType) {
	case "CNAME", "MX", "NS", "SRV":
		if !strings.HasSuffix(value, ".") {
			value += "."
		}
	case "TXT":
		value = `"` + strings.ReplaceAll(strings.Trim(value, `"`), `"`, `\"`) + `"`
	}
	return value
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

func safeFileName(value string) string {
	return strings.Map(func(r rune) rune {
		if (r >= 'a' && r <= 'z') || (r >= '0' && r <= '9') || r == '-' || r == '.' {
			return r
		}
		return '-'
	}, strings.ToLower(value))
}
