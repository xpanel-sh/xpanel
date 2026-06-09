package api

import (
	"net/http"
	"strings"

	dnswriter "xpanel/internal/dns"
	model "xpanel/internal/types"
)

var zoneWriter = dnswriter.NewZoneWriter()

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
	if dnswriter.HasRecords(req.Domain, records) {
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
