package dns

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
)

const cfBase = "https://api.cloudflare.com/client/v4"

type CloudflareClient struct {
	token  string
	client *http.Client
}

func NewCloudflareClient(token string) *CloudflareClient {
	return &CloudflareClient{token: token, client: &http.Client{}}
}

type cfZone struct {
	ID   string `json:"id"`
	Name string `json:"name"`
}

type cfRecord struct {
	ID      string `json:"id"`
	Type    string `json:"type"`
	Name    string `json:"name"`
	Content string `json:"content"`
	TTL     int    `json:"ttl"`
	Proxied bool   `json:"proxied"`
}

type cfResponse struct {
	Success bool            `json:"success"`
	Errors  []cfError       `json:"errors"`
	Result  json.RawMessage `json:"result"`
}

type cfError struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
}

func (c *CloudflareClient) do(method, path string, body any) (json.RawMessage, error) {
	var reqBody io.Reader
	if body != nil {
		data, err := json.Marshal(body)
		if err != nil {
			return nil, err
		}
		reqBody = bytes.NewReader(data)
	}
	req, err := http.NewRequest(method, cfBase+path, reqBody)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "Bearer "+c.token)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var cfResp cfResponse
	if err := json.NewDecoder(resp.Body).Decode(&cfResp); err != nil {
		return nil, fmt.Errorf("cloudflare decode error: %w", err)
	}
	if !cfResp.Success {
		msgs := make([]string, 0, len(cfResp.Errors))
		for _, e := range cfResp.Errors {
			msgs = append(msgs, fmt.Sprintf("[%d] %s", e.Code, e.Message))
		}
		return nil, fmt.Errorf("cloudflare API error: %s", strings.Join(msgs, "; "))
	}
	return cfResp.Result, nil
}

// GetZoneID returns the Cloudflare Zone ID for a given domain.
func (c *CloudflareClient) GetZoneID(domain string) (string, error) {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	raw, err := c.do("GET", "/zones?name="+domain+"&status=active", nil)
	if err != nil {
		return "", err
	}
	var zones []cfZone
	if err := json.Unmarshal(raw, &zones); err != nil {
		return "", err
	}
	if len(zones) == 0 {
		return "", fmt.Errorf("zone not found in Cloudflare for domain: %s", domain)
	}
	return zones[0].ID, nil
}

// ListRecords returns all DNS records for a zone.
func (c *CloudflareClient) ListRecords(zoneID string) ([]cfRecord, error) {
	raw, err := c.do("GET", "/zones/"+zoneID+"/dns_records?per_page=100", nil)
	if err != nil {
		return nil, err
	}
	var records []cfRecord
	if err := json.Unmarshal(raw, &records); err != nil {
		return nil, err
	}
	return records, nil
}

// UpsertRecord creates or updates a DNS record in Cloudflare.
// If a record with same type+name exists, it updates it; otherwise creates.
func (c *CloudflareClient) UpsertRecord(zoneID, recType, name, content string, ttl int, proxied bool) (string, error) {
	existing, err := c.findRecord(zoneID, recType, name)
	if err != nil {
		return "", err
	}

	payload := map[string]any{
		"type":    strings.ToUpper(recType),
		"name":    name,
		"content": content,
		"ttl":     ttl,
		"proxied": proxied,
	}

	if existing != nil {
		_, err := c.do("PUT", "/zones/"+zoneID+"/dns_records/"+existing.ID, payload)
		return existing.ID, err
	}
	raw, err := c.do("POST", "/zones/"+zoneID+"/dns_records", payload)
	if err != nil {
		return "", err
	}
	var created cfRecord
	if err := json.Unmarshal(raw, &created); err != nil {
		return "", err
	}
	return created.ID, nil
}

// DeleteRecord removes a DNS record from Cloudflare by type+name match.
func (c *CloudflareClient) DeleteRecord(zoneID, recType, name string) error {
	existing, err := c.findRecord(zoneID, recType, name)
	if err != nil {
		return err
	}
	if existing == nil {
		return nil // already gone
	}
	_, err = c.do("DELETE", "/zones/"+zoneID+"/dns_records/"+existing.ID, nil)
	return err
}

func (c *CloudflareClient) findRecord(zoneID, recType, name string) (*cfRecord, error) {
	records, err := c.ListRecords(zoneID)
	if err != nil {
		return nil, err
	}
	name = strings.ToLower(strings.TrimSpace(name))
	recType = strings.ToUpper(recType)
	for _, r := range records {
		if strings.ToUpper(r.Type) == recType && strings.ToLower(r.Name) == name {
			return &r, nil
		}
	}
	return nil, nil
}
