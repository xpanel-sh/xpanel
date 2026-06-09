package docker

import (
	"context"
	"fmt"
	"regexp"
	"strings"

	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/api/types/network"
	"github.com/docker/docker/client"
	"github.com/docker/go-connections/nat"
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

	// 1. Configurar imagen usando el Selector
	imageName, cmd := SelectImage(req.Type, req.WebServer, req.PhpVersion)

	if _, _, err := m.cli.ImageInspectWithRaw(ctx, imageName); err != nil {
		if client.IsErrNotFound(err) {
			return "", fmt.Errorf("site image %s is not available locally; pre-pull it before creating this site", imageName)
		}
		return "", fmt.Errorf("could not inspect site image %s", imageName)
	}

	routerName := strings.TrimPrefix(containerName, siteContainerPrefix)

	// 2. Configurar Contenedor
	config := &container.Config{
		Image: imageName,
		Cmd:   cmd,
		Labels: map[string]string{
			"traefik.enable": "true",
			fmt.Sprintf("traefik.http.routers.%s.rule", routerName):             fmt.Sprintf("Host(`%s`)", strings.ToLower(req.Domain)),
			fmt.Sprintf("traefik.http.routers.%s.entrypoints", routerName):      "websecure",
			fmt.Sprintf("traefik.http.routers.%s.tls.certresolver", routerName): "myresolver",
			"xpanel.managed":      "true",
			"xpanel.kind":         "site",
			"xpanel.domain":       strings.ToLower(req.Domain),
			"xpanel.project_type": req.Type,
		},
	}

	hostConfig := &container.HostConfig{
		NetworkMode: "xpanel-net", // Debe coincidir con docker-compose
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

	return resp.ID, nil
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

	labels := inspected.Config.Labels
	if labels["xpanel.managed"] != "true" || labels["xpanel.kind"] != "site" {
		return "", fmt.Errorf("container is not a managed XPanel site")
	}

	return inspected.ID, nil
}

func siteContainerName(req model.CreateSiteRequest) (string, error) {
	domain := strings.TrimSuffix(strings.ToLower(strings.TrimSpace(req.Domain)), ".")
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
