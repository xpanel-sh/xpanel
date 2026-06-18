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

type FileExtractRequest struct {
	Domain string `json:"domain"`
	Path   string `json:"path"`
}

type FileSearchRequest struct {
	Domain         string `json:"domain"`
	Path           string `json:"path"`
	Query          string `json:"query"`
	IncludeContent bool   `json:"include_content"`
	CaseSensitive  bool   `json:"case_sensitive"`
	MaxResults     int    `json:"max_results"`
}

type FileSearchResult struct {
	Name    string `json:"name"`
	Path    string `json:"path"`
	IsDir   bool   `json:"is_dir"`
	Kind    string `json:"kind"`
	Line    int    `json:"line,omitempty"`
	Column  int    `json:"column,omitempty"`
	Preview string `json:"preview,omitempty"`
}

type FileSearchResponse struct {
	Query     string             `json:"query"`
	Path      string             `json:"path"`
	Results   []FileSearchResult `json:"results"`
	Truncated bool               `json:"truncated"`
	Scanned   int                `json:"scanned"`
}

// --- DNS extended types ---

type NSLookupRequest struct {
	Domain string `json:"domain"`
}

type NSLookupResponse struct {
	Domain      string   `json:"domain"`
	Nameservers []string `json:"nameservers"`
	ARecords    []string `json:"a_records"`
}

type CloudflareRecordRequest struct {
	APIToken string `json:"api_token"`
	Domain   string `json:"domain"`
	Type     string `json:"type"`
	Name     string `json:"name"`
	Value    string `json:"value"`
	TTL      int    `json:"ttl"`
	Priority *int   `json:"priority,omitempty"`
	Proxied  bool   `json:"proxied"`
}

type CloudflareDeleteRequest struct {
	APIToken string `json:"api_token"`
	Domain   string `json:"domain"`
	Type     string `json:"type"`
	Name     string `json:"name"`
}

type CloudflareZoneIDRequest struct {
	APIToken string `json:"api_token"`
	Domain   string `json:"domain"`
}

// --- PHP types ---

type PhpIniRequest struct {
	Domain  string            `json:"domain"`
	Options map[string]string `json:"options"`
}

type DatabasePermissionsRequest struct {
	Name       string   `json:"name"`
	Username   string   `json:"username"`
	Engine     string   `json:"engine"`
	Privileges []string `json:"privileges"`
}

type DatabaseUserRequest struct {
	Database string `json:"database"`
	Username string `json:"username"`
	Password string `json:"password,omitempty"`
	Engine   string `json:"engine"`
}

// --- SSL types ---

type SSLIssueRequest struct {
	Domain  string `json:"domain"`
	Mode    string `json:"mode"`    // cloudflare | http
	CFToken string `json:"cf_token,omitempty"`
	Webroot string `json:"webroot,omitempty"`
}

// --- Docker App types ---

type DockerAppCreateRequest struct {
	TenantCode  string `json:"tenant_code"`
	Slug        string `json:"slug"`
	ComposeYAML string `json:"compose_yaml"`
}

type DockerAppActionRequest struct {
	TenantCode string `json:"tenant_code"`
	Slug       string `json:"slug"`
}

type DockerAppUpdateRequest struct {
	TenantCode  string `json:"tenant_code"`
	Slug        string `json:"slug"`
	ComposeYAML string `json:"compose_yaml"`
}

type DockerServiceStatus struct {
	Name   string `json:"name"`
	State  string `json:"state"`  // running | exited | created
	Health string `json:"health,omitempty"`
}

type DockerAppStatusResponse struct {
	Status   string                `json:"status"` // running | stopped | partial | not_found
	Services []DockerServiceStatus `json:"services"`
}

type DockerAppLogsResponse struct {
	Logs string `json:"logs"`
}
