msg_start()            { echo "🚀 Starting XPanel installation"; }
msg_checks()           { echo "🔍 Running system checks..."; }
msg_create_dirs()      { echo "📁 Creating system structure..."; }
msg_load_env()         { echo "⚙️ Loading initial configuration..."; }
msg_install_docker()   { echo "🐳 Installing Docker (if required)..."; }
msg_install_go()       { echo "🐹 Installing Go environment..."; }
msg_build_daemon()     { echo "⚙️ Compiling daemon..."; }
msg_init_laravel()     { echo "🛠️ Initializing web panel..."; }
msg_create_access()    { echo "🔐 Generating access credentials..."; }
msg_copy_files()       { echo "📦 Copying system files..."; }
msg_create_cli()       { echo "🧩 Registering global xpanel command..."; }
msg_create_service()   { echo "🛡️ Registering daemon systemd service..."; }
msg_start_services()   { echo "🚀 Starting XPanel services..."; }
msg_update_start()     { echo "⬆️ Starting XPanel update..."; }
msg_migrate()          { echo "🧬 Running system migrations..."; }
msg_uninstall_start()  { echo "🗑️ Uninstalling XPanel..."; }
msg_fresh_start()      { echo "🧼 Preparing clean XPanel installation..."; }
msg_checks_ok()      { echo "✅ System checks completed"; }
msg_dirs_ok()        { echo "✅ System structure created"; }
msg_env_ok()         { echo "✅ Configuration saved"; }
msg_access_ok()      { echo "✅ Credentials generated"; }
msg_docker_ok()      { echo "✅ Docker ready"; }
msg_files_ok()       { echo "✅ Files copied"; }
msg_cli_ok()         { echo "✅ xpanel command available"; }
msg_go_ok()          { echo "✅ Go installed"; }
msg_daemon_ok()      { echo "✅ Daemon compiled"; }
msg_service_ok()     { echo "✅ Daemon service active"; }
msg_services_ok()    { echo "✅ Services started"; }
msg_fresh_done()     { echo "✅ Previous installation cleaned"; }

msg_error_daemon()   { echo "❌ Daemon code not found"; }
msg_docs()           { echo "📘 Documentation: https://docs.xpanel.sh"; }
msg_thanks()         { echo "🙏 Thank you for using XPanel"; }
msg_done() { echo "✅ XPanel has been installed successfully"; }

msg_error_root()       { echo "❌ This script must be run as root"; }
msg_error_os()         { echo "❌ Unsupported operating system"; }
msg_error_env()        { echo "❌ Configuration file not found"; }
msg_error_lang()       { echo "❌ Unsupported language"; }
msg_error_compose()    { echo "❌ Docker Compose is not available"; }
msg_missing_dependency() { echo "❌ Missing dependency: $1"; }

msg_access() { echo "🔐 Panel access credentials:"; }
msg_ask_domain() { echo "🌐 Enter panel domain/subdomain (optional, press Enter to use server IP):"; }
msg_invalid_domain() { echo "❌ Invalid domain: $1"; }

msg_port() { echo "🔧 Port updated to: $1"; }

msg_thanks() {
  echo
  echo "🙏 Thank you for using XPanel"
  echo "📘 Documentation: https://docs.xpanel.sh"
  echo "💬 Community & support: https://xpanel.sh"
}



#CLI
msg_help() {
  echo "🧩 Available commands:"
  echo "  xpanel update #Update XPanel to the latest version# "
  echo "  xpanel update check|verificar #Check for a newer version (no changes)# "
  echo "  xpanel update --dry-run #Simulate update without changes# "
  echo "  xpanel update --rollback [file] #Rollback to update snapshot# "
  echo "  xpanel doctor #Run system diagnostics# "
  echo "  xpanel uninstall [safe|panel-only|full] [--dry-run] #Uninstall XPanel by mode# "
  echo "  xpanel reinstall #Reinstall XPanel from scratch# "
  echo "  bash install.sh en --fresh #Clean install from local repository# "
  echo "  xpanel version #Show current XPanel version# "
  echo "  xpanel access #Show panel access credentials# "
  echo "  xpanel access reset-password [new_password] #Reset admin password and sync access.info# "
  echo "  xpanel language #Return current language# " 
  echo "  xpanel language <code> #Change language# "
  echo "  xpanel config <get|set|list> #Manage runtime config# "
  echo "  xpanel config set admin-login-path admin/login #Change admin login path# "
  echo "  xpanel config set client-login-path login #Change client login path# "
  echo "  xpanel port <number> #Change the port# "
  echo "  xpanel site list #List provisioned site containers# "
  echo "  xpanel site create <domain> <php|node|static|python> [apache|nginx] [php_version] #Create site from CLI# "
  echo "  xpanel site delete <domain> #Delete site container# "
  echo "  xpanel ssl status [domain] #Show SSL status# "
  echo "  xpanel ssl check [domain] #Validate DNS and proxy for SSL# "
  echo "  xpanel ssl setup <email> <token> #Configure Cloudflare DNS token for SSL# "
  echo "  xpanel ssl renew #Reload proxy and renew certificates# "
  echo "  xpanel i18n-audit #Check missing language keys# "
}

