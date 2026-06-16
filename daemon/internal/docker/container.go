package docker

import (
	"context"
	"fmt"
	"path/filepath"
	"regexp"
	"strings"

	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/api/types/network"
	"github.com/docker/docker/client"
	"github.com/docker/go-connections/nat"
	"xpanel/internal/sites"
	model "xpanel/internal/types"
)

type Manager struct {
	cli *client.Client
}

const siteContainerPrefix = "xpanel-site-"

var siteContainerPattern = regexp.MustCompile(`^xpanel-site-[a-z0-9][a-z0-9-]{0,238}[a-z0-9]$`)
var siteDomainPattern = regexp.MustCompile(`^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$`)

func (m *Manager) RestartSiteContainer(ctx context.Context, name string) error {
	containerID, err := m.managedSiteContainerID(ctx, name)
	if err != nil {
		return err
	}

	return m.cli.ContainerRestart(ctx, containerID, container.StopOptions{})
}

func (m *Manager) DeleteSiteContainer(ctx context.Context, name string) error {
	containerID, err := m.managedSiteContainerID(ctx, name)
	if err != nil {
		return err
	}

	timeout := 10
	_ = m.cli.ContainerStop(ctx, containerID, container.StopOptions{Timeout: &timeout})
	return m.cli.ContainerRemove(ctx, containerID, container.RemoveOptions{Force: true})
}

func NewManager() (*Manager, error) {
	cli, err := client.NewClientWithOpts(client.FromEnv, client.WithAPIVersionNegotiation())
	if err != nil {
		return nil, err
	}
	return &Manager{cli: cli}, nil
}

func (m *Manager) CreateSiteContainer(ctx context.Context, req model.CreateSiteRequest) (string, error) {
	containerName, err := siteContainerName(req)
	if err != nil {
		return "", err
	}
	domain := normalizedSiteDomain(req.Domain)

	// 1. Configurar imagen usando el Selector
	imageName, cmd := SelectImage(req.Type, req.WebServer, req.PhpVersion)

	if _, _, err := m.cli.ImageInspectWithRaw(ctx, imageName); err != nil {
		if client.IsErrNotFound(err) {
			return "", fmt.Errorf("site image %s is not available locally; pre-pull it before creating this site", imageName)
		}
		return "", fmt.Errorf("could not inspect site image %s", imageName)
	}

	routerName := strings.TrimPrefix(containerName, siteContainerPrefix)
	preparedSite, err := sites.Prepare(req)
	if err != nil {
		return "", err
	}

	if existing, err := m.cli.ContainerInspect(ctx, containerName); err == nil {
		labels := containerLabels(existing)
		if !isManagedSite(labels) {
			return "", fmt.Errorf("container %s already exists and is not a managed XPanel site", containerName)
		}

		if existingDomain := strings.TrimSpace(labels["xpanel.domain"]); existingDomain != "" && existingDomain != domain {
			return "", fmt.Errorf("container %s is managed by XPanel but belongs to another domain", containerName)
		}

		if siteHasExpectedMount(existing, preparedSite) {
			if existing.State != nil && !existing.State.Running {
				if err := m.cli.ContainerStart(ctx, existing.ID, types.ContainerStartOptions{}); err != nil {
					return "", err
				}
			}

			return existing.ID, nil
		}

		if err := m.removeContainer(ctx, existing.ID); err != nil {
			return "", fmt.Errorf("could not repair site container %s: %w", containerName, err)
		}
	} else if !client.IsErrNotFound(err) {
		return "", err
	}

	// 2. Configurar Contenedor
	config := &container.Config{
		Image:      imageName,
		Cmd:        cmd,
		WorkingDir: preparedSite.WorkingDir,
		Env: []string{
			"XPANEL_MYSQL_HOST=xpanel-db",
			"XPANEL_MYSQL_PORT=3306",
		},
		Labels: map[string]string{
			"traefik.enable": "true",
			fmt.Sprintf("traefik.http.routers.%s.rule", routerName):             fmt.Sprintf("Host(`%s`)", domain),
			fmt.Sprintf("traefik.http.routers.%s.entrypoints", routerName):      "websecure",
			fmt.Sprintf("traefik.http.routers.%s.tls.certresolver", routerName): "myresolver",
			"xpanel.managed":      "true",
			"xpanel.kind":         "site",
			"xpanel.domain":       domain,
			"xpanel.project_type": req.Type,
		},
	}

	binds := []string{
		fmt.Sprintf("%s:%s", preparedSite.HostDir, preparedSite.TargetDir),
	}
	if req.Type == "php" {
		iniPath := filepath.Join(preparedSite.HostDir, "php.ini")
		binds = append(binds, iniPath+":/usr/local/etc/php/conf.d/90-xpanel.ini")
	}

	hostConfig := &container.HostConfig{
		NetworkMode: "xpanel-net", // Debe coincidir con docker-compose
		Binds:       binds,
		PortBindings: nat.PortMap{
			"80/tcp": []nat.PortBinding{}, // Traefik maneja el puerto, no exponemos al host
		},
	}

	networkingConfig := &network.NetworkingConfig{
		EndpointsConfig: map[string]*network.EndpointSettings{
			"xpanel-net": {},
		},
	}

	// 3. Crear
	resp, err := m.cli.ContainerCreate(ctx, config, hostConfig, networkingConfig, nil, containerName)
	if err != nil {
		return "", err
	}

	// 4. Iniciar
	if err := m.cli.ContainerStart(ctx, resp.ID, types.ContainerStartOptions{}); err != nil {
		return "", err
	}

	inspected, err := m.cli.ContainerInspect(ctx, resp.ID)
	if err != nil {
		return "", err
	}
	if !siteHasExpectedMount(inspected, preparedSite) {
		return "", fmt.Errorf("site container %s was created without the expected mount %s:%s", containerName, preparedSite.HostDir, preparedSite.TargetDir)
	}
	if inspected.State == nil || (!inspected.State.Running && !inspected.State.Restarting) {
		exitCode := 0
		exitMsg := "unknown"
		if inspected.State != nil {
			exitCode = inspected.State.ExitCode
			exitMsg = inspected.State.Error
			if exitMsg == "" {
				exitMsg = inspected.State.Status
			}
		}
		_ = m.cli.ContainerRemove(ctx, resp.ID, container.RemoveOptions{Force: true})
		return "", fmt.Errorf("site container %s exited immediately after start (exit code %d: %s)", containerName, exitCode, exitMsg)
	}

	return resp.ID, nil
}

