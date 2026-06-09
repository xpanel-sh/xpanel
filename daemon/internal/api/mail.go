package api

import (
	"encoding/json"
	"net/http"
	"strings"

	mailfiles "xpanel/internal/mail"
	model "xpanel/internal/types"
)

var mailWriter = mailfiles.NewFileWriter()

func handleMailAccountCreate(w http.ResponseWriter, r *http.Request) {
	var req model.MailAccountRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Email) == "" || strings.TrimSpace(req.Domain) == "" || strings.TrimSpace(req.Password) == "" {
		http.Error(w, "email, domain and password are required", http.StatusBadRequest)
		return
	}

	payload := payloadFrom(req)
	payload["status"] = "active"
	payload["password"] = "redacted"
	if err := daemonStore.Upsert("mail_accounts", req.Email, payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if err := rewriteMailArtifacts(); err != nil {
		payload["status"] = "error"
		_, _ = daemonStore.Record("mail", "write-artifacts", "error", req.Email, err.Error(), payload)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "mail", "create", req.Email, "mail account registered and mail artifacts updated", payload)
}

func handleMailAccountDelete(w http.ResponseWriter, r *http.Request) {
	var req model.MailAccountRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Email) == "" {
		http.Error(w, "email is required", http.StatusBadRequest)
		return
	}

	if err := daemonStore.Delete("mail_accounts", req.Email); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if err := rewriteMailArtifacts(); err != nil {
		payload := payloadFrom(req)
		payload["status"] = "error"
		_, _ = daemonStore.Record("mail", "write-artifacts", "error", req.Email, err.Error(), payload)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "mail", "delete", req.Email, "mail account removed and mail artifacts updated", payloadFrom(req))
}

func handleMailPasswordReset(w http.ResponseWriter, r *http.Request) {
	var req model.MailAccountRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Email) == "" || strings.TrimSpace(req.Password) == "" {
		http.Error(w, "email and password are required", http.StatusBadRequest)
		return
	}

	payload := payloadFrom(req)
	payload["password"] = "redacted"
	if err := daemonStore.Upsert("mail_password_resets", req.Email, payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "mail", "reset-password", req.Email, "mail password reset registered in daemon state", payload)
}

func rewriteMailArtifacts() error {
	accounts, err := daemonStore.Map("mail_accounts")
	if err != nil {
		return err
	}

	return mailWriter.Write(accounts)
}

func decodeJSON(w http.ResponseWriter, r *http.Request, v any) bool {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return false
	}

	if err := json.NewDecoder(r.Body).Decode(v); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return false
	}

	return true
}

func writeJSON(w http.ResponseWriter, payload any) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(payload)
}
