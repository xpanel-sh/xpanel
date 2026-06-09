package state

import (
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

type Store struct {
	base string
	mu   sync.Mutex
}

type Operation struct {
	ID        string         `json:"id"`
	Kind      string         `json:"kind"`
	Action    string         `json:"action"`
	Status    string         `json:"status"`
	Resource  string         `json:"resource"`
	Message   string         `json:"message,omitempty"`
	Payload   map[string]any `json:"payload,omitempty"`
	CreatedAt string         `json:"created_at"`
}

func NewStore() *Store {
	base := os.Getenv("XPANEL_DAEMON_STATE_DIR")
	if base == "" {
		xpanelBase := os.Getenv("XPANEL_BASE")
		if xpanelBase == "" {
			xpanelBase = "/opt/xpanel"
		}
		base = filepath.Join(xpanelBase, "runtime", "daemon")
	}

	return &Store{base: base}
}

func (s *Store) Upsert(collection, key string, value map[string]any) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	items, err := s.readMap(collection)
	if err != nil {
		return err
	}

	value["updated_at"] = now()
	items[key] = value

	return s.writeJSON(collection+".json", items)
}

func (s *Store) Delete(collection, key string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	items, err := s.readMap(collection)
	if err != nil {
		return err
	}

	delete(items, key)
	return s.writeJSON(collection+".json", items)
}

func (s *Store) Map(collection string) (map[string]map[string]any, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	return s.readMap(collection)
}

func (s *Store) Replace(collection string, value any) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	return s.writeJSON(collection+".json", value)
}

func (s *Store) Record(kind, action, status, resource, message string, payload map[string]any) (Operation, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	operations, err := s.readOperations()
	if err != nil {
		return Operation{}, err
	}

	op := Operation{
		ID:        operationID(kind, action, resource),
		Kind:      kind,
		Action:    action,
		Status:    status,
		Resource:  resource,
		Message:   message,
		Payload:   payload,
		CreatedAt: now(),
	}

	operations = append([]Operation{op}, operations...)
	if len(operations) > 500 {
		operations = operations[:500]
	}

	return op, s.writeJSON("operations.json", operations)
}

func (s *Store) Operations() ([]Operation, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	return s.readOperations()
}

func (s *Store) readMap(collection string) (map[string]map[string]any, error) {
	items := map[string]map[string]any{}
	err := s.readJSON(collection+".json", &items)
	return items, err
}

func (s *Store) readOperations() ([]Operation, error) {
	operations := []Operation{}
	err := s.readJSON("operations.json", &operations)
	return operations, err
}

func (s *Store) readJSON(name string, target any) error {
	path := filepath.Join(s.base, name)
	if _, err := os.Stat(path); os.IsNotExist(err) {
		return nil
	}

	data, err := os.ReadFile(path)
	if err != nil {
		return err
	}

	if len(strings.TrimSpace(string(data))) == 0 {
		return nil
	}

	return json.Unmarshal(data, target)
}

func (s *Store) writeJSON(name string, value any) error {
	if err := os.MkdirAll(s.base, 0750); err != nil {
		return err
	}

	data, err := json.MarshalIndent(value, "", "  ")
	if err != nil {
		return err
	}

	path := filepath.Join(s.base, name)
	tmp := path + ".tmp"
	if err := os.WriteFile(tmp, data, 0640); err != nil {
		return err
	}

	return os.Rename(tmp, path)
}

func now() string {
	return time.Now().UTC().Format(time.RFC3339)
}

func operationID(parts ...string) string {
	raw := strings.Join(parts, "-") + "-" + time.Now().UTC().Format("20060102150405.000000000")
	raw = strings.ToLower(raw)
	raw = strings.Map(func(r rune) rune {
		if (r >= 'a' && r <= 'z') || (r >= '0' && r <= '9') {
			return r
		}
		return '-'
	}, raw)
	return strings.Trim(raw, "-")
}
