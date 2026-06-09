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