// SiteContainerStatus returns the real-time state of a site container.
// Returns: running | restarting | provisioning | exited | not_found
func (m *Manager) SiteContainerStatus(ctx context.Context, name string) (map[string]any, error) {
	containerName, err := normalizeSiteContainerName(name)
	if err != nil {
		return nil, err
	}

	inspected, err := m.cli.ContainerInspect(ctx, containerName)
	if err != nil {
		if client.IsErrNotFound(err) {
			return map[string]any{"status": "not_found"}, nil
		}
		return nil, err
	}

	labels := containerLabels(inspected)
	if !isManagedSite(labels) {
		return map[string]any{"status": "not_found"}, nil
	}

	state := "unknown"
	exitCode := 0
	if inspected.State != nil {
		switch {
		case inspected.State.Running:
			state = "running"
		case inspected.State.Restarting:
			state = "restarting"
		default:
			state = "exited"
			exitCode = inspected.State.ExitCode
		}
	}

	return map[string]any{
		"status":    state,
		"exit_code": exitCode,
		"domain":    labels["xpanel.domain"],
	}, nil
}

func (m *Manager) removeContainer(ctx context.Context, id string) error {
	timeout := 10
	_ = m.cli.ContainerStop(ctx, id, container.StopOptions{Timeout: &timeout})
	return m.cli.ContainerRemove(ctx, id, container.RemoveOptions{Force: true})
}

func (m *Manager) managedSiteContainerID(ctx context.Context, name string) (string, error) {
	containerName, err := normalizeSiteContainerName(name)
	if err != nil {
		return "", err
	}

	inspected, err := m.cli.ContainerInspect(ctx, containerName)
	if err != nil {
		return "", err
	}

	labels := containerLabels(inspected)
	if !isManagedSite(labels) {
		return "", fmt.Errorf("container is not a managed XPanel site")
	}

	return inspected.ID, nil
}

func siteContainerName(req model.CreateSiteRequest) (string, error) {
	domain := normalizedSiteDomain(req.Domain)
	if !siteDomainPattern.MatchString(domain) {
		return "", fmt.Errorf("domain is invalid")
	}

	expectedName := siteContainerPrefix + strings.ReplaceAll(domain, ".", "-")
	if strings.TrimSpace(req.Name) != "" {
		requestedName, err := normalizeSiteContainerName(req.Name)
		if err != nil {
			return "", err
		}
		if requestedName != expectedName {
			return "", fmt.Errorf("container name does not match domain")
		}
	}

	return expectedName, nil
}

func normalizeSiteContainerName(name string) (string, error) {
	containerName := strings.ToLower(strings.TrimSpace(name))
	if !siteContainerPattern.MatchString(containerName) {
		return "", fmt.Errorf("container name is invalid")
	}

	return containerName, nil
}

func normalizedSiteDomain(domain string) string {
	return strings.TrimSuffix(strings.ToLower(strings.TrimSpace(domain)), ".")
}

func containerLabels(inspected types.ContainerJSON) map[string]string {
	if inspected.Config == nil {
		return map[string]string{}
	}

	return inspected.Config.Labels
}

func isManagedSite(labels map[string]string) bool {
	return labels["xpanel.managed"] == "true" && labels["xpanel.kind"] == "site"
}

func siteHasExpectedMount(inspected types.ContainerJSON, preparedSite sites.PreparedSite) bool {
	expectedSource := filepath.Clean(preparedSite.HostDir)
	expectedDestination := filepath.Clean(preparedSite.TargetDir)

	for _, mount := range inspected.Mounts {
		if filepath.Clean(mount.Source) == expectedSource && filepath.Clean(mount.Destination) == expectedDestination {
			return true
		}
	}

	return false
}
