package mail

import (
	"bytes"
	"context"
	"fmt"
	"os/exec"
	"strings"
	"time"
)

const mailContainer = "xpanel-mail"

// AccountManager manages email accounts through docker-mailserver's setup CLI.
// It delegates to the container so passwords are always hashed by the container.
type AccountManager struct{}

func NewAccountManager() *AccountManager {
	return &AccountManager{}
}

func (m *AccountManager) Add(ctx context.Context, email, password string) error {
	return m.setup(ctx, "email", "add", email, password)
}

func (m *AccountManager) Delete(ctx context.Context, email string) error {
	return m.setup(ctx, "email", "del", email)
}

func (m *AccountManager) UpdatePassword(ctx context.Context, email, password string) error {
	return m.setup(ctx, "email", "update", email, password)
}

func (m *AccountManager) setup(ctx context.Context, args ...string) error {
	ctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	cmdArgs := append([]string{"exec", mailContainer, "setup"}, args...)
	cmd := exec.CommandContext(ctx, "docker", cmdArgs...)

	var out bytes.Buffer
	cmd.Stdout = &out
	cmd.Stderr = &out

	if err := cmd.Run(); err != nil {
		return fmt.Errorf("docker-mailserver setup %s failed: %s", strings.Join(args[:2], " "), strings.TrimSpace(out.String()))
	}
	return nil
}
