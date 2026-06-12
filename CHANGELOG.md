# Changelog

All notable changes to this project will be documented in this file.

---

Future releases will include:
- Resource quotas
- Domain manager
- Backup automation
- Plugin system

---

## [2.5.0] - Admin/Client Layout Refresh and Advanced File Manager
**Release date:** 2026-06-12

### Added
- Added shared admin and client layout partials for sidebar, navbar, footer and search modal.
- Added contextual admin/client navbars with route-aware actions and dropdown support.
- Added system settings model, migration, controller and admin settings view.
- Added admin dashboard runtime endpoint and refreshed dashboard panels for server monitoring, resources and operational metrics.
- Added client dashboard content for tenant/account, plan, resources and site overview.
- Added a shared advanced file manager view for admin and client panels using the Metronic/iKode editor layout.
- Added IDE-style file manager controls: explorer/settings modes, outline/timeline panel, right-side info/summary panel, terminal-style bottom panel, context menu actions and Monaco editor.
- Added persistent file manager UI state in `localStorage` for panel visibility, active tabs and split sizes.
- Added split panes for explorer/outline, editor/terminal and main left/center/right layout.
- Added duplicated editor tab support so the same open file can be viewed in two side-by-side Monaco panes.
- Added drag-and-drop empty-state upload area in the explorer when a directory has no files.
- Added optional domain-based file manager routes for admin and client access.

### Changed
- Changed admin and client layouts to noindex/noarchive mode and removed SEO/social metadata from private panels.
- Changed admin/client file manager routes to support `/admin/files`, `/admin/files/{domain}`, `/client/files` and `/client/files/{domain}`.
- Changed file manager access to use domains instead of numeric site IDs in admin/client URLs.
- Changed admin file manager root behavior so `/admin/files` can browse the global `www/` root and a domain route browses that site's root.
- Changed client file manager root behavior so `/client/files` shows only the authenticated tenant's sites and domain routes are tenant-authorized.
- Changed all admin/client views, except the legacy admin files example, to use the new content container/footer structure.
- Changed file manager frontend assets to load from `public/assets/files`.
- Changed daemon file handling so an empty domain resolves to the global sites root.
- Changed tenant-scoped migrations to include soft delete timestamps for managed resources.

### Fixed
- Fixed undefined `$user` in the admin layout by resolving the authenticated admin guard user in the layout.
- Fixed admin/client navbar visibility so file manager pages can use the full workspace area.
- Fixed file manager split panes that were not resizing inside flex layouts by using `flex-basis` Split.js styles.
- Fixed Monaco showing behind the empty state before a file is opened.
- Fixed duplicated file manager action buttons and moved upload/drop behavior into the explorer flow.

---

## [2.4.0] - File Manager Fix and Multi-Tenant Domain Access (Port 2083)
**Release date:** 2026-06-10

### Added
- Added Traefik `clientpanel` entrypoint on port 2083 so clients can access their panel at `theirdomain.com:2083/client/login` — similar to how cPanel exposes `:2083`.
- Added `TenantFromHost` middleware: on every web request, if the hostname matches a registered site domain (and is not the main XPanel domain), the associated active tenant is stored on the request as `tenant_host`.
- Added fallback in `ResolveTenant`: if the authenticated user has no tenant record, the tenant detected by `TenantFromHost` is used instead.

### Fixed
- Fixed `site root not found for domain` error in the file manager daemon: `SiteRoot()` now calls `os.MkdirAll` instead of `os.Stat`, auto-creating the site directory if it does not yet exist (e.g. sites created directly in the database or imported from another system).

### Changed
- Changed `docker-compose.yml` to expose port `2083` on the Traefik container and add a `clientpanel` router label to the panel service.

---

## [2.3.0] - Separate Admin/Client Layouts and Client Route Prefix
**Release date:** 2026-06-10

