# XPanel

XPanel es un **panel open-source de hosting y gestión de servidores**, inspirado en ideas de aaPanel, WHM y cPanel,
pero diseñado con una **arquitectura moderna**, modular, multi-tenant y orientada a Docker.

---

## 🎯 Objetivos del proyecto

- Simplificar la administración de servidores
- Facilitar despliegues multi-tenant
- Evitar dependencias rígidas del sistema
- Permitir actualizaciones sin romper instalaciones
- Centralizar todo vía CLI (`xpanel`)
- Separar claramente el panel Admin global del panel Cliente
- Usar un agente/daemon para operaciones del sistema, no comandos directos desde Laravel

---

## ✨ Características

- 🐳 Servicios basados en Docker
- 🧩 Arquitectura modular
- 🏢 Preparado para multi-tenant
- 🌍 Instalador multi-idioma (ES / EN)
- ⚙️ Panel en Laravel + daemon en Go
- 🧠 CLI inteligente (`xpanel`)
- 🔄 Actualizaciones seguras
- 💾 Backups integrados
- 🔐 Login Admin y Cliente separados
- 🧼 Instalación limpia con `--fresh`

---

## 🧭 Modelo de paneles

XPanel tiene dos experiencias principales:

- **Admin Global:** gestiona clientes, sitios globales, servidores conectados, configuración y salud de la plataforma.
- **Panel Cliente:** gestiona solo los recursos del cliente: sitios, bases de datos y futuros módulos de correo, archivos, SSL y backups.

La lógica backend puede compartirse mediante servicios internos, pero las rutas, permisos y vistas se mantienen separadas.

---

## 🆚 Comparación con otros paneles

| Característica        | XPanel | aaPanel | cPanel |
|----------------------|--------|--------|--------|
| Open Source          | ✅     | ✅     | ❌     |
| Docker Native        | ✅     | ⚠️     | ❌     |
| CLI avanzada         | ✅     | ❌     | ❌     |
| Multi-tenant         | ✅     | ⚠️     | ❌     |
| Arquitectura moderna | ✅     | ⚠️     | ❌     |

---

## 📦 Requisitos

- SO limpio (sin Apache/Nginx/MySQL previos)
- Docker compatible
- 512MB RAM (recomendado 1GB)
- Acceso root

---

## Sistemas soportados

XPanel está diseñado para ejecutarse en servidores Linux.

### Sistemas oficialmente soportados
- Ubuntu 22.04 LTS
- Ubuntu 24.04 LTS
- Debian 11
- Debian 12

### Soporte experimental
- AlmaLinux 8 / 9
- Rocky Linux 8 / 9
- CentOS Stream 9

### No soportado
- Windows (excepto desarrollo con Docker Desktop)
- macOS (excepto desarrollo)
- Sistemas sin systemd

---

## 🚀 Instalación (Español)

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable es
```

Canal beta:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- beta es
```

Instalación no interactiva:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable es --yes --domain panel.tudominio.com --email admin@tudominio.com
```

Instalación manual vía `git`:

```bash
git clone https://github.com/xpanel-sh/xpanel.git /opt/xpanel
bash /opt/xpanel/install.sh
```

Instalación limpia desde repo local:

```bash
bash /opt/xpanel/install.sh es --fresh --domain panel.tudominio.com --email admin@tudominio.com
```

Al finalizar se mostrarán:
- URL del panel
- Usuario
- Contraseña
- Ruta de primer acceso: `/admin/login`

XPanel usa Let's Encrypt real en producción mediante HTTP Challenge por defecto. La instalación requiere un dominio válido apuntando al servidor y los puertos públicos `80`/`443`; `--cf-token`/`CF_DNS_API_TOKEN` es opcional para Cloudflare DNS Challenge y futuros flujos wildcard. No se usa Let's Encrypt staging por defecto.

## 🧪 Entorno Local de Desarrollo (No Producción)

Usa esto solo en tu máquina local/dev:

```bash
bash ./install_dev.sh
```

Este script es para bootstrap de desarrollo y no reemplaza la instalación de producción.

## 🧩 Comandos principales
```bash
# Estado y diagnostico
xpanel status
xpanel status --json
xpanel doctor

# Acceso y configuracion runtime
xpanel acceso
xpanel acceso reset-password
xpanel config list
xpanel config get domain
xpanel config set domain panel.tudominio.com
xpanel config set port 8888
xpanel config set lang es
xpanel config set admin-login-path admin/login
xpanel config set client-login-path login

# Actualizaciones
xpanel actualizar
xpanel update verificar
xpanel update --dry-run
xpanel update --rollback
xpanel reinstalar

# Backups
xpanel respaldo create
xpanel respaldo list
xpanel respaldo restore xpanel-backup-YYYYMMDD-HHMMSS.tar.gz
xpanel respaldo prune

# Logs
xpanel logs
xpanel logs panel -f --since 1h --lines 200
xpanel logs db -f

# Sitios (CLI)
xpanel site list
xpanel site create ejemplo.com php nginx 8.2
xpanel site restart ejemplo.com
xpanel site delete ejemplo.com

# SSL
xpanel ssl check
xpanel ssl status ejemplo.com
xpanel ssl setup tu-email@dominio.com tu_token_cloudflare
xpanel ssl renew

# Idioma y auditoria
xpanel idioma list
xpanel idioma es
xpanel i18n-audit
```

## DNS, correo y artefactos operativos

El daemon genera artefactos locales para conectar servicios reales sin mezclar lógica del sistema con Laravel:

- Zonas DNS tipo BIND: `runtime/daemon/dns/zones`
- Mapas de correo virtual: `runtime/daemon/mail`
- Historial auditable: `runtime/daemon/operations.json`

El servicio DNS autoritativo con CoreDNS está preparado como perfil opcional. No se activa por defecto para evitar conflictos con servidores que ya usan el puerto 53.

```bash
cd /opt/xpanel
docker compose --profile dns up -d dns
xpanel doctor
```

Para que un dominio use XPanel como DNS, apunta sus NS hacia los nameservers configurados en Admin y asegúrate de que el puerto 53 TCP/UDP esté abierto.

## Seguridad de producción

- El instalador genera secretos reales para MariaDB, Laravel y el daemon; los seeders no crean usuarios demo salvo que se habiliten explícitamente con contraseñas propias.
- Las contraseñas de bases de datos y correos las define el usuario y XPanel no las muestra en mensajes flash ni las recupera después.
- Traefik accede a Docker mediante `docker-socket-proxy`, no con el socket montado directamente en el contenedor de Traefik.
- Las imágenes de sitio se precargan durante instalación/operación; el daemon no descarga imágenes dentro del request HTTP de creación.

## ⌨️ Autocompletado Bash

Si se instaló via `install.sh`, el autocompletado queda en:

```bash
/etc/bash_completion.d/xpanel
```

Para activarlo en la sesion actual:

```bash
source /etc/bash_completion.d/xpanel
```

## 🌐 Dominios oficiales

Web: https://xpanel.sh
Instalador: https://get.xpanel.sh
Docs: https://docs.xpanel.sh

## 🧯 Recuperación rápida

Si necesitas verificar o reparar acceso admin:

```bash
xpanel doctor
xpanel acceso
xpanel acceso reset-password
```

Si una instalación se interrumpió, el instalador detecta locks viejos y los limpia cuando ya no hay proceso activo.

## 📚 Documentación

Changelog: CHANGELOG.md

Licencia: LICENSE

## 🤝 Contribuciones

Las contribuciones son bienvenidas.
Pull Requests, issues y sugerencias ayudan a mejorar XPanel.

Gracias por usar XPanel 🚀
