package dns

import (
	"net"
	"sort"
	"strings"
)

// LookupNS returns the nameservers currently configured for a domain via public DNS.
func LookupNS(domain string) ([]string, error) {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	records, err := net.LookupNS(domain)
	if err != nil {
		return nil, err
	}
	ns := make([]string, 0, len(records))
	for _, r := range records {
		ns = append(ns, strings.TrimSuffix(strings.ToLower(r.Host), "."))
	}
	sort.Strings(ns)
	return ns, nil
}

// LookupA returns the IPv4 addresses for a domain.
func LookupA(domain string) ([]string, error) {
	domain = strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
	addrs, err := net.LookupHost(domain)
	if err != nil {
		return nil, err
	}
	result := []string{}
	for _, a := range addrs {
		if !strings.Contains(a, ":") { // skip IPv6
			result = append(result, a)
		}
	}
	return result, nil
}