### Added
- Added `layouts/admin.blade.php`: dedicated admin layout with admin sidebar and navbar, no conditional logic, admin guard logout.
- Added `layouts/client.blade.php`: dedicated client layout with client sidebar, domain selector dropdown in header, Mi Cuenta in user menu, client guard logout.
- Added `layouts/home.blade.php`: minimal public layout for the home page (nav bar + footer, no auth required).
- Added `HomeController` and `home/index.blade.php` / `home/disabled.blade.php` views. Root `/` shows home or a blank branded page depending on `XPANEL_HOME_ENABLED` env flag (default `false`).
- Added `home_enabled` key to `config/xpanel.php`.

### Changed
- Changed all client routes from root prefix to `/client/` prefix: login is now `/client/login`, dashboard `/client/dashboard`, etc. Route names are unchanged (`client.login`, `client.dashboard`, …).
- Changed default `XPANEL_CLIENT_LOGIN_PATH` from `login` to `client/login`.
- Changed all 15 admin views to extend `layouts.admin` and all 12 client views to extend `layouts.client`; `layouts/app.blade.php` kept as legacy fallback.
- Changed root `/` from client login redirect to `HomeController@index`.

### Fixed
- Fixed hardcoded BASE URL in client file manager (`/files/` → `/client/files/`) after route prefix change.

---

## [2.2.1] - Update Script Self-Re-Exec Fix
**Release date:** 2026-06-10

### Fixed
- Fixed update script running stale bash-buffered code after `git pull` by adding a self-re-exec mechanism: after pulling new code the script re-executes itself (`exec bash update.sh`) with `XPANEL_PULLED=1` so the build and deploy phase always runs from the freshly-downloaded version.
- Fixed `go mod download` warning in Go 1.21+ by changing `go mod download` to `go mod download all` inside the daemon build subshell.

---

## [2.2.0] - Metronic UI, CLI Permissions Fix and Update Hardening
**Release date:** 2026-06-10

### Added
- Added Metronic v9.4.14 (Tailwind CSS) as the new panel UI framework, replacing the previous custom Tailwind layout.
- Added icon-only sidebar (58 px) with hover tooltips using KeenIcons, separate icon sets for admin and client panels.
- Added horizontal tab navbar below the header that reflects all main sections for admin and client; automatically hidden on file manager pages.
- Added dark/light mode toggle using Metronic's `kt-theme` system (`localStorage` key `kt-theme`), replacing the previous `xpanel-theme` key.
- Added domain selector dropdown in the header for client panel: lists all tenant sites and navigates directly to the file manager for the selected domain.
- Added `@yield('navbar_actions')` slot so views can inject action buttons into the navbar right side.
- Added automatic `chmod +x` restoration for all `installer/*.sh` scripts after `git pull` in `update.sh`.
- Added automatic re-creation of the `/usr/local/bin/xpanel` symlink if it is missing after an update.

### Changed
- Changed `layouts/app.blade.php` to use Metronic card-based layout: rounded content card, sidebar, header and navbar from Metronic's `demo3` demo.
- Changed file manager content wrapper to `full-height, no-container` mode so Monaco editor fills the available viewport without interference from the navbar card layout.
- Changed file manager height calculation from `calc(100vh - 10rem)` to `calc(100dvh - var(--header-height) - 2.5rem)` to match the new fixed header.
- Changed admin and client panel to load `assets/css/styles.css` and `assets/vendors/keenicons/styles.bundle.css` from `public/assets/` (Metronic bundle) instead of CDN Tailwind.
- Changed user info display in sidebar footer to user dropdown in header topbar (name, email, logout, Mi Cuenta for clients).

### Fixed
- Fixed `xpanel` CLI becoming non-executable after `git pull` updates `installer/cli.sh` without preserving the executable bit.
- Fixed update aborting with `set -euo pipefail` when Go daemon build fails; build failure now logs a warning and the update continues to docker compose and systemd restart steps.
- Fixed daemon build path detection: git installs now correctly compile from `$BASE/daemon/` (where `go.mod` exists) instead of the stale `$BASE/daemon-src/` directory.

