#!/bin/bash
set -e

echo "Iniciando configuración del contenedor..."

# Asegurar que los directorios necesarios existen
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/uploads
mkdir -p /var/www/html/bootstrap/cache

# Establecer permisos correctos cada vez que el contenedor inicia
# Esto es crucial cuando se usa bind mounts en Windows/Mac
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/public/storage 2>/dev/null || true

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/public 2>/dev/null || true

# Crear enlace simbólico si no existe
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creando enlace simbólico de storage..."
    php artisan storage:link 2>/dev/null || true
fi

echo "Configuración completada. Iniciando servicios..."

# Ejecutar el comando pasado al contenedor
exec "$@"
