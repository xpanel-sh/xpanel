package api

import (
	"fmt"
	"net/http"
	"path/filepath"
	"strings"

	filemanager "xpanel/internal/files"
	model "xpanel/internal/types"
)

// handleFileList lists directory contents.
// GET /api/files/list?domain=example.com&path=/subdir
// When domain is empty, lists the global sites root.
func handleFileList(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	domain := strings.TrimSpace(r.URL.Query().Get("domain"))
	path := r.URL.Query().Get("path")
	if path == "" {
		path = "/"
	}
	root, err := filemanager.SiteRoot(domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	entries, err := filemanager.List(root, path)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.FileListResponse{Path: path, Entries: entries})
}

// handleFileRead reads a file's content for the browser editor.
// GET /api/files/read?domain=example.com&path=/index.php
func handleFileRead(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	domain := strings.TrimSpace(r.URL.Query().Get("domain"))
	path := r.URL.Query().Get("path")
	if path == "" {
		http.Error(w, "path is required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	content, err := filemanager.ReadFile(root, path)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	// Binary files are served as raw octet-stream
	if filemanager.IsBinary(content) {
		w.Header().Set("Content-Type", "application/octet-stream")
		w.Write(content)
		return
	}
	writeJSON(w, model.FileReadResponse{
		Path:    path,
		Content: string(content),
		Size:    int64(len(content)),
	})
}

// handleFileWrite saves file content from the editor.
// POST /api/files/write   body: { domain, path, content }
func handleFileWrite(w http.ResponseWriter, r *http.Request) {
	var req model.FileWriteRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.Path) == "" {
		http.Error(w, "path is required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(req.Domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	if err := filemanager.WriteFile(root, req.Path, []byte(req.Content)); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "saved"})
}

// handleFileMkdir creates a directory.
// POST /api/files/mkdir   body: { domain, path }
func handleFileMkdir(w http.ResponseWriter, r *http.Request) {
	var req model.FileMkdirRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.Path) == "" {
		http.Error(w, "path is required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(req.Domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	if err := filemanager.CreateDir(root, req.Path); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "created"})
}

// handleFileDelete deletes a file or directory.
// POST /api/files/delete   body: { domain, path }
func handleFileDelete(w http.ResponseWriter, r *http.Request) {
	var req model.FileDeleteRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.Path) == "" {
		http.Error(w, "path is required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(req.Domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	if err := filemanager.Delete(root, req.Path); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "deleted"})
}

// handleFileRename renames or moves a file/directory within the same site root.
// POST /api/files/rename   body: { domain, old_path, new_path }
func handleFileRename(w http.ResponseWriter, r *http.Request) {
	var req model.FileRenameRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if strings.TrimSpace(req.OldPath) == "" || strings.TrimSpace(req.NewPath) == "" {
		http.Error(w, "old_path and new_path are required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(req.Domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	if err := filemanager.Rename(root, req.OldPath, req.NewPath); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "renamed"})
}

// handleFileUpload handles multipart/form-data file uploads.
// POST /api/files/upload   fields: domain (string), path (destination dir), file (multipart)
func handleFileUpload(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	if err := r.ParseMultipartForm(50 << 20); err != nil {
		http.Error(w, "failed to parse multipart form: "+err.Error(), http.StatusBadRequest)
		return
	}
	domain := strings.TrimSpace(r.FormValue("domain"))
	destDir := r.FormValue("path")
	if destDir == "" {
		destDir = "/"
	}

	file, header, err := r.FormFile("file")
	if err != nil {
		http.Error(w, "file field is required", http.StatusBadRequest)
		return
	}
	defer file.Close()

	root, err := filemanager.SiteRoot(domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}

	// filepath.Base prevents any path traversal in the filename itself
	filename := filepath.Base(header.Filename)
	if err := filemanager.SaveUpload(root, destDir, filename, file); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, model.ActionResponse{Status: "uploaded", Message: fmt.Sprintf("uploaded %s", filename)})
}

// handleFileDownload streams a file to the client as an attachment.
// GET /api/files/download?domain=example.com&path=/file.php
func handleFileDownload(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	domain := strings.TrimSpace(r.URL.Query().Get("domain"))
	path := r.URL.Query().Get("path")
	if path == "" {
		http.Error(w, "path is required", http.StatusBadRequest)
		return
	}
	root, err := filemanager.SiteRoot(domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}
	abs, err := filemanager.SafeJoin(root, path)
	if err != nil {
		http.Error(w, err.Error(), http.StatusForbidden)
		return
	}
	filename := filepath.Base(abs)
	w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="%s"`, filename))
	http.ServeFile(w, r, abs)
}
