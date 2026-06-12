package files

import (
	"archive/zip"
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

const maxReadSize = 2 * 1024 * 1024   // 2MB
const maxSearchFileSize = 1024 * 1024 // 1MB
const defaultSearchLimit = 200

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

// Search walks a root path and returns name/content matches for text files.
func Search(root string, req model.FileSearchRequest) (model.FileSearchResponse, error) {
	query := strings.TrimSpace(req.Query)
	if query == "" {
		return model.FileSearchResponse{Query: query, Path: req.Path, Results: []model.FileSearchResult{}}, nil
	}
	searchPath := req.Path
	if searchPath == "" {
		searchPath = "/"
	}
	abs, err := SafeJoin(root, searchPath)
	if err != nil {
		return model.FileSearchResponse{}, err
	}
	info, err := os.Stat(abs)
	if err != nil {
		return model.FileSearchResponse{}, fmt.Errorf("search path not found: %w", err)
	}
	if !info.IsDir() {
		abs = filepath.Dir(abs)
		searchPath = filepath.ToSlash(filepath.Dir(searchPath))
		if searchPath == "." || searchPath == "" {
			searchPath = "/"
		}
	}

	limit := req.MaxResults
	if limit <= 0 || limit > 500 {
		limit = defaultSearchLimit
	}

	needle := query
	if !req.CaseSensitive {
		needle = strings.ToLower(needle)
	}
	response := model.FileSearchResponse{
		Query:   query,
		Path:    searchPath,
		Results: []model.FileSearchResult{},
	}
	skipDirs := map[string]bool{
		".git": true, "node_modules": true, "vendor": true, "storage": true,
		"cache": true, ".next": true, "dist": true, "build": true,
	}

	err = filepath.WalkDir(abs, func(path string, entry os.DirEntry, walkErr error) error {
		if walkErr != nil {
			return nil
		}
		if path == abs {
			return nil
		}
		if response.Truncated {
			return filepath.SkipAll
		}
		name := entry.Name()
		if entry.IsDir() && skipDirs[strings.ToLower(name)] {
			return filepath.SkipDir
		}

		rel, err := filepath.Rel(root, path)
		if err != nil {
			return nil
		}
		rel = "/" + filepath.ToSlash(rel)
		haystackName := name
		if !req.CaseSensitive {
			haystackName = strings.ToLower(haystackName)
		}
		if strings.Contains(haystackName, needle) {
			response.Results = append(response.Results, model.FileSearchResult{
				Name:  name,
				Path:  rel,
				IsDir: entry.IsDir(),
				Kind:  "name",
			})
			if len(response.Results) >= limit {
				response.Truncated = true
				return filepath.SkipAll
			}
		}
		if entry.IsDir() || !req.IncludeContent {
			return nil
		}

		info, err := entry.Info()
		if err != nil || info.Size() > maxSearchFileSize {
			return nil
		}
		response.Scanned++
		content, err := os.ReadFile(path)
		if err != nil || IsBinary(content) {
			return nil
		}
		lines := strings.Split(string(content), "\n")
		for index, line := range lines {
			haystackLine := line
			if !req.CaseSensitive {
				haystackLine = strings.ToLower(haystackLine)
			}
			column := strings.Index(haystackLine, needle)
			if column < 0 {
				continue
			}
			preview := strings.TrimSpace(line)
			if len(preview) > 220 {
				preview = preview[:220] + "..."
			}
			response.Results = append(response.Results, model.FileSearchResult{
				Name:    name,
				Path:    rel,
				IsDir:   false,
				Kind:    "content",
				Line:    index + 1,
				Column:  column + 1,
				Preview: preview,
			})
			if len(response.Results) >= limit {
				response.Truncated = true
				return filepath.SkipAll
			}
		}
		return nil
	})
	if err != nil && err != filepath.SkipAll {
		return response, err
	}

	return response, nil
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

// ExtractZip extracts a zip-compatible archive into the directory where it lives.
// Every archive member is validated through SafeJoin to prevent zip-slip traversal.
func ExtractZip(root, relativePath string) (int, error) {
	abs, err := SafeJoin(root, relativePath)
	if err != nil {
		return 0, err
	}
	info, err := os.Stat(abs)
	if err != nil {
		return 0, fmt.Errorf("archive not found: %w", err)
	}
	if info.IsDir() {
		return 0, fmt.Errorf("path is a directory, not an archive")
	}
	if !strings.EqualFold(filepath.Ext(abs), ".zip") && !strings.EqualFold(filepath.Ext(abs), ".jar") {
		return 0, fmt.Errorf("only zip archives are supported")
	}

	reader, err := zip.OpenReader(abs)
	if err != nil {
		return 0, fmt.Errorf("cannot open archive: %w", err)
	}
	defer reader.Close()

	destDir := filepath.Dir(abs)
	destRel, err := filepath.Rel(root, destDir)
	if err != nil {
		return 0, err
	}
	if destRel == "." {
		destRel = ""
	}

	extracted := 0
	for _, file := range reader.File {
		name := filepath.ToSlash(file.Name)
		name = strings.TrimLeft(name, "/")
		if name == "" || strings.Contains(file.Name, "\\") || strings.Contains(name, "../") || strings.HasPrefix(name, "..") {
			return extracted, fmt.Errorf("unsafe archive entry: %q", file.Name)
		}
		targetRel := filepath.ToSlash(filepath.Join(destRel, name))
		targetAbs, err := SafeJoin(root, targetRel)
		if err != nil {
			return extracted, err
		}
		mode := file.Mode()

		if file.FileInfo().IsDir() {
			if mode == 0 {
				mode = 0755
			}
			if err := os.MkdirAll(targetAbs, mode); err != nil {
				return extracted, fmt.Errorf("cannot create directory %q: %w", file.Name, err)
			}
			continue
		}
		if mode == 0 {
			mode = 0644
		}

		if err := os.MkdirAll(filepath.Dir(targetAbs), 0755); err != nil {
			return extracted, fmt.Errorf("cannot create parent directory for %q: %w", file.Name, err)
		}

		src, err := file.Open()
		if err != nil {
			return extracted, fmt.Errorf("cannot open archive entry %q: %w", file.Name, err)
		}
		dst, err := os.OpenFile(targetAbs, os.O_WRONLY|os.O_CREATE|os.O_TRUNC, mode)
		if err != nil {
			src.Close()
			return extracted, fmt.Errorf("cannot create file %q: %w", file.Name, err)
		}
		if _, err := io.Copy(dst, src); err != nil {
			src.Close()
			dst.Close()
			return extracted, fmt.Errorf("cannot extract file %q: %w", file.Name, err)
		}
		src.Close()
		dst.Close()
		extracted++
	}

	return extracted, nil
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
