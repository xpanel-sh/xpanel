package api

import (
	"fmt"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strings"

	model "xpanel/internal/types"
	xenv "xpanel/internal/env"
)

// handleSSLIssue issues or renews an SSL certificate for a domain.
// Supports two modes:
//   - "cloudflare": DNS-01 via Cloudflare API (supports wildcard)
//   - "http":       HTTP-01 via existing web server (no wildcard)
func handleSSLIssue(w http.ResponseWriter, r *http.Request) {
	var req model.SSLIssueRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.Domain) == "" {
		http.Error(w, "domain is required", http.StatusBadRequest)
		return
	}

	acme := findAcmeSh()
	if acme == "" {
		http.Error(w, "acme.sh not found — run: xpanel components install ssl", http.StatusServiceUnavailable)
		return
	}

	certDir := filepath.Join(xenv.BasePath(), "runtime", "ssl", req.Domain)
	if err := os.MkdirAll(certDir, 0750); err != nil {
		http.Error(w, "cannot create cert directory: "+err.Error(), http.StatusInternalServerError)
		return
	}

	var args []string

	switch req.Mode {
	case "cloudflare":
		if req.CFToken == "" {
			http.Error(w, "cf_token is required for cloudflare mode", http.StatusBadRequest)
			return
		}
		os.Setenv("CF_Token", req.CFToken)
		defer os.Unsetenv("CF_Token")

		args = []string{
			"--issue",
			"-d", req.Domain,
			"-d", "*." + req.Domain,
			"--dns", "dns_cf",
			"--cert-file", filepath.Join(certDir, "cert.pem"),
			"--key-file", filepath.Join(certDir, "key.pem"),
			"--ca-file", filepath.Join(certDir, "ca.pem"),
			"--fullchain-file", filepath.Join(certDir, "fullchain.pem"),
			"--reloadcmd", "echo 'cert renewed'",
		}

	case "http", "":
		webroot := req.Webroot
		if webroot == "" {
			webroot = filepath.Join(xenv.BasePath(), "runtime", "sites", req.Domain, "public")
		}
		args = []string{
			"--issue",
			"-d", req.Domain,
			"-w", webroot,
			"--cert-file", filepath.Join(certDir, "cert.pem"),
			"--key-file", filepath.Join(certDir, "key.pem"),
			"--ca-file", filepath.Join(certDir, "ca.pem"),
			"--fullchain-file", filepath.Join(certDir, "fullchain.pem"),
		}

	default:
		http.Error(w, fmt.Sprintf("unknown mode: %s (use cloudflare or http)", req.Mode), http.StatusBadRequest)
		return
	}

	cmd := exec.Command(acme, args...)
	cmd.Env = append(os.Environ())
	out, err := cmd.CombinedOutput()

	payload := map[string]any{
		"domain":  req.Domain,
		"mode":    req.Mode,
		"output":  string(out),
		"cert_dir": certDir,
	}

	if err != nil {
		exitCode := 0
		if cmd.ProcessState != nil {
			exitCode = cmd.ProcessState.ExitCode()
		}
		// acme.sh exits 2 when the cert is already valid (not an error)
		if exitCode == 2 {
			accepted(w, "ssl", "issue", req.Domain, "certificate already valid, no renewal needed", payload)
			return
		}
		payload["error"] = err.Error()
		_, _ = daemonStore.Record("ssl", "issue", "error", req.Domain, string(out), payload)
		http.Error(w, "acme.sh failed: "+string(out), http.StatusInternalServerError)
		return
	}

	_, _ = daemonStore.Record("ssl", "issue", "issued", req.Domain, "SSL certificate issued", payload)
	accepted(w, "ssl", "issue", req.Domain, "SSL certificate issued successfully", payload)
}

func handleSSLStatus(w http.ResponseWriter, r *http.Request) {
	domain := strings.TrimSpace(r.URL.Query().Get("domain"))
	if domain == "" {
		http.Error(w, "domain is required", http.StatusBadRequest)
		return
	}

	certDir := filepath.Join(xenv.BasePath(), "runtime", "ssl", domain)
	certFile := filepath.Join(certDir, "fullchain.pem")

	info, err := os.Stat(certFile)
	if err != nil {
		writeJSON(w, map[string]any{
			"domain": domain,
			"issued": false,
		})
		return
	}

	writeJSON(w, map[string]any{
		"domain":   domain,
		"issued":   true,
		"cert_dir": certDir,
		"modified": info.ModTime().UTC(),
	})
}

func findAcmeSh() string {
	candidates := []string{
		"/root/.acme.sh/acme.sh",
		"/usr/local/bin/acme.sh",
		"/opt/acme.sh/acme.sh",
	}
	for _, p := range candidates {
		if _, err := os.Stat(p); err == nil {
			return p
		}
	}
	if p, err := exec.LookPath("acme.sh"); err == nil {
		return p
	}
	return ""
}
