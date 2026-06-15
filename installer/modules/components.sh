#!/usr/bin/env bash
# xpanel components — install/update/list optional components
set -euo pipefail

BASE="${XPANEL_BASE:-/opt/xpanel}"

# ── colours ──────────────────────────────────────────────────────────────────
GREEN="\033[0;32m"; YELLOW="\033[0;33m"; RED="\033[0;31m"; BOLD="\033[1m"; NC="\033[0m"
ok()   { echo -e "${GREEN}✔${NC} $*"; }
warn() { echo -e "${YELLOW}⚠${NC} $*"; }
err()  { echo -e "${RED}✖${NC} $*"; }
hdr()  { echo -e "\n${BOLD}$*${NC}"; }

# ── component definitions ────────────────────────────────────────────────────
# Format: name|label_en|label_es|type(docker_profile|shell)
COMPONENTS=(
    "dns|Authoritative DNS (CoreDNS)|DNS Autoritativo (CoreDNS)|docker_profile|dns"
    "mail|Mail Server (Postfix+Dovecot)|Servidor de correo (Postfix+Dovecot)|docker_profile|mail"
    "ssl|SSL auto-certificates (acme.sh)|Certificados SSL automáticos (acme.sh)|shell|acme"
)

# ── status helpers ────────────────────────────────────────────────────────────

component_status_docker() {
    local profile="$1"
    local service=""
    case "$profile" in
        dns)  service="xpanel-dns" ;;
        mail) service="xpanel-mail" ;;
    esac
    if docker inspect "$service" >/dev/null 2>&1; then
        local state
        state=$(docker inspect --format '{{.State.Status}}' "$service" 2>/dev/null || echo "unknown")
        if [ "$state" = "running" ]; then
            echo "running"
        else
            echo "stopped ($state)"
        fi
    else
        echo "not_installed"
    fi
}

component_status_ssl() {
    if [ -f "/root/.acme.sh/acme.sh" ] || command -v acme.sh >/dev/null 2>&1; then
        echo "installed"
    else
        echo "not_installed"
    fi
}

component_status() {
    local name="$1"
    local type="$2"
    local profile="${3:-}"
    case "$type" in
        docker_profile) component_status_docker "$profile" ;;
        shell)          component_status_ssl ;;
        *)              echo "unknown" ;;
    esac
}

# ── list ──────────────────────────────────────────────────────────────────────

cmd_list() {
    hdr "XPanel Components"
    printf "%-10s %-42s %s\n" "NAME" "DESCRIPTION" "STATUS"
    printf "%-10s %-42s %s\n" "----------" "------------------------------------------" "----------"

    for entry in "${COMPONENTS[@]}"; do
        IFS='|' read -r name label_en label_es type profile <<< "$entry"
        status=$(component_status "$name" "$type" "$profile")

        color="$RED"
        [ "$status" = "running" ]   && color="$GREEN"
        [ "$status" = "installed" ] && color="$GREEN"
        [ "$status" = "stopped"* ]  && color="$YELLOW"

        printf "%-10s %-42s ${color}%s${NC}\n" "$name" "$label_en" "$status"
    done
    echo ""
    echo -e "  Install:  ${BOLD}xpanel components install <name>${NC}"
    echo -e "  Update:   ${BOLD}xpanel components update${NC}"
}

# ── install ───────────────────────────────────────────────────────────────────

install_docker_profile() {
    local profile="$1"
    local label="$2"

    if [ ! -f "$BASE/docker-compose.yml" ]; then
        err "docker-compose.yml not found at $BASE"
        exit 1
    fi

    hdr "Installing: $label"
    cd "$BASE"
    docker compose --profile "$profile" up -d
    ok "$label started."
}

install_ssl() {
    hdr "Installing: SSL auto-certificates (acme.sh)"

    if [ -f "/root/.acme.sh/acme.sh" ]; then
        ok "acme.sh already installed at /root/.acme.sh/acme.sh"
        return
    fi

    echo "Downloading acme.sh..."
    curl -fsSL https://get.acme.sh | sh -s email="${ACME_EMAIL:-admin@localhost}"
    source /root/.acme.sh/acme.sh.env 2>/dev/null || true

    # Set ZeroSSL as default CA (free wildcard certs)
    /root/.acme.sh/acme.sh --set-default-ca --server zerossl 2>/dev/null || \
    /root/.acme.sh/acme.sh --set-default-ca --server letsencrypt

    ok "acme.sh installed at /root/.acme.sh/"
    echo ""
    echo "  To issue a cert with Cloudflare DNS-01:"
    echo "    CF_Token=<your_token> acme.sh --issue -d domain.com -d '*.domain.com' --dns dns_cf"
    echo ""
    echo "  Or via XPanel panel: Client → Websites → Advanced → SSL"
}

cmd_install() {
    local name="${1:-}"
    if [ -z "$name" ]; then
        err "Usage: xpanel components install <name>"
        echo "Available: dns, mail, ssl"
        exit 1
    fi

    case "$name" in
        dns)
            install_docker_profile "dns" "Authoritative DNS (CoreDNS)"
            echo ""
            ok "Port 53 UDP/TCP now serves your XPanel zones."
            warn "Open port 53 in your firewall: ufw allow 53"
            ;;
        mail)
            install_docker_profile "mail" "Mail Server (Postfix+Dovecot)"
            echo ""
            ok "Ports 25, 587, 143, 993 are now active."
            warn "Open mail ports in your firewall:"
            warn "  ufw allow 25 && ufw allow 587 && ufw allow 143 && ufw allow 993"
            ;;
        ssl)
            install_ssl
            ;;
        *)
            err "Unknown component: $name"
            echo "Available: dns, mail, ssl"
            exit 1
            ;;
    esac
}

# ── update ────────────────────────────────────────────────────────────────────

cmd_update() {
    hdr "Updating installed components..."

    for entry in "${COMPONENTS[@]}"; do
        IFS='|' read -r name label_en label_es type profile <<< "$entry"
        status=$(component_status "$name" "$type" "$profile")

        if [ "$status" = "not_installed" ]; then
            echo "  Skipping $name (not installed)"
            continue
        fi

        echo "  Updating $name..."
        case "$type" in
            docker_profile)
                cd "$BASE"
                docker compose --profile "$profile" pull
                docker compose --profile "$profile" up -d
                ok "$name updated."
                ;;
            shell)
                if [ -f "/root/.acme.sh/acme.sh" ]; then
                    /root/.acme.sh/acme.sh --upgrade
                    ok "acme.sh upgraded."
                fi
                ;;
        esac
    done
}

# ── main ──────────────────────────────────────────────────────────────────────

SUBCMD="${1:-list}"
shift || true

case "$SUBCMD" in
    list|listar|"")
        cmd_list
        ;;
    install|instalar)
        cmd_install "${1:-}"
        ;;
    update|actualizar)
        cmd_update
        ;;
    *)
        err "Unknown subcommand: $SUBCMD"
        echo "Usage: xpanel components [list|install <name>|update]"
        exit 1
        ;;
esac
