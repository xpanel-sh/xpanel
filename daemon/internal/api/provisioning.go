package api

import (
	"bufio"
	"encoding/json"
	"net/http"
	"os"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"syscall"

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
		"system":      systemMetrics(base),
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

func systemMetrics(base string) map[string]any {
	return map[string]any{
		"cpu_percent": cpuPercent(),
		"memory":      memoryMetrics(),
		"disk":        diskMetrics(base),
	}
}

func cpuPercent() float64 {
	data, err := os.ReadFile("/proc/loadavg")
	if err != nil {
		return 0
	}
	fields := strings.Fields(string(data))
	if len(fields) == 0 {
		return 0
	}
	load, err := strconv.ParseFloat(fields[0], 64)
	if err != nil {
		return 0
	}
	cpus := runtime.NumCPU()
	if cpus < 1 {
		cpus = 1
	}
	percent := (load / float64(cpus)) * 100
	if percent > 100 {
		return 100
	}
	if percent < 0 {
		return 0
	}
	return percent
}

func memoryMetrics() map[string]any {
	file, err := os.Open("/proc/meminfo")
	if err != nil {
		var stats runtime.MemStats
		runtime.ReadMemStats(&stats)
		return map[string]any{
			"total": stats.Sys,
			"used":  stats.Alloc,
			"free":  stats.Sys - stats.Alloc,
			"percent": func() float64 {
				if stats.Sys == 0 {
					return 0
				}
				return (float64(stats.Alloc) / float64(stats.Sys)) * 100
			}(),
		}
	}
	defer file.Close()

	values := map[string]uint64{}
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		parts := strings.Fields(scanner.Text())
		if len(parts) < 2 {
			continue
		}
		key := strings.TrimSuffix(parts[0], ":")
		value, err := strconv.ParseUint(parts[1], 10, 64)
		if err == nil {
			values[key] = value * 1024
		}
	}
	total := values["MemTotal"]
	available := values["MemAvailable"]
	if available == 0 {
		available = values["MemFree"] + values["Buffers"] + values["Cached"]
	}
	used := uint64(0)
	if total > available {
		used = total - available
	}
	percent := 0.0
	if total > 0 {
		percent = (float64(used) / float64(total)) * 100
	}
	return map[string]any{
		"total":   total,
		"used":    used,
		"free":    available,
		"percent": percent,
	}
}

func diskMetrics(path string) map[string]any {
	var stat syscall.Statfs_t
	if err := syscall.Statfs(path, &stat); err != nil {
		return map[string]any{"total": 0, "used": 0, "free": 0, "percent": 0}
	}
	total := stat.Blocks * uint64(stat.Bsize)
	free := stat.Bavail * uint64(stat.Bsize)
	used := uint64(0)
	if total > free {
		used = total - free
	}
	percent := 0.0
	if total > 0 {
		percent = (float64(used) / float64(total)) * 100
	}
	return map[string]any{
		"total":   total,
		"used":    used,
		"free":    free,
		"percent": percent,
	}
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
