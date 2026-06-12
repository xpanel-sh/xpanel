package api

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
	"strings"
	"xpanel/internal/auth"
	"xpanel/internal/docker"
	model "xpanel/internal/types"
)

func NewRouter() http.Handler {
	mux := http.NewServeMux()

	// Middleware básico de logging podría ir aquí

	// Docker endpoints
	dockerManager, dockerErr := docker.NewManager()

	mux.HandleFunc("/status", handleHealth)
	mux.HandleFunc("/health", handleHealth)
	mux.HandleFunc("/api/mail/account/create", requireAuth(handleMailAccountCreate))
	mux.HandleFunc("/api/mail/account/delete", requireAuth(handleMailAccountDelete))
	mux.HandleFunc("/api/mail/account/reset-password", requireAuth(handleMailPasswordReset))
	mux.HandleFunc("/api/dns/record/upsert", requireAuth(handleDNSRecordUpsert))
	mux.HandleFunc("/api/dns/record/delete", requireAuth(handleDNSRecordDelete))
	mux.HandleFunc("/api/dns/nameservers/apply", requireAuth(handleNameserversApply))
	mux.HandleFunc("/api/database/create", requireAuth(handleDatabaseCreate))
	mux.HandleFunc("/api/database/delete", requireAuth(handleDatabaseDelete))
	mux.HandleFunc("/api/operations", requireAuth(handleOperationsList))
	mux.HandleFunc("/api/runtime/status", requireAuth(handleRuntimeStatus))

	// File Manager endpoints
	mux.HandleFunc("/api/files/list", requireAuth(handleFileList))
	mux.HandleFunc("/api/files/read", requireAuth(handleFileRead))
	mux.HandleFunc("/api/files/write", requireAuth(handleFileWrite))
	mux.HandleFunc("/api/files/mkdir", requireAuth(handleFileMkdir))
	mux.HandleFunc("/api/files/delete", requireAuth(handleFileDelete))
	mux.HandleFunc("/api/files/rename", requireAuth(handleFileRename))
	mux.HandleFunc("/api/files/extract", requireAuth(handleFileExtract))
	mux.HandleFunc("/api/files/search", requireAuth(handleFileSearch))
	mux.HandleFunc("/api/files/upload", requireAuth(handleFileUpload))
	mux.HandleFunc("/api/files/download", requireAuth(handleFileDownload))

	mux.HandleFunc("/api/site/create", requireAuth(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
			return
		}

		var req model.CreateSiteRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			http.Error(w, err.Error(), http.StatusBadRequest)
			return
		}

		if dockerErr != nil {
			log.Printf("docker manager unavailable: %v", dockerErr)
			http.Error(w, "Docker manager unavailable", http.StatusServiceUnavailable)
			return
		}

		id, err := dockerManager.CreateSiteContainer(r.Context(), req)
		if err != nil {
			_, _ = daemonStore.Record("site", "create", "error", req.Name, err.Error(), payloadFrom(req))
			log.Printf("site create failed for %q: %v", req.Name, err)
			http.Error(w, "Site provisioning failed", http.StatusInternalServerError)
			return
		}

		_, _ = daemonStore.Record("site", "create", "created", req.Name, "site container created", payloadFrom(req))

		json.NewEncoder(w).Encode(model.CreateSiteResponse{ID: id, Status: "created"})
	}))

	mux.HandleFunc("/api/site/restart", requireAuth(func(w http.ResponseWriter, r *http.Request) {
		var req model.SiteActionRequest
		if !decodeJSON(w, r, &req) {
			return
		}
		if strings.TrimSpace(req.Name) == "" {
			http.Error(w, "name is required", http.StatusBadRequest)
			return
		}
		if dockerErr != nil {
			log.Printf("docker manager unavailable: %v", dockerErr)
			http.Error(w, "Docker manager unavailable", http.StatusServiceUnavailable)
			return
		}
		if err := dockerManager.RestartSiteContainer(r.Context(), req.Name); err != nil {
			_, _ = daemonStore.Record("site", "restart", "error", req.Name, err.Error(), payloadFrom(req))
			log.Printf("site restart failed for %q: %v", req.Name, err)
			http.Error(w, "Site restart failed", http.StatusInternalServerError)
			return
		}
		op, err := daemonStore.Record("site", "restart", "restarted", req.Name, "site container restarted", payloadFrom(req))
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		writeJSON(w, model.ActionResponse{Status: "restarted", OperationID: op.ID})
	}))

	mux.HandleFunc("/api/site/delete", requireAuth(func(w http.ResponseWriter, r *http.Request) {
		var req model.SiteActionRequest
		if !decodeJSON(w, r, &req) {
			return
		}
		if strings.TrimSpace(req.Name) == "" {
			http.Error(w, "name is required", http.StatusBadRequest)
			return
		}
		if dockerErr != nil {
			log.Printf("docker manager unavailable: %v", dockerErr)
			http.Error(w, "Docker manager unavailable", http.StatusServiceUnavailable)
			return
		}
		if err := dockerManager.DeleteSiteContainer(r.Context(), req.Name); err != nil {
			_, _ = daemonStore.Record("site", "delete", "error", req.Name, err.Error(), payloadFrom(req))
			log.Printf("site delete failed for %q: %v", req.Name, err)
			http.Error(w, "Site delete failed", http.StatusInternalServerError)
			return
		}
		op, err := daemonStore.Record("site", "delete", "deleted", req.Name, "site container deleted", payloadFrom(req))
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		writeJSON(w, model.ActionResponse{Status: "deleted", OperationID: op.ID})
	}))

	return mux
}

func requireAuth(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		token := strings.TrimSpace(r.Header.Get("X-XPanel-Token"))
		if token == "" {
			authHeader := strings.TrimSpace(r.Header.Get("Authorization"))
			token = strings.TrimPrefix(authHeader, "Bearer ")
		}

		if !auth.Valid(token) {
			http.Error(w, "Unauthorized", http.StatusUnauthorized)
			return
		}

		next(w, r)
	}
}

func handleHealth(w http.ResponseWriter, r *http.Request) {
	version := os.Getenv("XPANEL_VERSION")
	if version == "" {
		version = "dev"
	}
	response := map[string]string{
		"status":  "ok",
		"version": version,
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}
