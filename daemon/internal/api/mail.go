package api

import (
	"net/http"
	"strconv"
	"strings"

	mailpkg "xpanel/internal/mail"
	model "xpanel/internal/types"
)

var (
	mailWriter      = mailpkg.NewFileWriter()
	mailAccounts    = mailpkg.NewAccountManager()
	imapClient      = mailpkg.NewIMAPClient()
	smtpClient      = mailpkg.NewSMTPClient()
)

// ── Account management ────────────────────────────────────────────────────────

func handleMailAccountCreate(w http.ResponseWriter, r *http.Request) {
	var req model.MailAccountRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.Email) == "" || strings.TrimSpace(req.Domain) == "" || strings.TrimSpace(req.Password) == "" {
		http.Error(w, "email, domain and password are required", http.StatusBadRequest)
		return
	}

	// Provision account in docker-mailserver.
	if err := mailAccounts.Add(r.Context(), req.Email, req.Password); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	payload := payloadFrom(req)
	payload["status"] = "active"
	payload["password"] = "redacted"
	if err := daemonStore.Upsert("mail_accounts", req.Email, payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	// Keep legacy virtual-map files in sync (used by Postfix in non-docker-mailserver setups).
	_ = rewriteMailArtifacts()

	accepted(w, "mail", "create", req.Email, "mail account provisioned", payload)
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

	if err := mailAccounts.Delete(r.Context(), req.Email); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	if err := daemonStore.Delete("mail_accounts", req.Email); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_ = rewriteMailArtifacts()

	accepted(w, "mail", "delete", req.Email, "mail account removed", payloadFrom(req))
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

	// Update password in docker-mailserver.
	if err := mailAccounts.UpdatePassword(r.Context(), req.Email, req.Password); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accounts, err := daemonStore.Map("mail_accounts")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	existing, ok := accounts[req.Email]
	if !ok {
		http.Error(w, "mail account not found in daemon state", http.StatusNotFound)
		return
	}
	existing["password"] = "redacted"
	if err := daemonStore.Upsert("mail_accounts", req.Email, existing); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_ = rewriteMailArtifacts()

	accepted(w, "mail", "reset-password", req.Email, "password updated in mail server", map[string]any{"email": req.Email})
}

// ── IMAP proxy ────────────────────────────────────────────────────────────────

func handleMailFolders(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	account := strings.TrimSpace(r.URL.Query().Get("account"))
	if account == "" {
		http.Error(w, "account is required", http.StatusBadRequest)
		return
	}

	folders, err := imapClient.Folders(account)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"folders": folders})
}

func handleMailMessages(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	account := strings.TrimSpace(r.URL.Query().Get("account"))
	folder := strings.TrimSpace(r.URL.Query().Get("folder"))
	if account == "" || folder == "" {
		http.Error(w, "account and folder are required", http.StatusBadRequest)
		return
	}
	page, _ := strconv.Atoi(r.URL.Query().Get("page"))
	if page < 1 {
		page = 1
	}
	perPage, _ := strconv.Atoi(r.URL.Query().Get("per_page"))
	if perPage < 1 || perPage > 100 {
		perPage = 25
	}

	messages, total, err := imapClient.Messages(account, folder, page, perPage)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{
		"folder":   folder,
		"total":    total,
		"page":     page,
		"per_page": perPage,
		"messages": messages,
	})
}

func handleMailMessage(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	account := strings.TrimSpace(r.URL.Query().Get("account"))
	folder := strings.TrimSpace(r.URL.Query().Get("folder"))
	uidStr := strings.TrimSpace(r.URL.Query().Get("uid"))
	if account == "" || folder == "" || uidStr == "" {
		http.Error(w, "account, folder and uid are required", http.StatusBadRequest)
		return
	}
	uid64, err := strconv.ParseUint(uidStr, 10, 32)
	if err != nil {
		http.Error(w, "invalid uid", http.StatusBadRequest)
		return
	}

	msg, err := imapClient.Message(account, folder, uint32(uid64))
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, msg)
}

func handleMailFlag(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Account string `json:"account"`
		Folder  string `json:"folder"`
		UID     uint32 `json:"uid"`
		Flag    string `json:"flag"` // "seen", "flagged", "deleted"
		Set     bool   `json:"set"`
	}
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.Account == "" || req.Folder == "" || req.UID == 0 || req.Flag == "" {
		http.Error(w, "account, folder, uid and flag are required", http.StatusBadRequest)
		return
	}

	imapFlag := map[string]string{
		"seen":    `\Seen`,
		"flagged": `\Flagged`,
		"deleted": `\Deleted`,
	}[strings.ToLower(req.Flag)]
	if imapFlag == "" {
		http.Error(w, "unknown flag: "+req.Flag, http.StatusBadRequest)
		return
	}

	if err := imapClient.SetFlag(req.Account, req.Folder, req.UID, imapFlag, req.Set); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

func handleMailMove(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Account      string `json:"account"`
		Folder       string `json:"folder"`
		UID          uint32 `json:"uid"`
		TargetFolder string `json:"target_folder"`
	}
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.Account == "" || req.Folder == "" || req.UID == 0 || req.TargetFolder == "" {
		http.Error(w, "account, folder, uid and target_folder are required", http.StatusBadRequest)
		return
	}

	if err := imapClient.Move(req.Account, req.Folder, req.UID, req.TargetFolder); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

func handleMailDelete(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Account string `json:"account"`
		Folder  string `json:"folder"`
		UID     uint32 `json:"uid"`
	}
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.Account == "" || req.Folder == "" || req.UID == 0 {
		http.Error(w, "account, folder and uid are required", http.StatusBadRequest)
		return
	}

	if err := imapClient.Delete(req.Account, req.Folder, req.UID); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

// ── SMTP proxy ────────────────────────────────────────────────────────────────

func handleMailSend(w http.ResponseWriter, r *http.Request) {
	var req mailpkg.SendRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := smtpClient.Send(req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

// ── Folder management ─────────────────────────────────────────────────────────

func handleMailFolderCreate(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Account string `json:"account"`
		Name    string `json:"name"`
	}
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.Account == "" || req.Name == "" {
		http.Error(w, "account and name are required", http.StatusBadRequest)
		return
	}
	if err := imapClient.CreateFolder(req.Account, req.Name); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

func handleMailFolderDelete(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Account string `json:"account"`
		Name    string `json:"name"`
	}
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.Account == "" || req.Name == "" {
		http.Error(w, "account and name are required", http.StatusBadRequest)
		return
	}
	if err := imapClient.DeleteFolder(req.Account, req.Name); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]any{"ok": true})
}

// ── Legacy virtual-map artifact helper ───────────────────────────────────────

func rewriteMailArtifacts() error {
	accounts, err := daemonStore.Map("mail_accounts")
	if err != nil {
		return err
	}
	return mailWriter.Write(accounts)
}
