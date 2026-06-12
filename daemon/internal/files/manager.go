package files

import (
	"fmt"
	"io"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"time"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

// SiteRoot returns the validated absolute path for a site's files directory.
// When domain is empty it returns the global sites root.
// Always call this before any file operation.
func SiteRoot(domain string) (string, error) {
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" {
		root := filepath.Join(xenv.BasePath(), "runtime", "sites")
		if err := os.MkdirAll(root, 0755); err != nil {
			return "", fmt.Errorf("cannot initialize sites root: %w", err)
		}
		return root, nil
	}
	// Reject domains containing path separators or traversal sequences
	if strings.ContainsAny(domain, "/\\") || strings.Contains(domain, "..") {
		return "", fmt.Errorf("invalid domain: %q", domain)
	}
	root := filepath.Join(xenv.BasePath(), "runtime", "sites", domain)
	// Auto-create the directory if it doesn't exist (e.g. freshly-imported site).
	if err := os.MkdirAll(root, 0755); err != nil {
		return "", fmt.Errorf("cannot initialize site root for domain %q: %w", domain, err)
	}
	return root, nil
}

// SafeJoin prevents path traversal. Returns error if the resulting
// absolute path does not start with root.
func SafeJoin(root, relativePath string) (string, error) {
	cleaned := filepath.Clean("/" + relativePath)
	abs := filepath.Join(root, cleaned)
	cleanRoot := filepath.Clean(root)
	if abs != cleanRoot && !strings.HasPrefix(abs, cleanRoot+string(os.PathSeparator)) {
		return "", fmt.Errorf("path traversal detected: %q", relativePath)
	}
	return abs, nil
}

// List returns directory entries at relativePath inside root.
// Directories are sorted first, then files, both alphabetically.
func List(root, relativePath string) ([]model.FileEntry, error) {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return nil, err
	}
	entries, err := os.ReadDir(abs)
	if err != nil {
		return nil, fmt.Errorf("cannot read directory: %w", err)
	}

	result := make([]model.FileEntry, 0, len(entries))
	for _, e := range entries {
		info, err := e.Info()
		if err != nil {
			continue
		}
		// Normalize path separator to forward slash for JSON
		rel := filepath.ToSlash(filepath.Join(relativePath, e.Name()))
		if !strings.HasPrefix(rel, "/") {
			rel = "/" + rel
		}
		entry := model.FileEntry{
			Name:    e.Name(),
			Path:    rel,
			IsDir:   e.IsDir(),
			Size:    info.Size(),
			Mode:    info.Mode().String(),
			ModTime: info.ModTime().UTC().Format(time.RFC3339),
		}
		result = append(result, entry)
	}

	sort.Slice(result, func(i, j int) bool {
		if result[i].IsDir != result[j].IsDir {
			return result[i].IsDir
		}
		return strings.ToLower(result[i].Name) < strings.ToLower(result[j].Name)
	})
	return result, nil
}

const maxReadSize = 2 * 1024 * 1024 // 2MB

// ReadFile reads a file's content. Returns error if file exceeds 2MB.
func ReadFile(root, relativePath string) ([]byte, error) {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return nil, err
	}
	info, err := os.Stat(abs)
	if err != nil {
		return nil, fmt.Errorf("file not found: %w", err)
	}
	if info.IsDir() {
		return nil, fmt.Errorf("path is a directory, not a file")
	}
	if info.Size() > maxReadSize {
		return nil, fmt.Errorf("file too large for editor (max 2MB, got %d bytes)", info.Size())
	}
	return os.ReadFile(abs)
}

// WriteFile writes content to a file, creating parent directories as needed.
func WriteFile(root, relativePath string, content []byte) error {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(abs), 0755); err != nil {
		return fmt.Errorf("cannot create parent directories: %w", err)
	}
	return os.WriteFile(abs, content, 0644)
}

// CreateDir creates a directory (and any parents).
func CreateDir(root, relativePath string) error {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return err
	}
	return os.MkdirAll(abs, 0755)
}

// Delete removes a file or directory (recursive). Refuses to delete site root.
func Delete(root, relativePath string) error {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return err
	}
	if abs == filepath.Clean(root) {
		return fmt.Errorf("cannot delete the site root directory")
	}
	return os.RemoveAll(abs)
}

// Rename renames/moves a file or directory within the same root.
func Rename(root, oldRelative, newRelative string) error {
	oldAbs, err := SafeJoin(root, oldRelative)
	if err != nil {
		return err
	}
	newAbs, err := SafeJoin(root, newRelative)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(newAbs), 0755); err != nil {
		return fmt.Errorf("cannot create parent directories: %w", err)
	}
	return os.Rename(oldAbs, newAbs)
}

const maxUploadSize = 50 * 1024 * 1024 // 50MB

// SaveUpload writes streamed upload data to a file.
// filename must come from filepath.Base() before being passed here.
func SaveUpload(root, destDir, filename string, r io.Reader) error {
	// Ensure filename has no path components
	filename = filepath.Base(filename)
	if filename == "." || filename == "/" {
		return fmt.Errorf("invalid filename")
	}
	destPath := filepath.Join(destDir, filename)
	abs, err := SafeJoin(root, destPath)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(abs), 0755); err != nil {
		return fmt.Errorf("cannot create parent directories: %w", err)
	}
	f, err := os.Create(abs)
	if err != nil {
		return fmt.Errorf("cannot create file: %w", err)
	}
	defer f.Close()
	_, err = io.Copy(f, io.LimitReader(r, maxUploadSize))
	return err
}

// IsBinary detects if content is binary by looking for null bytes in the first 512 bytes.
func IsBinary(data []byte) bool {
	check := data
	if len(check) > 512 {
		check = check[:512]
	}
	for _, b := range check {
		if b == 0 {
			return true
		}
	}
	return false
}
