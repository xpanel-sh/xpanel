package docker

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"
	"time"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

type AppManager struct {
	basePath string
}

func NewAppManager() *AppManager {
	return &AppManager{basePath: xenv.BasePath()}
}

var slugPattern = regexp.MustCompile(`^[a-z0-9][a-z0-9-]{0,48}[a-z0-9]$`)
var tenantCodePattern = regexp.MustCompile(`^[A-Za-z0-9]{3,16}$`)

func (m *AppManager) instanceDir(tenantCode, slug string) string {
	return filepath.Join(m.basePath, "runtime", "docker", strings.ToLower(tenantCode), slug)
}

func (m *AppManager) projectName(tenantCode, slug string) string {
	return "xpanel-docker-" + strings.ToLower(tenantCode) + "-" + slug
}

func (m *AppManager) composePath(tenantCode, slug string) string {
	return filepath.Join(m.instanceDir(tenantCode, slug), "docker-compose.yml")
}

func (m *AppManager) validate(tenantCode, slug string) error {
	if !tenantCodePattern.MatchString(tenantCode) {
		return fmt.Errorf("tenant_code inválido")
	}
	if !slugPattern.MatchString(slug) {
		return fmt.Errorf("slug inválido: debe ser minúsculas, números y guiones, 2-50 chars")
	}
	return nil
}

func (m *AppManager) Create(ctx context.Context, req model.DockerAppCreateRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}
	if strings.TrimSpace(req.ComposeYAML) == "" {
		return fmt.Errorf("compose_yaml es requerido")
	}

	dir := m.instanceDir(req.TenantCode, req.Slug)
	if err := os.MkdirAll(dir, 0755); err != nil {
		return fmt.Errorf("no se pudo crear directorio: %w", err)
	}

	composePath := m.composePath(req.TenantCode, req.Slug)
	if err := os.WriteFile(composePath, []byte(req.ComposeYAML), 0644); err != nil {
		return fmt.Errorf("no se pudo escribir docker-compose.yml: %w", err)
	}

	return m.composeUp(ctx, req.TenantCode, req.Slug)
}

func (m *AppManager) Update(ctx context.Context, req model.DockerAppUpdateRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}
	if strings.TrimSpace(req.ComposeYAML) == "" {
		return fmt.Errorf("compose_yaml es requerido")
	}

	composePath := m.composePath(req.TenantCode, req.Slug)
	if _, err := os.Stat(composePath); os.IsNotExist(err) {
		return fmt.Errorf("instancia no encontrada")
	}

	if err := os.WriteFile(composePath, []byte(req.ComposeYAML), 0644); err != nil {
		return fmt.Errorf("no se pudo actualizar docker-compose.yml: %w", err)
	}

	return m.composeUp(ctx, req.TenantCode, req.Slug)
}

func (m *AppManager) Start(ctx context.Context, req model.DockerAppActionRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}
	return m.composeUp(ctx, req.TenantCode, req.Slug)
}

func (m *AppManager) Stop(ctx context.Context, req model.DockerAppActionRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}
	return m.runCompose(ctx, req.TenantCode, req.Slug, 30*time.Second, "stop")
}

func (m *AppManager) Restart(ctx context.Context, req model.DockerAppActionRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}
	return m.runCompose(ctx, req.TenantCode, req.Slug, 60*time.Second, "restart")
}

func (m *AppManager) Delete(ctx context.Context, req model.DockerAppActionRequest) error {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return err
	}

	composePath := m.composePath(req.TenantCode, req.Slug)
	if _, err := os.Stat(composePath); os.IsNotExist(err) {
		return nil // ya eliminado
	}

	// down --volumes elimina contenedores y volúmenes anónimos
	if err := m.runCompose(ctx, req.TenantCode, req.Slug, 60*time.Second, "down", "--volumes", "--remove-orphans"); err != nil {
		return err
	}

	return os.RemoveAll(m.instanceDir(req.TenantCode, req.Slug))
}

