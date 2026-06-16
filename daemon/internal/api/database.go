package api

import (
	"net/http"
	"strings"

	dbmanager "xpanel/internal/database"
	model "xpanel/internal/types"
)

var databaseManager = dbmanager.NewManager()

func handleDatabaseCreate(w http.ResponseWriter, r *http.Request) {
	var req model.DatabaseRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Name) == "" || strings.TrimSpace(req.Username) == "" || strings.TrimSpace(req.Password) == "" || strings.TrimSpace(req.Engine) == "" {
		http.Error(w, "name, username, password and engine are required", http.StatusBadRequest)
		return
	}

	key := strings.ToLower(req.Engine + "|" + req.Name)
	payload := payloadFrom(req)
	payload["password"] = "redacted"

	if err := databaseManager.Create(r.Context(), req); err != nil {
		payload["status"] = "error"
		_, _ = daemonStore.Record("database", "create", "error", key, err.Error(), payload)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	payload["status"] = "active"
	if err := daemonStore.Upsert("databases", key, payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "database", "create", key, "database created and registered in daemon state", payload)
}

func handleDatabasePermissions(w http.ResponseWriter, r *http.Request) {
	var req model.DatabasePermissionsRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := databaseManager.UpdatePermissions(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]string{"status": "updated"})
}

func handleDatabaseUserAdd(w http.ResponseWriter, r *http.Request) {
	var req model.DatabaseUserRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := databaseManager.AddUser(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]string{"status": "created"})
}

func handleDatabaseUserRemove(w http.ResponseWriter, r *http.Request) {
	var req model.DatabaseUserRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := databaseManager.RemoveUser(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]string{"status": "removed"})
}

func handleDatabaseUserPassword(w http.ResponseWriter, r *http.Request) {
	var req model.DatabaseUserRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := databaseManager.ChangeUserPassword(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]string{"status": "updated"})
}

func handleDatabaseDelete(w http.ResponseWriter, r *http.Request) {
	var req model.DatabaseRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Name) == "" || strings.TrimSpace(req.Username) == "" || strings.TrimSpace(req.Engine) == "" {
		http.Error(w, "name, username and engine are required", http.StatusBadRequest)
		return
	}

	key := strings.ToLower(req.Engine + "|" + req.Name)
	if err := databaseManager.Delete(r.Context(), req); err != nil {
		payload := payloadFrom(req)
		payload["status"] = "error"
		_, _ = daemonStore.Record("database", "delete", "error", key, err.Error(), payload)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	if err := daemonStore.Delete("databases", key); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "database", "delete", key, "database removed from MariaDB and daemon state", payloadFrom(req))
}
