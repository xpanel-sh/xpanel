package api

import (
	"encoding/json"
	"net/http"
	"os"
	"path/filepath"

	xenv "xpanel/internal/env"
	"xpanel/internal/state"
	model "xpanel/internal/types"
)

var daemonStore = state.NewStore()

func payloadFrom(value any) map[string]any {
	data, err := json.Marshal(value)
	if err != nil {
		return map[string]any{}
	}

	payload := map[string]any{}
	if err := json.Unmarshal(data, &payload); err != nil {
		return map[string]any{}
	}

	return payload
}

func accepted(w http.ResponseWriter, kind, action, resource, message string, payload map[string]any) {
	op, err := daemonStore.Record(kind, action, "accepted", resource, message, payload)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	writeJSON(w, model.ActionResponse{
		Status:      "accepted",
		Message:     message,
		OperationID: op.ID,
	})
}

func handleOperationsList(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	operations, err := daemonStore.Operations()
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	writeJSON(w, operations)
}

func handleRuntimeStatus(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	status, err := runtimeStatus()
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	writeJSON(w, status)
}

func runtimeStatus() (map[string]any, error) {
	base := xenv.BasePath()
	runtimeDir := filepath.Join(base, "runtime", "daemon")

	mailAccounts, err := daemonStore.Map("mail_accounts")
	if err != nil {
		return nil, err
	}
	dnsRecords, err := daemonStore.Map("dns_records")
	if err != nil {
		return nil, err
	}
	databases, err := daemonStore.Map("databases")
	if err != nil {
		return nil, err
	}
	operations, err := daemonStore.Operations()
	if err != nil {
		return nil, err
	}

	return map[string]any{
		"base_path":   base,
		"runtime_dir": runtimeDir,
		"resources": map[string]int{
			"mail_accounts": len(mailAccounts),
			"dns_records":   len(dnsRecords),
			"databases":     len(databases),
			"operations":    len(operations),
		},
		"artifacts": map[string]any{
			"dns_zones": map[string]any{
				"path":   filepath.Join(runtimeDir, "dns", "zones"),
				"exists": pathExists(filepath.Join(runtimeDir, "dns", "zones")),
				"count":  countFiles(filepath.Join(runtimeDir, "dns", "zones"), ".zone"),
			},
			"mail": map[string]any{
				"path": filepath.Join(runtimeDir, "mail"),
				"files": map[string]bool{
					"virtual_domains":   pathExists(filepath.Join(runtimeDir, "mail", "virtual_domains")),
					"virtual_mailboxes": pathExists(filepath.Join(runtimeDir, "mail", "virtual_mailboxes")),
					"virtual_quotas":    pathExists(filepath.Join(runtimeDir, "mail", "virtual_quotas")),
				},
			},
		},
	}, nil
}

func pathExists(path string) bool {
	_, err := os.Stat(path)
	return err == nil
}

func countFiles(dir string, suffix string) int {
	entries, err := os.ReadDir(dir)
	if err != nil {
		return 0
	}

	count := 0
	for _, entry := range entries {
		if !entry.IsDir() && filepath.Ext(entry.Name()) == suffix {
			count++
		}
	}
	return count
}