msg_unknown() { echo "❌ Unknown command: $1"; }
msg_current_language() { echo "🌐 Current language: $1"; }
msg_language_changed() { echo "✅ Language changed to: $1"; }
msg_language_not_supported() {
  echo "❌ Language not supported: $1"
  echo "Available languages: es, en"
}

msg_status_start() { echo "📊 XPanel system status"; }
msg_logs_start() { echo "📜 Showing system logs..."; }
msg_backup_start() { echo "💾 Creating XPanel backup..."; }
msg_backup_done()  { echo "✅ Backup created at: $1"; }

msg_source_incomplete() { echo "❌ Source tree incomplete at: $1"; }
msg_source_expected() { echo "Expected: panel/.env.example, panel/artisan, docker-compose.yml"; }
msg_source_tip_clone() { echo "Tip: clone full repository and run installer again."; }

msg_update_local_version() { echo "Local version:  $1"; }
msg_update_remote_version() { echo "Remote version: $1"; }
msg_update_uptodate() { echo "✅ Already up to date."; }
msg_update_remote_fetch_error() { echo "❌ Could not fetch remote VERSION from $1/$2"; }
msg_update_payload_error() { echo "❌ Could not extract update payload"; }

msg_site_usage() { echo "Usage: xpanel site <list|create|delete> ..."; }
msg_site_usage_create() { echo "Usage: xpanel site create <domain> <php|node|static|python> [apache|nginx] [php_version]"; }
msg_site_usage_delete() { echo "Usage: xpanel site delete <domain>"; }
msg_site_provision_sent() { echo "Site provision request sent: $1"; }
msg_site_removed() { echo "Site container removed: $1"; }
msg_site_not_found() { echo "Site container not found: $1"; }

msg_ssl_usage() { echo "Usage: xpanel ssl <status|setup|renew>"; }
msg_ssl_usage_setup() { echo "Usage: xpanel ssl setup <cloudflare_email> <cloudflare_token>"; }
msg_ssl_acme_present() { echo "acme.json present"; }
msg_ssl_acme_missing() { echo "acme.json missing or empty"; }
msg_ssl_cert_found() { echo "Certificate found for: $1"; }
msg_ssl_cert_not_found() { echo "No certificate found yet for: $1"; }
msg_ssl_env_missing() { echo "Missing file: $1"; }
msg_ssl_saved() { echo "Cloudflare SSL variables saved in $1"; }
msg_ssl_restarted() { echo "Traefik restarted. Certificates will renew on next ACME cycle/request."; }
msg_doctor_summary() { echo "Summary: FAIL=$1 WARN=$2"; }
msg_update_available() { echo "Update available: $1 -> $2"; }
msg_snapshot_created() { echo "Snapshot created: $1"; }
msg_rollback_complete() { echo "Rollback complete: $1"; }
msg_rollback_not_found() { echo "No rollback snapshot found"; }
msg_backup_usage() { echo "Usage: xpanel backup <create|list|restore|prune>"; }
msg_backup_restore_usage() { echo "Usage: xpanel backup restore <file>"; }
msg_backup_not_found() { echo "Backup not found: $1"; }
msg_backup_restore_done() { echo "Restore complete: $1"; }
msg_config_usage() { echo "Usage: xpanel config <get|set|list> [key] [value]"; }
msg_config_unknown_key() { echo "Unknown key: $1"; }
msg_ok_done() { echo "OK"; }
msg_confirm_prompt() { echo -n "Confirm (yes/no): "; }
msg_cancelled() { echo "Cancelled"; }
msg_site_restarted() { echo "Restarted: $1"; }
msg_ssl_dns_ok() { echo "DNS OK: $1"; }
msg_ssl_dns_fail() { echo "DNS FAIL: $1"; }
msg_ssl_domain_not_set() { echo "Domain not set"; }
msg_ssl_proxy_running() { echo "Traefik container running"; }
msg_ssl_proxy_not_running() { echo "Traefik container not running"; }
msg_uninstall_mode() { echo "Uninstall mode: $1"; }
msg_uninstall_dryrun() { echo "Simulation: no changes will be applied"; }
msg_uninstall_confirm_full() { echo -n "This will remove panel, data and websites. Type YES to continue: "; }
