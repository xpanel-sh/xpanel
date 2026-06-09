<div align="center">
  <img src="https://xpanel.sh/assets/xpanel-logo.png" alt="XPanel" width="220"/>
</div>

<br/>

<div align="center">

[![License](https://img.shields.io/badge/license-MIT-green.svg)]
[![Docker](https://img.shields.io/badge/docker-ready-blue.svg)]
[![CLI](https://img.shields.io/badge/cli-xpanel-orange.svg)]

</div>

<p align="center">
  <a href="https://xpanel.sh">Official Website</a> |
  <a href="https://get.xpanel.sh">Installer</a> |
  <a href="CHANGELOG.md">Changelog</a> |
  <a href="LICENSE">License</a>
</p>

---

## 🚀 XPanel

**XPanel** is an open-source server management panel inspired by aaPanel and cPanel,  
built with **modern architecture**, **Docker-first**, and **CLI-driven automation**.

Designed for:
- Developers
- SaaS builders
- Hosting providers
- Multi-tenant platforms

---

## 🌍 Documentation

Choose your language:

- 🇪🇸 **Español** → [README_ES.md](README_ES.md)
- 🇺🇸 **English** → [README_EN.md](README_EN.md)

Operational CLI guide (updates, backups, diagnostics, SSL, autocomplete) is included in both files.

---

## ⚡ Quick Install

General installer:

```bash
curl -fsSL https://get.xpanel.sh | bash
```

Stable + Spanish:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable es
```

Stable + English:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable en
```

Beta channel:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- beta en
```

Non-interactive example:

```bash
curl -fsSL https://get.xpanel.sh | bash -s -- stable en --yes --domain panel.example.com --email admin@example.com
```

Clean reinstall from a cloned repository:

```bash
bash /opt/xpanel/install.sh en --fresh --domain panel.example.com --email admin@example.com
```

Production installs require a real domain pointing to the server and public ports 80/443 so Traefik can issue real Let's Encrypt certificates through HTTP challenge. Cloudflare DNS API tokens are optional and can be configured later for DNS challenge/wildcard workflows.

Manual clone path (full control):

```bash
git clone https://github.com/xpanel-sh/xpanel.git /opt/xpanel
bash /opt/xpanel/install.sh
```

---

## 🧠 Project Vision

XPanel aims to be:

- 🧩 Modular
- 🐳 Docker-native
- 🏗️ Multi-tenant ready
- 🌍 Multi-language
- 🧠 CLI-first (automation friendly)
- 🔓 100% Open Source
- 🔐 Separate Admin and Client panels
- 🧼 Installer with stale-lock detection and clean install mode

---

Maintained by **XPanel**
Made for the open hosting community
