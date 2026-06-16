package database

import (
	"bytes"
	"context"
	"fmt"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"
	"time"

	xenv "xpanel/internal/env"
	model "xpanel/internal/types"
)

type Manager struct {
	basePath string
}

func NewManager() *Manager {
	return &Manager{basePath: xenv.BasePath()}
}

func (m *Manager) Create(ctx context.Context, req model.DatabaseRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("database engine %q is not implemented yet", req.Engine)
	}

	if err := validateIdentifier(req.Name, "database name"); err != nil {
		return err
	}
	if err := validateIdentifier(req.Username, "database username"); err != nil {
		return err
	}
	if strings.TrimSpace(req.Password) == "" {
		return fmt.Errorf("database password is required")
	}

	sql := fmt.Sprintf(
		"CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED BY %s; ALTER USER '%s'@'%%' IDENTIFIED BY %s; GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'; FLUSH PRIVILEGES;",
		escapeIdentifier(req.Name),
		escapeSQLString(req.Username),
		quoteSQLString(req.Password),
		escapeSQLString(req.Username),
		quoteSQLString(req.Password),
		escapeIdentifier(req.Name),
		escapeSQLString(req.Username),
	)

	return m.execMariaDB(ctx, sql)
}

var allowedPrivileges = map[string]bool{
	"SELECT": true, "INSERT": true, "UPDATE": true, "DELETE": true,
	"CREATE": true, "DROP": true, "INDEX": true, "ALTER": true,
	"REFERENCES": true, "ALL PRIVILEGES": true,
}

func (m *Manager) UpdatePermissions(ctx context.Context, req model.DatabasePermissionsRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("engine %q not supported", req.Engine)
	}
	if err := validateIdentifier(req.Name, "database name"); err != nil {
		return err
	}
	if err := validateIdentifier(req.Username, "username"); err != nil {
		return err
	}

	var grants []string
	for _, p := range req.Privileges {
		p = strings.ToUpper(strings.TrimSpace(p))
		if !allowedPrivileges[p] {
			return fmt.Errorf("privilege %q not allowed", p)
		}
		grants = append(grants, p)
	}
	grantList := strings.Join(grants, ", ")

	var sql string
	if len(grants) == 0 {
		// GRANT ALL primero garantiza que exista fila en mysql.db;
		// luego REVOKE ALL la vacía sin error 1141.
		sql = fmt.Sprintf(
			"GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'; REVOKE ALL PRIVILEGES ON `%s`.* FROM '%s'@'%%'; FLUSH PRIVILEGES;",
			escapeIdentifier(req.Name), escapeSQLString(req.Username),
			escapeIdentifier(req.Name), escapeSQLString(req.Username),
		)
	} else {
		// Igual: GRANT ALL crea/actualiza la fila, REVOKE limpia, GRANT aplica solo los elegidos.
		sql = fmt.Sprintf(
			"GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'; REVOKE ALL PRIVILEGES ON `%s`.* FROM '%s'@'%%'; GRANT %s ON `%s`.* TO '%s'@'%%'; FLUSH PRIVILEGES;",
			escapeIdentifier(req.Name), escapeSQLString(req.Username),
			escapeIdentifier(req.Name), escapeSQLString(req.Username),
			grantList,
			escapeIdentifier(req.Name), escapeSQLString(req.Username),
		)
	}
	return m.execMariaDB(ctx, sql)
}

func (m *Manager) AddUser(ctx context.Context, req model.DatabaseUserRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("engine %q not supported", req.Engine)
	}
	if err := validateIdentifier(req.Database, "database name"); err != nil {
		return err
	}
	if err := validateIdentifier(req.Username, "username"); err != nil {
		return err
	}
	if strings.TrimSpace(req.Password) == "" {
		return fmt.Errorf("password is required")
	}

	sql := fmt.Sprintf(
		"CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED BY %s; ALTER USER '%s'@'%%' IDENTIFIED BY %s; GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'; FLUSH PRIVILEGES;",
		escapeSQLString(req.Username), quoteSQLString(req.Password),
		escapeSQLString(req.Username), quoteSQLString(req.Password),
		escapeIdentifier(req.Database),
		escapeSQLString(req.Username),
	)
	return m.execMariaDB(ctx, sql)
}

