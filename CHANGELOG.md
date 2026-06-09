# Changelog

All notable changes to this project will be documented in this file.

---

Future releases will include:
- Resource quotas
- Domain manager
- Backup automation
- Plugin system

---

## [2.0.0] - Production Security Hardening
**Release date:** 2026-06-08

### Added
- Added a restricted Docker socket proxy service so Traefik no longer mounts the Docker socket directly.
- Added production ACME email wiring through `XPANEL_ACME_EMAIL`.
- Added installer pre-pull of supported site runtime images so site creation requests do not block on Docker image downloads.
- Added `xpanel doctor` checks for production ACME settings and the Docker socket proxy.
- Added update-time repair for `XPANEL_ACME_EMAIL` on existing installations.

### Changed
- Changed Traefik from Let's Encrypt staging to real production Let's Encrypt issuance by default.
- Changed Traefik routing to use HTTPS entrypoints, TLS resolver labels and secure headers for the panel.
- Changed production installation to require a real domain and Cloudflare DNS API token for certificate issuance.
- Changed Docker Compose secrets and domain variables to fail fast when required production values are missing.
- Changed default Laravel environment example to production-safe values, including `APP_DEBUG=false`, warning logs and secure cookies.
- Changed database and mail credential flows so users provide passwords and XPanel does not show generated passwords in flash/session messages.
- Changed panel daemon errors so internal details are logged server-side instead of being exposed to users.
- Changed site containers to use the canonical `xpanel-site-*` naming convention consistently across panel, CLI and daemon.
- Changed CLI site actions to call the authenticated daemon API instead of directly restarting/removing Docker containers.

### Fixed
- Fixed insecure default server node seeding by removing the hardcoded demo daemon token path and using `XPANEL_DAEMON_TOKEN`.
- Fixed demo user seeding so enabling demo users requires explicit admin/client demo passwords.
- Fixed daemon Docker manager initialization so Docker client creation errors are handled instead of ignored.
- Fixed Docker site restart/delete operations so only XPanel-managed site containers with the correct labels can be controlled.
- Fixed client site creation form to submit the selected web server and removed unsupported PHP 7.4 from production choices.
- Fixed reset-password consistency for mail accounts by updating the panel hash only after the daemon accepts the change.

### Documentation
- Updated README ES/EN/root installation examples to include the required Cloudflare token for production SSL.
- Updated security documentation to describe production certificates, no demo credentials, no password flash messages and restricted Docker socket access.
- Recorded release reasoning in `RUN_CHANGELOG_INTERNAL.md`.

---

## [1.3.0] - Panel Expansion and Daemon Provisioning
**Release date:** 2026-06-01

### Added
- Added stale installer lock detection for interrupted installs.
- Added production clean install mode with `--fresh`.
- Added safer root installer argument parsing for language, domain, non-interactive installs and clean installs.
- Added configurable Admin and Client login paths:
  - `XPANEL_ADMIN_LOGIN_PATH`
  - `XPANEL_CLIENT_LOGIN_PATH`
  - `XPANEL_ADMIN_BASE_PATH`
- Added CLI runtime configuration for login paths:
  - `xpanel config set admin-login-path admin/login`
  - `xpanel config set client-login-path login`
