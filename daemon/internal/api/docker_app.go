package api

import (
	"net/http"
	"strconv"
	"xpanel/internal/docker"
	model "xpanel/internal/types"
)

var dockerAppManager = docker.NewAppManager()

func handleDockerAppCreate(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppCreateRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Create(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "created"})
}

func handleDockerAppUpdate(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppUpdateRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Update(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "updated"})
}

func handleDockerAppStart(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppActionRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Start(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "started"})
}

func handleDockerAppStop(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppActionRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Stop(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "stopped"})
}

func handleDockerAppRestart(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppActionRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Restart(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "restarted"})
}

func handleDockerAppDelete(w http.ResponseWriter, r *http.Request) {
	var req model.DockerAppActionRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if err := dockerAppManager.Delete(r.Context(), req); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "deleted"})
}

func handleDockerAppStatus(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "method not allowed", http.StatusMethodNotAllowed)
		return
	}
	req := model.DockerAppActionRequest{
		TenantCode: r.URL.Query().Get("tenant_code"),
		Slug:       r.URL.Query().Get("slug"),
	}
	result, err := dockerAppManager.Status(r.Context(), req)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, result)
}

func handleDockerAppLogs(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "method not allowed", http.StatusMethodNotAllowed)
		return
	}
	req := model.DockerAppActionRequest{
		TenantCode: r.URL.Query().Get("tenant_code"),
		Slug:       r.URL.Query().Get("slug"),
	}
	tail := 150
	if t := r.URL.Query().Get("tail"); t != "" {
		if v, err := strconv.Atoi(t); err == nil && v > 0 && v <= 1000 {
			tail = v
		}
	}
	logs, err := dockerAppManager.Logs(r.Context(), req, tail)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.DockerAppLogsResponse{Logs: logs})
}
