package api

import (
	"fmt"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

func handlePhpIniWrite(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req model.PhpIniRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Domain) == "" {
		http.Error(w, "domain is required", http.StatusBadRequest)
		return
	}

	iniPath := filepath.Join(xenv.BasePath(), "runtime", "sites", req.Domain, "php.ini")

	if err := os.MkdirAll(filepath.Dir(iniPath), 0755); err != nil {
		http.Error(w, "failed to create directory: "+err.Error(), http.StatusInternalServerError)
		return
	}

	content := buildPhpIni(req.Options)
	if err := os.WriteFile(iniPath, []byte(content), 0644); err != nil {
		http.Error(w, "failed to write php.ini: "+err.Error(), http.StatusInternalServerError)
		return
	}

	writeJSON(w, map[string]string{"status": "written", "path": iniPath})
}

func buildPhpIni(options map[string]string) string {
	var sb strings.Builder
	sb.WriteString("[PHP]\n")
	allowed := []string{
		"memory_limit",
		"upload_max_filesize",
		"post_max_size",
		"max_execution_time",
		"max_input_time",
		"display_errors",
		"error_reporting",
	}
	for _, key := range allowed {
		if val, ok := options[key]; ok && strings.TrimSpace(val) != "" {
			sb.WriteString(fmt.Sprintf("%s = %s\n", key, val))
		}
	}
	return sb.String()
}