func (m *Manager) RemoveUser(ctx context.Context, req model.DatabaseUserRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("engine %q not supported", req.Engine)
	}
	if err := validateIdentifier(req.Database, "database name"); err != nil {
		return err
	}
	if err := validateIdentifier(req.Username, "username"); err != nil {
		return err
	}

	sql := fmt.Sprintf(
		"REVOKE ALL PRIVILEGES ON `%s`.* FROM '%s'@'%%'; DROP USER IF EXISTS '%s'@'%%'; FLUSH PRIVILEGES;",
		escapeIdentifier(req.Database),
		escapeSQLString(req.Username),
		escapeSQLString(req.Username),
	)
	return m.execMariaDB(ctx, sql)
}

func (m *Manager) ChangeUserPassword(ctx context.Context, req model.DatabaseUserRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("engine %q not supported", req.Engine)
	}
	if err := validateIdentifier(req.Username, "username"); err != nil {
		return err
	}
	if strings.TrimSpace(req.Password) == "" {
		return fmt.Errorf("password is required")
	}

	sql := fmt.Sprintf(
		"ALTER USER '%s'@'%%' IDENTIFIED BY %s; FLUSH PRIVILEGES;",
		escapeSQLString(req.Username),
		quoteSQLString(req.Password),
	)
	return m.execMariaDB(ctx, sql)
}

func (m *Manager) Delete(ctx context.Context, req model.DatabaseRequest) error {
	engine := strings.ToLower(strings.TrimSpace(req.Engine))
	if engine != "mariadb" && engine != "mysql" {
		return fmt.Errorf("database engine %q is not implemented yet", req.Engine)
	}

	if err := validateIdentifier(req.Name, "database name"); err != nil {
		return err
	}
	if err := validateIdentifier(req.Username, "database username"); err != nil {
		return err
	}

	sql := fmt.Sprintf(
		"DROP DATABASE IF EXISTS `%s`; DROP USER IF EXISTS '%s'@'%%'; FLUSH PRIVILEGES;",
		escapeIdentifier(req.Name),
		escapeSQLString(req.Username),
	)

	return m.execMariaDB(ctx, sql)
}

func (m *Manager) execMariaDB(ctx context.Context, sql string) error {
	rootPassword := m.rootPassword()
	if rootPassword == "" {
		return fmt.Errorf("MYSQL_ROOT_PASSWORD not found in %s", filepath.Join(m.basePath, ".env"))
	}

	ctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	cmd := exec.CommandContext(ctx, "docker", "exec", "-i", "-e", "MYSQL_PWD="+rootPassword, "xpanel-db", "mariadb", "-uroot")
	cmd.Stdin = strings.NewReader(sql)

	var out bytes.Buffer
	cmd.Stdout = &out
	cmd.Stderr = &out

	if err := cmd.Run(); err != nil {
		return fmt.Errorf("mariadb exec failed: %s", strings.TrimSpace(out.String()))
	}

	return nil
}

func (m *Manager) rootPassword() string {
	values := xenv.ReadFile(filepath.Join(m.basePath, ".env"))
	return values["MYSQL_ROOT_PASSWORD"]
}

func validateIdentifier(value string, label string) error {
	value = strings.TrimSpace(value)
	if value == "" {
		return fmt.Errorf("%s is required", label)
	}

	if len(value) > 64 {
		return fmt.Errorf("%s must be 64 characters or less", label)
	}

	if !regexp.MustCompile(`^[A-Za-z0-9_]+$`).MatchString(value) {
		return fmt.Errorf("%s may only contain letters, numbers and underscores", label)
	}

	return nil
}

func escapeIdentifier(value string) string {
	return strings.ReplaceAll(value, "`", "``")
}

func escapeSQLString(value string) string {
	return strings.ReplaceAll(value, `'`, `''`)
}

func quoteSQLString(value string) string {
	return "'" + escapeSQLString(value) + "'"
}
