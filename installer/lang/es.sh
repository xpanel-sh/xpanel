msg_start()            { echo "🚀 Iniciando instalación de XPanel"; }
msg_checks()           { echo "🔍 Ejecutando verificaciones del sistema..."; }
msg_create_dirs()      { echo "📁 Creando estructura del sistema..."; }
msg_load_env()         { echo "⚙️ Cargando configuración inicial..."; }
msg_install_docker()   { echo "🐳 Instalando Docker (si es necesario)..."; }
msg_install_go()       { echo "🐹 Instalando entorno de Go..."; }
msg_build_daemon()     { echo "⚙️ Compilando daemon..."; }
msg_init_laravel()     { echo "🛠️ Inicializando panel web..."; }
msg_create_access()    { echo "🔐 Generando credenciales de acceso..."; }
msg_copy_files()       { echo "📦 Copiando archivos del sistema..."; }
msg_create_cli()       { echo "🧩 Registrando comando global xpanel..."; }
msg_create_service()   { echo "🛡️ Registrando servicio systemd del daemon..."; }
msg_start_services()   { echo "🚀 Iniciando servicios de XPanel..."; }
msg_update_start()     { echo "⬆️ Iniciando actualización de XPanel..."; }
msg_migrate()          { echo "🧬 Ejecutando migraciones del sistema..."; }
msg_uninstall_start()  { echo "🗑️ Desinstalando XPanel..."; }
msg_fresh_start()      { echo "🧼 Preparando instalación limpia de XPanel..."; }
msg_checks_ok()      { echo "✅ Verificaciones completadas"; }
msg_dirs_ok()        { echo "✅ Estructura creada"; }
msg_env_ok()         { echo "✅ Configuración guardada"; }
msg_access_ok()      { echo "✅ Credenciales generadas"; }
msg_docker_ok()      { echo "✅ Docker listo"; }
msg_files_ok()       { echo "✅ Archivos copiados"; }
msg_cli_ok()         { echo "✅ Comando xpanel disponible"; }
msg_go_ok()          { echo "✅ Go instalado"; }
msg_daemon_ok()      { echo "✅ Daemon compilado"; }
msg_service_ok()     { echo "✅ Servicio daemon activo"; }
msg_services_ok()    { echo "✅ Servicios levantados"; }
msg_fresh_done()     { echo "✅ Instalación anterior limpiada"; }

msg_error_daemon()   { echo "❌ Código del daemon no encontrado"; }
msg_docs()           { echo "📘 Documentación: https://docs.xpanel.sh"; }
msg_thanks()         { echo "🙏 Gracias por usar XPanel"; }

msg_done() {
  echo "✅ XPanel se ha instalado correctamente"
}

msg_error_root()       { echo "❌ Debe ejecutar este script como root"; }
msg_error_os()         { echo "❌ Sistema operativo no soportado"; }
msg_error_env()        { echo "❌ Archivo de configuración no encontrado"; }
msg_error_lang()       { echo "❌ Idioma no soportado"; }
msg_error_compose()    { echo "❌ Docker Compose no está disponible"; } 
msg_missing_dependency() { echo "❌ Dependencia faltante: $1"; }

msg_access() {
  echo "🔐 Datos de acceso al panel:"
}
msg_ask_domain() { echo "🌐 Ingresa dominio/subdominio del panel (opcional, Enter para usar la IP del servidor):"; }
msg_invalid_domain() { echo "❌ Dominio invalido: $1"; }

msg_port() {
  echo "🔧 Puerto actualizado a: $1"
}

msg_thanks() {
  echo
  echo "🙏 Gracias por usar XPanel"
  echo "📘 Documentación: https://docs.xpanel.sh"
  echo "💬 Comunidad y soporte: https://xpanel.sh"
}

#CLI
msg_help() {
  echo "🧩 Comandos disponibles:"
  echo "  xpanel actualizar #Actualiza XPanel a la última versión# "
  echo "  xpanel update check|verificar #Verifica si hay una nueva version (sin cambios)# "
  echo "  xpanel update --dry-run #Simula actualizacion sin aplicar cambios# "
  echo "  xpanel update --rollback [archivo] #Revierte a snapshot de actualizacion# "
  echo "  xpanel doctor #Ejecuta diagnostico del sistema# "
  echo "  xpanel eliminar [safe|panel-only|full] [--dry-run] #Desinstala XPanel por modo# "
  echo "  xpanel reinstalar #Reinstala XPanel desde cero# "
  echo "  bash install.sh es --fresh #Instalación limpia desde el repositorio local# "
  echo "  xpanel version #Muestra la versión actual de XPanel# "
  echo "  xpanel acceso #Muestra las credenciales de acceso# "
  echo "  xpanel acceso reset-password [nueva_clave] #Resetea clave admin y sincroniza access.info# "
  echo "  xpanel idioma #Retorna idioma actual# " 
  echo "  xpanel idioma <codigo> #Cambia el idioma# "
  echo "  xpanel config <get|set|list> #Gestiona configuracion en tiempo real# "
  echo "  xpanel config set admin-login-path admin/login #Cambia ruta de login admin# "
  echo "  xpanel config set client-login-path login #Cambia ruta de login cliente# "
  echo "  xpanel puerto <numero> #Cambia el puerto# "
  echo "  xpanel site list #Lista contenedores de sitios# "
  echo "  xpanel site create <dominio> <php|node|static|python> [apache|nginx] [php_version] #Crea sitio desde CLI# "
  echo "  xpanel site delete <dominio> #Elimina contenedor de sitio# "
  echo "  xpanel ssl status [dominio] #Muestra estado SSL# "
  echo "  xpanel ssl check [dominio] #Valida DNS y proxy para SSL# "
  echo "  xpanel ssl setup <email> <token> #Configura token DNS de Cloudflare para SSL# "
  echo "  xpanel ssl renew #Recarga proxy y renueva certificados# "
  echo "  xpanel i18n-audit #Verifica llaves faltantes de idioma# "
}