---

## [2.1.0] - File Manager, UI Overhaul and Dual-Session Auth
**Release date:** 2026-06-09

### Added
- Added full file manager module accessible from both client and admin panels, similar to cPanel/Hostinger file managers.
- Added Go daemon `files` package (`daemon/internal/files/manager.go`) with path traversal prevention via `SafeJoin()`, 2 MB read limit and 50 MB upload limit.
- Added 8 daemon API endpoints under `/api/files/*`: list, read, write, mkdir, delete, rename, upload (multipart) and download.
- Added `DaemonClient` PHP methods: `fileList`, `fileRead`, `fileWrite`, `fileMkdir`, `fileDelete`, `fileRename`, `fileUpload` and `fileDownloadProxy`.
- Added `Client\FileManagerController` with per-operation tenant ownership check (`$site->tenant_id !== $tenant->id` → 403).
- Added `Admin\FileManagerController` with unrestricted admin access to any site's files.
- Added 18 file manager routes (9 client under `/files/{site}/api/*`, 9 admin under `/admin/files/{site}/api/*`).
- Added Monaco Editor (CDN `monaco-editor@0.52.2`) as the in-browser code editor with syntax highlighting by file extension, dirty indicator and Ctrl+S save.
- Added image preview mode in the file manager for PNG, JPG, GIF, SVG, WebP and ICO files.
- Added drag-and-drop upload support and upload progress bar.
- Added right-click context menu in the file manager (open, rename, download, delete).
- Added directory tree sidebar with lazy path expansion.
- Added dark/light mode toggle with `localStorage` persistence and anti-flash inline script in `<head>`.
- Added Monaco Editor theme sync with dark/light mode via `MutationObserver`.
- Added Alpine.js 3.14.3 globally via CDN across the authenticated layout.
- Added `confirm-modal` Blade component replacing all native `confirm()` dialogs.
- Added auto-dismiss alerts: success messages dismiss after 5 s, error messages after 7 s.
- Added client-side search (Alpine.js `x-model`) in admin clients and admin sites tables.
- Added server-side search for client databases (`DatabaseController`) and client email accounts (`EmailAccountController`).
- Added dual authentication guard system: `admin` guard (session key `login_admin_*`) independent from `web` guard (session key `login_web_*`), allowing simultaneous admin and client sessions in the same browser without incognito mode.

### Changed
- Changed admin routes to use `auth:admin` middleware (previously shared `auth` + `can:admin` gate), fully separating admin session from client session.
- Changed client routes to use `auth:web` middleware, preventing admin credentials from being accepted through the client login form.
- Changed `Client\AuthController::showLogin()` to only redirect already-authenticated client users; admin guard state no longer triggers a redirect on the client login page.
- Changed `ResolveTenant` middleware to explicitly use `Auth::guard('web')->user()` so admin guard sessions are never mistaken for client sessions.
- Changed authenticated layout `app.blade.php` to resolve `$user` and `$isAdmin` from the admin guard first, falling back to the web guard.
- Changed `AuthServiceProvider` admin gate definition to check `Auth::guard('admin')` directly.
- Changed `Admin\AuthController` to use `Auth::guard('admin')` for all login, check and logout operations.
- Changed `Client\AuthController` to use `Auth::guard('web')` explicitly and reject admin-role users with a clear error message.
- Unified visual style across admin and client views: replaced legacy `bg-gray-800`/`border-gray-700` classes with `bg-white/[0.03]`/`border-white/10` modern palette.
- Changed client web, databases, domains, emails and DNS views to use the `confirm-modal` component instead of inline `onsubmit` confirm.
- Changed admin clients and sites views to use Alpine.js client-side search.
- Added "Archivos" button to client sites list and admin sites list linking to the respective file manager.

### Fixed
- Fixed inability to open client login in the same browser while admin is logged in without using incognito mode.
- Fixed admin users being incorrectly redirected to `admin.dashboard` when visiting the client login URL.

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
