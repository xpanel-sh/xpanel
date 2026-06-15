package api

import (
	"net/http"
	"strings"

	dnslib "xpanel/internal/dns"
	model "xpanel/internal/types"
)

var zoneWriter = dnslib.NewZoneWriter()

// ── XPanel NS (zone file) handlers ──────────────────────────────────────────

func handleDNSRecordUpsert(w http.ResponseWriter, r *http.Request) {
	var req model.DNSRecordRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Domain) == "" || strings.TrimSpace(req.Type) == "" || strings.TrimSpace(req.Name) == "" {
		http.Error(w, "domain, type and name are required", http.StatusBadRequest)
		return
	}

	key := strings.ToLower(req.Domain + "|" + req.Type + "|" + req.Name)
	payload := payloadFrom(req)
	payload["status"] = "active"
	if err := daemonStore.Upsert("dns_records", key, payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	records, err := daemonStore.Map("dns_records")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if err := zoneWriter.WriteZone(req.Domain, records); err != nil {
		payload["status"] = "error"
		_, _ = daemonStore.Record("dns", "write-zone", "error", req.Domain, err.Error(), payload)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "dns", "upsert-record", key, "dns record registered and zone file updated", payload)
}

func handleDNSRecordDelete(w http.ResponseWriter, r *http.Request) {
	var req model.DNSRecordRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if strings.TrimSpace(req.Domain) == "" || strings.TrimSpace(req.Type) == "" || strings.TrimSpace(req.Name) == "" {
		http.Error(w, "domain, type and name are required", http.StatusBadRequest)
		return
	}

	key := strings.ToLower(req.Domain + "|" + req.Type + "|" + req.Name)
	if err := daemonStore.Delete("dns_records", key); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	records, err := daemonStore.Map("dns_records")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if err := zoneWriter.DeleteZoneIfEmpty(req.Domain, records); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if dnslib.HasRecords(req.Domain, records) {
		if err := zoneWriter.WriteZone(req.Domain, records); err != nil {
			payload := payloadFrom(req)
			payload["status"] = "error"
			_, _ = daemonStore.Record("dns", "write-zone", "error", req.Domain, err.Error(), payload)
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
	}

	accepted(w, "dns", "delete-record", key, "dns record removed and zone file updated", payloadFrom(req))
}

func handleNameserversApply(w http.ResponseWriter, r *http.Request) {
	var req model.NameserverApplyRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if len(req.Nameservers) == 0 {
		http.Error(w, "nameservers are required", http.StatusBadRequest)
		return
	}

	payload := payloadFrom(req)
	if err := daemonStore.Replace("nameservers", payload); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	accepted(w, "dns", "apply-nameservers", "default", "nameserver configuration registered in daemon state", payload)
}

// ── NS Lookup ────────────────────────────────────────────────────────────────

func handleNSLookup(w http.ResponseWriter, r *http.Request) {
	domain := strings.TrimSpace(r.URL.Query().Get("domain"))
	if domain == "" {
		var req model.NSLookupRequest
		if !decodeJSON(w, r, &req) {
			return
		}
		domain = req.Domain
	}
	if domain == "" {
		http.Error(w, "domain is required", http.StatusBadRequest)
		return
	}

	ns, nsErr := dnslib.LookupNS(domain)
	aRecords, _ := dnslib.LookupA(domain)

	if nsErr != nil {
		ns = []string{}
	}
	if aRecords == nil {
		aRecords = []string{}
	}

	writeJSON(w, model.NSLookupResponse{
		Domain:      domain,
		Nameservers: ns,
		ARecords:    aRecords,
	})
}

// ── Cloudflare DNS handlers ──────────────────────────────────────────────────

func handleCloudflareDNSUpsert(w http.ResponseWriter, r *http.Request) {
	var req model.CloudflareRecordRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if req.APIToken == "" || req.Domain == "" || req.Type == "" || req.Name == "" {
		http.Error(w, "api_token, domain, type, name are required", http.StatusBadRequest)
		return
	}

	cf := dnslib.NewCloudflareClient(req.APIToken)
	zoneID, err := cf.GetZoneID(req.Domain)
	if err != nil {
		http.Error(w, "cloudflare zone lookup failed: "+err.Error(), http.StatusBadGateway)
		return
	}

	ttl := req.TTL
	if ttl <= 0 {
		ttl = 1 // auto in Cloudflare
	}
	recordID, err := cf.UpsertRecord(zoneID, req.Type, req.Name, req.Value, ttl, req.Proxied)
	if err != nil {
		http.Error(w, "cloudflare record upsert failed: "+err.Error(), http.StatusBadGateway)
		return
	}

	accepted(w, "dns", "cf-upsert", req.Domain+"|"+req.Type+"|"+req.Name, "cloudflare record upserted", map[string]any{
		"zone_id":   zoneID,
		"record_id": recordID,
		"domain":    req.Domain,
		"type":      req.Type,
		"name":      req.Name,
		"value":     req.Value,
	})
}

func handleCloudflareDNSDelete(w http.ResponseWriter, r *http.Request) {
	var req model.CloudflareDeleteRequest
	if !decodeJSON(w, r, &req) {
		return
	}

	if req.APIToken == "" || req.Domain == "" || req.Type == "" || req.Name == "" {
		http.Error(w, "api_token, domain, type, name are required", http.StatusBadRequest)
		return
	}

	cf := dnslib.NewCloudflareClient(req.APIToken)
	zoneID, err := cf.GetZoneID(req.Domain)
	if err != nil {
		http.Error(w, "cloudflare zone lookup failed: "+err.Error(), http.StatusBadGateway)
		return
	}

	if err := cf.DeleteRecord(zoneID, req.Type, req.Name); err != nil {
		http.Error(w, "cloudflare record delete failed: "+err.Error(), http.StatusBadGateway)
		return
	}

	accepted(w, "dns", "cf-delete", req.Domain+"|"+req.Type+"|"+req.Name, "cloudflare record deleted", payloadFrom(req))
}

func handleCloudflareZoneID(w http.ResponseWriter, r *http.Request) {
	var req model.CloudflareZoneIDRequest
	if !decodeJSON(w, r, &req) {
		return
	}
	if req.APIToken == "" || req.Domain == "" {
		http.Error(w, "api_token and domain are required", http.StatusBadRequest)
		return
	}

	cf := dnslib.NewCloudflareClient(req.APIToken)
	zoneID, err := cf.GetZoneID(req.Domain)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadGateway)
		return
	}

	writeJSON(w, map[string]string{"zone_id": zoneID, "domain": req.Domain})
}