msg_unknown() { echo "❌ Comando desconocido: $1";}
msg_current_language() { echo "🌐 Idioma actual: $1"; }

msg_language_changed() { echo "✅ Idioma cambiado a: $1"; }

msg_language_not_supported() {
  echo "❌ Idioma no soportado: $1"
  echo "Idiomas disponibles: es, en"
}

msg_status_start() { echo "📊 Estado del sistema XPanel"; }
msg_logs_start() { echo "📜 Mostrando logs del sistema..."; }
msg_backup_start() { echo "💾 Creando backup de XPanel..."; }
msg_backup_done()  { echo "✅ Backup creado en: $1"; }

msg_source_incomplete() { echo "❌ Arbol de origen incompleto en: $1"; }
msg_source_expected() { echo "Esperado: panel/.env.example, panel/artisan, docker-compose.yml"; }
msg_source_tip_clone() { echo "Sugerencia: clona el repositorio completo e intenta de nuevo."; }

msg_update_local_version() { echo "Version local:  $1"; }
msg_update_remote_version() { echo "Version remota: $1"; }
msg_update_uptodate() { echo "✅ Ya tienes la ultima version."; }
msg_update_remote_fetch_error() { echo "❌ No se pudo obtener VERSION remota de $1/$2"; }
msg_update_payload_error() { echo "❌ No se pudo extraer el paquete de actualizacion"; }

msg_site_usage() { echo "Uso: xpanel site <list|create|delete> ..."; }
msg_site_usage_create() { echo "Uso: xpanel site create <dominio> <php|node|static|python> [apache|nginx] [php_version]"; }
msg_site_usage_delete() { echo "Uso: xpanel site delete <dominio>"; }
msg_site_provision_sent() { echo "Solicitud de aprovisionamiento enviada para: $1"; }
msg_site_removed() { echo "Contenedor de sitio eliminado: $1"; }
msg_site_not_found() { echo "Contenedor de sitio no encontrado: $1"; }

msg_ssl_usage() { echo "Uso: xpanel ssl <status|setup|renew>"; }
msg_ssl_usage_setup() { echo "Uso: xpanel ssl setup <cloudflare_email> <cloudflare_token>"; }
msg_ssl_acme_present() { echo "acme.json presente"; }
msg_ssl_acme_missing() { echo "acme.json no existe o esta vacio"; }
msg_ssl_cert_found() { echo "Certificado encontrado para: $1"; }
msg_ssl_cert_not_found() { echo "Aun no hay certificado para: $1"; }
msg_ssl_env_missing() { echo "Falta archivo: $1"; }
msg_ssl_saved() { echo "Variables de Cloudflare SSL guardadas en $1"; }
msg_ssl_restarted() { echo "Traefik reiniciado. Los certificados se renovaran en el siguiente ciclo/solicitud ACME."; }
msg_doctor_summary() { echo "Resumen: FAIL=$1 WARN=$2"; }
msg_update_available() { echo "Actualizacion disponible: $1 -> $2"; }
msg_snapshot_created() { echo "Snapshot creado: $1"; }
msg_rollback_complete() { echo "Rollback completado: $1"; }
msg_rollback_not_found() { echo "No se encontro snapshot para rollback"; }
msg_backup_usage() { echo "Uso: xpanel backup <create|list|restore|prune>"; }
msg_backup_restore_usage() { echo "Uso: xpanel backup restore <archivo>"; }
msg_backup_not_found() { echo "Backup no encontrado: $1"; }
msg_backup_restore_done() { echo "Restauracion completada: $1"; }
msg_config_usage() { echo "Uso: xpanel config <get|set|list> [clave] [valor]"; }
msg_config_unknown_key() { echo "Clave desconocida: $1"; }
msg_ok_done() { echo "OK"; }
msg_confirm_prompt() { echo -n "Confirmar (yes/no): "; }
msg_cancelled() { echo "Cancelado"; }
msg_site_restarted() { echo "Reiniciado: $1"; }
msg_ssl_dns_ok() { echo "DNS OK: $1"; }
msg_ssl_dns_fail() { echo "DNS FAIL: $1"; }
msg_ssl_domain_not_set() { echo "Dominio no configurado"; }
msg_ssl_proxy_running() { echo "Contenedor Traefik ejecutandose"; }
msg_ssl_proxy_not_running() { echo "Contenedor Traefik no esta ejecutandose"; }
msg_uninstall_mode() { echo "Modo de desinstalacion: $1"; }
msg_uninstall_dryrun() { echo "Simulacion: no se aplicaran cambios"; }
msg_uninstall_confirm_full() { echo -n "Esto eliminara panel, datos y sitios. Escribe YES para continuar: "; }
