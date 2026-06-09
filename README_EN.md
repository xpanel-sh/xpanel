# XPanel

XPanel is an **open-source hosting and server management panel** inspired by ideas from aaPanel, WHM and cPanel,
built with a **modern, modular, multi-tenant and Docker-native architecture**.

---

## 🎯 Project Goals

- Simplify server administration
- Enable multi-tenant platforms
- Avoid OS-level conflicts
- Safe upgrades without breaking installs
- Full control via CLI (`xpanel`)
- Clear separation between the global Admin panel and the Client panel
- Use a daemon/agent for system operations, not direct shell commands from Laravel

---

## ✨ Features

- 🐳 Docker-based services
- 🧩 Modular architecture
- 🏢 Multi-tenant ready
- 🌍 Multi-language installer (EN / ES)
- ⚙️ Laravel panel + Go daemon
- 🧠 Smart CLI (`xpanel`)
- 🔄 Safe updates
- 💾 Integrated backups
- 🔐 Separate Admin and Client login paths
- 🧼 Clean install mode with `--fresh`

---

## 🧭 Panel Model

XPanel has two main experiences:

- **Global Admin:** manages clients, global sites, connected servers, configuration and platform health.
- **Client Panel:** manages only the client's resources: sites, databases and future mail, files, SSL and backup modules.

Backend logic can be shared through internal services, while routes, permissions and views stay separated.

---

## 🆚 Comparison

| Feature              | XPanel | aaPanel | cPanel |
|---------------------|--------|--------|--------|
| Open Source         | ✅     | ✅     | ❌     |
| Docker Native       | ✅     | ⚠️     | ❌     |
| Advanced CLI        | ✅     | ❌     | ❌     |
| Multi-tenant        | ✅     | ⚠️     | ❌     |
| Modern Architecture | ✅     | ⚠️     | ❌     |

---

## 📦 Requirements

- Clean OS (no Apache/Nginx/MySQL installed)
- Docker support
- 512MB RAM (1GB recommended)
- Root access

---

## Supported Systems

XPanel is designed to run on Linux servers.

### Officially supported
- Ubuntu 22.04 LTS
- Ubuntu 24.04 LTS
- Debian 11
- Debian 12

### Experimental support
- AlmaLinux 8 / 9
- Rocky Linux 8 / 9
- CentOS Stream 9

### Not supported
- Windows (except for local development)
- macOS (except for development)
- Systems without systemd

---

## 🚀 Installation (English)

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable en
```

Beta channel:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- beta en
```

Non-interactive install:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable en --yes --domain panel.example.com --email admin@example.com
```

Manual installation via `git`:

```bash
git clone https://github.com/xpanel-sh/xpanel.git /opt/xpanel
bash /opt/xpanel/install.sh
```

Clean install from local repository:

```bash
bash /opt/xpanel/install.sh en --fresh --domain panel.example.com --email admin@example.com
```

Installer will display:
- Panel URL
- Username
- Password
- First login path: `/admin/login`

XPanel uses real production Let's Encrypt certificates through HTTP Challenge by default. Production installation requires a valid domain pointing to the server and public ports `80`/`443`; `--cf-token`/`CF_DNS_API_TOKEN` is optional for Cloudflare DNS Challenge and future wildcard workflows. Let's Encrypt staging is not enabled by default.

## 🧪 Local Development Setup (Not Production)

Use this only on your local/dev machine:

```bash
bash ./install_dev.sh
```

This script is for development bootstrap and does not replace production installation.

## 🧩 Main Commands
```bash
# Health and diagnostics
xpanel status
xpanel status --json
xpanel doctor

# Access and runtime config
xpanel access
xpanel access reset-password
xpanel config list
xpanel config get domain
xpanel config set domain panel.yourdomain.com
xpanel config set port 8888
xpanel config set lang en
xpanel config set admin-login-path admin/login
xpanel config set client-login-path login

# Updates
xpanel update
xpanel update check
xpanel update --dry-run
xpanel update --rollback
xpanel reinstall

# Backups
xpanel backup create
xpanel backup list
xpanel backup restore xpanel-backup-YYYYMMDD-HHMMSS.tar.gz
xpanel backup prune

# Logs
xpanel logs
xpanel logs panel -f --since 1h --lines 200
xpanel logs db -f

# Sites (CLI)
xpanel site list
xpanel site create example.com php nginx 8.2
xpanel site restart example.com
xpanel site delete example.com

# SSL
xpanel ssl check
xpanel ssl status example.com
xpanel ssl setup your-email@domain.com your_cloudflare_token
xpanel ssl renew

# Language and i18n audit
xpanel language list
xpanel language en
xpanel i18n-audit
```

## DNS, mail and operational artifacts

The daemon generates local artifacts so real services can be connected without mixing system operations into Laravel:

- BIND-style DNS zones: `runtime/daemon/dns/zones`
- Virtual mail maps: `runtime/daemon/mail`
- Auditable history: `runtime/daemon/operations.json`

The authoritative DNS service with CoreDNS is prepared as an optional profile. It is not enabled by default to avoid conflicts on servers already using port 53.

```bash
cd /opt/xpanel
docker compose --profile dns up -d dns
xpanel doctor
```

To use XPanel as DNS for a domain, point the domain NS records to the nameservers configured in Admin and make sure TCP/UDP port 53 is open.

## Production Security

- The installer generates real MariaDB, Laravel and daemon secrets; seeders do not create demo users unless explicitly enabled with your own passwords.
- Database and mail passwords are provided by the user, and XPanel does not show them in flash messages or recover them later.
- Traefik reaches Docker through `docker-socket-proxy`, not through a direct Docker socket mount inside the Traefik container.
- Site images are pre-pulled during install/operations; the daemon no longer downloads images inside the site creation HTTP request.

## ⌨️ Bash Autocomplete

If installed via `install.sh`, autocomplete is placed at:

```bash
/etc/bash_completion.d/xpanel
```

Enable it in your current session:

```bash
source /etc/bash_completion.d/xpanel
```

## 🌐 Official Domains

Website: https://xpanel.sh
Installer: https://get.xpanel.sh
Docs: https://docs.xpanel.sh

## 🧯 Quick Recovery

To verify or repair admin access:

```bash
xpanel doctor
xpanel access
xpanel access reset-password
```

If an installation is interrupted, the installer detects stale locks and removes them when no installer process is active.

## 📚 Documentation

Changelog: CHANGELOG.md

License: LICENSE

## 🤝 Contributing

Contributions are welcome!
Issues, PRs and suggestions help improve XPanel.

Thank you for using XPanel 🚀
