#!/usr/bin/env bash
set -euo pipefail

echo "🚀 Inicializando entorno de desarrollo local de XPanel..."
echo "⚠️  Este script es solo para desarrollo local, no para producción."

# 1. Crear archivo ACME vacío para Traefik (evita error de permisos Docker si no existe)
if [ ! -f traefik/acme.json ]; then
    touch traefik/acme.json
    chmod 600 traefik/acme.json
    echo "✅ Archivo acme.json creado"
fi

# 2. Iniciar contenedores
echo "🐳 Levantando contenedores..."
docker compose up -d

# 3. Esperar a DB
echo "⏳ Esperando a Base de Datos..."
sleep 10

# 4. Instalar dependencias Laravel
echo "📦 Instalando dependencias Composer..."
docker compose exec panel composer install

# 5. Generar Key
echo "🔑 Generando App Key..."
docker compose exec panel php artisan key:generate

# 6. Migraciones
echo "database.sqlite está configurado por defecto en .env.example, pero usamos MySQL en docker-compose"
# Ajustamos entorno si es necesario o copiamos .env
if [ ! -f panel/.env ]; then
    cp panel/.env.example panel/.env
    # Ajuste rapido para docker
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' panel/.env
    sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=db/g' panel/.env
    sed -i 's/# DB_PORT=3306/DB_PORT=3306/g' panel/.env
    sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=xpanel/g' panel/.env
    sed -i 's/# DB_USERNAME=root/DB_USERNAME=xpanel/g' panel/.env
    sed -i 's/# DB_PASSWORD=/DB_PASSWORD=xpanel_password/g' panel/.env
    echo "✅ Archivo .env configurado"
fi

echo "🧬 Ejecutando migraciones..."
docker compose exec panel php artisan migrate --force

echo "✅ Listo! Accede a http://localhost:8080 (Traefik) o configura tu hosts para panel.localhost"