- Added `App\Services\SiteProvisioner` as a shared website provisioning service.
- Added `panel/config/xpanel.php` for XPanel-specific runtime configuration.
- Added Admin hosting plans module with create, edit, list and activate/deactivate actions.
- Added default `Starter` and `Growth` hosting plans in the system seeder.
- Added plan assignment during client creation.
- Added Admin client detail page with status toggle.
- Added Admin client edit flow for account data, status, plan reassignment and owner credentials.
- Added Client account page to show assigned plan, limits and current usage.
- Added Client domain module for registering primary domains, aliases and subdomains.
- Added Admin global domains view.
- Added Client email account module for creating, listing, deleting and resetting mailbox passwords by domain.
- Added Admin nameserver settings for XPanel-managed DNS instructions.
- Added Client DNS records module for A, AAAA, CNAME, MX, TXT, NS, SRV and CAA records.
- Added DNS, email and nameserver data models and migrations.
- Added Laravel-to-daemon provisioning calls for email accounts, DNS records and nameserver settings.
- Added daemon API contract endpoints for mail and DNS provisioning actions.
- Added token authentication for protected panel-to-daemon API calls.
- Added panel-to-daemon calls for database create/delete and site restart/delete.
- Added update-time daemon token repair so older installs get `config/daemon.key` and `XPANEL_DAEMON_TOKEN` automatically.
- Added `openssl` as an installer precheck because credentials and daemon tokens depend on it.
- Added daemon local state tracking under `runtime/daemon` for operation history and provisioned resource records.
- Added protected daemon `/api/operations` endpoint.
- Added Admin "Operaciones Agente" screen for daemon operation history.
- Added `runtime/` to `.gitignore` for daemon-generated local state.
- Added `xpanel doctor` check for the protected daemon operations endpoint.
- Added real MariaDB/MySQL database provisioning from the daemon through `xpanel-db`.
- Added daemon env reader for installed XPanel runtime `.env` values.
- Added `xpanel doctor` check for MariaDB root access used by database provisioning.
- Added daemon-generated BIND-style DNS zone artifacts under `runtime/daemon/dns/zones`.
- Added daemon-generated mail artifacts under `runtime/daemon/mail` for virtual domains, mailboxes and quotas.
- Added protected daemon `/api/runtime/status` endpoint for resource and artifact health.
- Added optional CoreDNS profile and `dns/coredns/Corefile`.
- Added Admin runtime cards in "Operaciones Agente".
- Added stronger `xpanel doctor` diagnostics for:
  - panel `.env`
  - `APP_URL`
  - `APP_KEY`
  - Laravel writable directories
  - unhealthy containers
  - route presence
  - Admin login endpoint reachability

### Changed
- Changed `xpanel reinstall` to use the installer `--fresh` flow instead of deleting the installer before re-running.
- Changed Admin global sites route from `/sites` to `/admin/sites` to avoid collision with Client `/sites`.
- Changed login behavior so authenticated users are redirected to the correct Admin or Client dashboard.
- Changed generated admin password handling to synchronize installer output, `config/access.info` and Laravel DB more safely.
- Changed client site creation to use `SiteProvisioner` and roll back the DB record if daemon provisioning fails.
- Changed default seed behavior so demo users are only created when `XPANEL_SEED_DEMO_USERS=true`.
- Changed Client website and database creation to respect assigned plan limits.
- Changed suspended tenants so they cannot continue operating Client panel resources.
- Changed Client dashboard and account pages to include domain usage.
- Changed Client dashboard and account pages to include email usage.
- Changed email and DNS flows to track daemon provisioning success/error states.
- Changed installer to generate and wire `XPANEL_DAEMON_TOKEN` into Laravel and systemd.
- Changed update flow to sync panel-daemon connection settings before rebuilding/restarting services.
- Changed daemon mail/DNS/database handlers from response-only stubs to auditable state updates.
- Changed database daemon handler to create/drop real MariaDB/MySQL databases and users before updating state.
- Changed database provisioning to pass the MariaDB root password through `MYSQL_PWD` inside `docker exec`.
- Changed DNS record create/delete to update local zone files in addition to daemon JSON state.
- Changed mail account create/delete to update local virtual-mail artifacts without storing plaintext passwords.
- Changed installer/update flow to include `dns/` assets and prepare `runtime/`.
- Changed `xpanel doctor` to report optional DNS service/artifact readiness without treating disabled DNS as a failure.
- Changed Client database form to allow only MariaDB/MySQL until PostgreSQL has a backing service.
- Changed Client database names/usernames to letters, numbers and underscores for safe SQL provisioning.
- Changed Client resource lists to show clearer active/provisioning/error states.
- Changed user-facing terminology from technical "node" wording to "connected server" / "servidor conectado" where appropriate.
- Updated README files with clean install mode, panel model, configurable login paths and recovery commands.
- Replaced old documentation/support references with:
  - `https://xpanel.sh`
  - `https://get.xpanel.sh`
  - `https://docs.xpanel.sh`

