package types

type CreateSiteRequest struct {
	Name       string `json:"name"`
	Domain     string `json:"domain"`
	Type       string `json:"type"`       // php, node, static, python
	WebServer  string `json:"web_server"` // apache, nginx
	PhpVersion string `json:"php_version"`
}

type CreateSiteResponse struct {
	ID     string `json:"id"`
	Status string `json:"status"`
}

type SiteActionRequest struct {
	Name string `json:"name"`
}

type MailAccountRequest struct {
	Email    string `json:"email"`
	Domain   string `json:"domain,omitempty"`
	QuotaMB  int    `json:"quota_mb,omitempty"`
	Password string `json:"password,omitempty"`
}

type DNSRecordRequest struct {
	Domain   string `json:"domain"`
	Type     string `json:"type"`
	Name     string `json:"name"`
	Value    string `json:"value,omitempty"`
	TTL      int    `json:"ttl,omitempty"`
	Priority *int   `json:"priority,omitempty"`
}

type NameserverApplyRequest struct {
	Nameservers []string `json:"nameservers"`
}

type DatabaseRequest struct {
	Name     string `json:"name"`
	Username string `json:"username"`
	Password string `json:"password,omitempty"`
	Engine   string `json:"engine"`
}

type ActionResponse struct {
	Status      string `json:"status"`
	Message     string `json:"message,omitempty"`
	OperationID string `json:"operation_id,omitempty"`
}

// --- File Manager Types ---

type FileEntry struct {
	Name    string `json:"name"`
	Path    string `json:"path"`
	IsDir   bool   `json:"is_dir"`
	Size    int64  `json:"size"`
	Mode    string `json:"mode"`
	ModTime string `json:"mod_time"`
}

type FileListResponse struct {
	Path    string      `json:"path"`
	Entries []FileEntry `json:"entries"`
}

type FileReadResponse struct {
	Path    string `json:"path"`
	Content string `json:"content"`
	Size    int64  `json:"size"`
}

type FileWriteRequest struct {
	Domain  string `json:"domain"`
	Path    string `json:"path"`
	Content string `json:"content"`
}

type FileMkdirRequest struct {
	Domain string `json:"domain"`
	Path   string `json:"path"`
}

type FileDeleteRequest struct {
	Domain string `json:"domain"`
	Path   string `json:"path"`
}

type FileRenameRequest struct {
	Domain  string `json:"domain"`
	OldPath string `json:"old_path"`
	NewPath string `json:"new_path"`
}