func (m *AppManager) Status(ctx context.Context, req model.DockerAppActionRequest) (model.DockerAppStatusResponse, error) {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return model.DockerAppStatusResponse{Status: "not_found"}, err
	}

	composePath := m.composePath(req.TenantCode, req.Slug)
	if _, err := os.Stat(composePath); os.IsNotExist(err) {
		return model.DockerAppStatusResponse{Status: "not_found"}, nil
	}

	ctx, cancel := context.WithTimeout(ctx, 15*time.Second)
	defer cancel()

	out, err := m.composeOutput(ctx, req.TenantCode, req.Slug, "ps", "--format", "json")
	if err != nil {
		return model.DockerAppStatusResponse{Status: "error"}, nil
	}

	services := parseComposePS(out)

	status := deriveStatus(services)
	return model.DockerAppStatusResponse{Status: status, Services: services}, nil
}

func (m *AppManager) Logs(ctx context.Context, req model.DockerAppActionRequest, tail int) (string, error) {
	if err := m.validate(req.TenantCode, req.Slug); err != nil {
		return "", err
	}

	tailStr := fmt.Sprintf("%d", tail)
	ctx, cancel := context.WithTimeout(ctx, 15*time.Second)
	defer cancel()

	out, err := m.composeOutput(ctx, req.TenantCode, req.Slug, "logs", "--no-color", "--tail", tailStr)
	if err != nil {
		return "", err
	}
	return out, nil
}

// ── helpers ──────────────────────────────────────────────────────────────────

func (m *AppManager) composeUp(ctx context.Context, tenantCode, slug string) error {
	return m.runCompose(ctx, tenantCode, slug, 120*time.Second, "up", "-d", "--remove-orphans")
}

func (m *AppManager) runCompose(ctx context.Context, tenantCode, slug string, timeout time.Duration, args ...string) error {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	out, err := m.composeOutput(ctx, tenantCode, slug, args...)
	if err != nil {
		return fmt.Errorf("docker compose %s falló: %s", args[0], strings.TrimSpace(out))
	}
	return nil
}

func (m *AppManager) composeOutput(ctx context.Context, tenantCode, slug string, args ...string) (string, error) {
	composePath := m.composePath(tenantCode, slug)
	project := m.projectName(tenantCode, slug)

	cmdArgs := append([]string{"compose", "--project-name", project, "-f", composePath}, args...)
	cmd := exec.CommandContext(ctx, "docker", cmdArgs...)

	var buf bytes.Buffer
	cmd.Stdout = &buf
	cmd.Stderr = &buf

	err := cmd.Run()
	return buf.String(), err
}

// parseComposePS parsea la salida JSON de `docker compose ps --format json`
func parseComposePS(output string) []model.DockerServiceStatus {
	var services []model.DockerServiceStatus

	// docker compose ps --format json puede devolver un array o líneas JSON separadas
	output = strings.TrimSpace(output)
	if output == "" {
		return services
	}

	// Intentar como array JSON
	if strings.HasPrefix(output, "[") {
		var rows []map[string]any
		if err := json.Unmarshal([]byte(output), &rows); err == nil {
			for _, row := range rows {
				services = append(services, rowToServiceStatus(row))
			}
			return services
		}
	}

	// Intentar línea por línea (NDJSON)
	for _, line := range strings.Split(output, "\n") {
		line = strings.TrimSpace(line)
		if line == "" || line == "[]" {
			continue
		}
		var row map[string]any
		if err := json.Unmarshal([]byte(line), &row); err == nil {
			services = append(services, rowToServiceStatus(row))
		}
	}
	return services
}

func rowToServiceStatus(row map[string]any) model.DockerServiceStatus {
	name, _ := row["Name"].(string)
	if name == "" {
		name, _ = row["Service"].(string)
	}
	state, _ := row["State"].(string)
	if state == "" {
		state, _ = row["Status"].(string)
	}
	health, _ := row["Health"].(string)
	return model.DockerServiceStatus{Name: name, State: strings.ToLower(state), Health: health}
}

func deriveStatus(services []model.DockerServiceStatus) string {
	if len(services) == 0 {
		return "stopped"
	}
	running := 0
	for _, s := range services {
		if strings.Contains(s.State, "running") || strings.Contains(s.State, "up") {
			running++
		}
	}
	if running == 0 {
		return "stopped"
	}
	if running == len(services) {
		return "running"
	}
	return "partial"
}
