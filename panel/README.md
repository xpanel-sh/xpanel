# XPanel Web Panel

This directory contains the Laravel application used by XPanel.

## Responsibility

The web panel is responsible for:

- Admin and client authentication.
- Tenant-aware UI and authorization.
- Managing platform metadata: clients, sites, databases and connected servers.
- Calling shared application services.
- Calling the Go daemon through `App\Services\DaemonClient` for system-level work.

The Laravel app must not execute operating-system commands directly. System operations belong to the Go daemon.

## Main Areas

- `app/Http/Controllers/Admin`: global platform/admin flows.
- `app/Http/Controllers/Client`: tenant/client flows.
- `app/Services`: shared backend actions used by both panels.
- `resources/views/admin`: admin UI.
- `resources/views/client`: client UI.
- `resources/views/layouts/app.blade.php`: authenticated shell with role-aware navigation.
- `routes/web.php`: browser routes for admin and client panels.

## Configurable Login Paths

The installer writes these values to `panel/.env`:

```env
XPANEL_ADMIN_LOGIN_PATH=admin/login
XPANEL_CLIENT_LOGIN_PATH=login
XPANEL_ADMIN_BASE_PATH=admin
```

Runtime CLI examples:

```bash
xpanel config set admin-login-path admin/login
xpanel config set client-login-path login
xpanel config list
```

## Local Notes

Install PHP dependencies only for development:

```bash
composer install
php artisan key:generate
php artisan migrate
```

Production installation is handled by the root installer:

```bash
bash /opt/xpanel/install.sh
```