### Fixed
- Fixed stale `/tmp/xpanel-install.lock` blocking future installations after interrupted sessions.
- Fixed reinstall flow that could remove the installer before executing it.
- Fixed potential daemon binary self-copy errors during install/update.
- Fixed daemon mail delete validation so deleting a mailbox does not require a password payload.
- Fixed client site restart failures showing as raw exceptions instead of clean panel errors.
- Fixed Admin/Client route collision around `/sites`.
- Fixed missing visible login errors by adding error/status rendering to login views.
- Fixed duplicate generic logout route.
- Fixed stale Laravel welcome route reference to `route('login')`.
- Fixed insecure/confusing default demo user seeding in production installs.
- Fixed remaining `doolpool.com` references in docs/security metadata.

### UI
- Rebuilt authenticated layout with role-aware Admin/Client navigation.
- Redesigned Admin login and Client login pages with clearer separation.
- Redesigned Admin dashboard to explain global control, clients, sites, servers and agent model.
- Redesigned Client dashboard around tenant-owned hosting resources.
- Removed confusing WHM/cPanel wording from active UI labels.

### Validation
- PHP lint passed for panel PHP files.
- Bash syntax validation passed for key installer/CLI scripts.
- `docker compose config --quiet` passed.
- `git diff --check` passed.

---

## [1.2.0] - Unified Installer and Project Cleanup
**Release date:** 2026-05-30

### Added
- Added unified root installer flow through `install.sh`.
- Added language selection in the root installer for Spanish and English.
- Added non-interactive installer flags:
  - `--yes`
  - `--domain`
  - `--email`
  - `--password`
  - `--cf-token`
  - `--cf-email`
- Added `install_dev.sh` as the local development bootstrap script.
- Added internal changelog workflow through `RUN_CHANGELOG_INTERNAL.md`.

### Changed
- Changed install documentation to use `xpanel.sh` and `get.xpanel.sh`.
- Changed development setup naming from `init_dev.sh` to `install_dev.sh`.
- Changed installer flow to reduce duplicated install entrypoints.
- Updated README ES/EN with clearer production and local development instructions.

### Removed
- Removed duplicated language-specific installer entrypoints from the main flow.
- Removed stale `doolpool.com` references from primary docs.

### Git Hygiene
- Added `.gitignore` entries for:
  - `xpanel.sh/`
  - `PROMT.txt`
  - `RUN_CHANGELOG_INTERNAL.md`

---

## [1.1.0] – Architecture Improvements 🚀
**Release date:** 2026-02-02

### ✨ Daemon (Go)
- **Refactor Completo:** Nueva estructura modular (`internal/api`, `internal/config`).
- **Graceful Shutdown:** El daemon ahora se apaga limpiamente con `SIGINT`/`SIGTERM`.
- **Config Loader:** Carga de configuración desde variables de entorno.
- **Router:** Implementación de router HTTP extensible.

### 🖥️ Panel (Laravel)
- **Esqueleto Inicial:** Estructura base creada (`app`, `routes`, `composer.json`).
- **Arquitectura Docker:** `docker-compose.yml` con **Traefik** como proxy reverso.
- **Refactorización:** Separación estricta de controladores y vistas (`Admin` vs `Client`).
- **Rutas:** Namespacing y prefixing (`admin.*`, `client.*`) para mejor organización.
- **SSL Wildcard:** Preparado para Let's Encrypt + Cloudflare DNS Challenge configurado en `traefik/traefik.yml`.

### 🛠️ Developer Tools
- **init_dev.sh:** Script de arranque rápido (levanta Docker, instala dependencias, ejecuta migraciones).


### 🧠 CLI (`xpanel`)
- **Idiomas Dinámicos:** Nuevo comando `xpanel idioma list`.
- **Mejoras:** Detección automática de nuevos idiomas en `lang/`.

---

## [1.0.0] – Initial Release 🎉
**Release date:** 2026-01-28

### ✨ Added
- Initial XPanel architecture
- Multi-language installer (ES / EN)
- CLI command `xpanel`
- Docker-based service management
- Laravel panel foundation
- Go daemon structure
- Secure credential generation
- Global installer (`curl | bash`)

### 🧠 CLI Commands
- `xpanel access`
- `xpanel update`
- `xpanel uninstall`
- `xpanel language`
- `xpanel version`

### 🔒 Security
- Isolated services via Docker
- Random password generation
- Root-only installer enforcement

### 📚 Documentation
- README (EN / ES)
- CONTRIBUTING.md
- SECURITY.md
