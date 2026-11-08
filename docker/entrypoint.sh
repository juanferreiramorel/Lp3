#!/bin/bash

set -euo pipefail

echo "Iniciando configuración del contenedor..."

# Sincronizar vendor si el volumen está vacío
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Sincronizando vendor desde imagen..."
    if [ -d /var/www/html/vendor_backup ]; then
        mkdir -p /var/www/html/vendor
        cp -r /var/www/html/vendor_backup/* /var/www/html/vendor/ 2>/dev/null || true
    else
        cd /var/www/html && composer install --no-dev --optimize-autoloader
    fi
fi

# Crear directorios de storage
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/app/uploads
mkdir -p /var/www/html/bootstrap/cache

# Crear directorios de public
mkdir -p /var/www/html/public/img/productos
mkdir -p /var/www/html/public/img/categorias
mkdir -p /var/www/html/public/img/usuarios
mkdir -p /var/www/html/public/uploads
mkdir -p /var/www/html/public/archivos

# Corregir propietarios en rutas escribibles de Laravel
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/vendor
chown -R www-data:www-data /var/www/html/public/img
chown -R www-data:www-data /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/archivos
chown -R www-data:www-data /var/www/html/public/storage 2>/dev/null || true

# Asegurar permisos de escritura en directorios y logs existentes
chmod -R 775 /var/www/html/storage
chmod -R 777 /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/vendor
chmod -R 777 /var/www/html/public/img
chmod -R 777 /var/www/html/public/uploads
chmod -R 777 /var/www/html/public/archivos
find /var/www/html/storage/logs -type f -exec chmod 664 {} \; 2>/dev/null || true

# Precrear logs base como www-data para evitar archivos root:root en bind mounts
for log_file in \
    /var/www/html/storage/logs/laravel.log \
    /var/www/html/storage/logs/php-errors.log \
    /var/www/html/storage/logs/php-slow.log
do
    su -s /bin/bash www-data -c "umask 0002 && touch '$log_file'"
done

# Crear enlace simbólico
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creando enlace simbólico de storage..."
    php artisan storage:link 2>/dev/null || true
fi

# Limpiar vistas compiladas antiguas para evitar problemas de permisos
rm -rf /var/www/html/storage/framework/views/*.php 2>/dev/null || true

# Warm-up de caché de Laravel (ejecutar como www-data para permisos correctos)
echo "Generando caché de Laravel..."
cd /var/www/html
su -s /bin/bash www-data -c "php artisan config:cache" 2>/dev/null || true
su -s /bin/bash www-data -c "php artisan route:cache" 2>/dev/null || true
su -s /bin/bash www-data -c "php artisan view:cache" 2>/dev/null || true

echo "Configuración completada. Iniciando servicios..."
exec "$@"
