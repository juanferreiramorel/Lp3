FROM php:8.2-fpm

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP (incluyendo opcache)
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip opcache

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar configuración de Nginx y Supervisor
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar configuración PHP-FPM optimizada
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copiar composer files PRIMERO para aprovechar caché de Docker
# Si composer.json no cambia, este paso se salta en rebuilds
COPY composer.json composer.lock /var/www/html/

# Instalar dependencias de Composer
RUN composer install --no-scripts --no-autoloader --no-dev

# Copiar el resto de los archivos del proyecto
COPY . /var/www/html

# Generar autoloader optimizado
RUN composer dump-autoload --optimize --no-dev

# Crear backup del vendor para sincronizar al volumen nombrado
RUN cp -r /var/www/html/vendor /var/www/html/vendor_backup

# Crear directorios necesarios para Laravel (incluyendo uploads)
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/testing \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/app/uploads \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/public/storage \
    && mkdir -p /var/www/html/public/img/productos \
    && mkdir -p /var/www/html/public/img/categorias \
    && mkdir -p /var/www/html/public/img/usuarios \
    && mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/www/html/public/archivos

# Crear enlace simbólico de storage
RUN php artisan storage:link || true

# Establecer permisos correctos para www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 777 /var/www/html/public/img \
    && chmod -R 777 /var/www/html/public/uploads \
    && chmod -R 777 /var/www/html/public/archivos \
    && chmod -R 775 /var/www/html/public

# Crear script de inicialización integrado con sincronización de vendor
RUN echo '#!/bin/bash\n\
set -e\n\
echo "Iniciando configuración del contenedor..."\n\
\n\
# Sincronizar vendor si el volumen está vacío\n\
if [ ! -f /var/www/html/vendor/autoload.php ]; then\n\
    echo "Sincronizando vendor desde imagen..."\n\
    if [ -d /var/www/html/vendor_backup ]; then\n\
        cp -r /var/www/html/vendor_backup/* /var/www/html/vendor/ 2>/dev/null || true\n\
    else\n\
        cd /var/www/html && composer install --no-dev --optimize-autoloader\n\
    fi\n\
fi\n\
\n\
# Crear directorios de storage\n\
mkdir -p /var/www/html/storage/framework/sessions\n\
mkdir -p /var/www/html/storage/framework/cache/data\n\
mkdir -p /var/www/html/storage/framework/views\n\
mkdir -p /var/www/html/storage/framework/testing\n\
mkdir -p /var/www/html/storage/logs\n\
mkdir -p /var/www/html/storage/app/public\n\
mkdir -p /var/www/html/storage/app/uploads\n\
mkdir -p /var/www/html/bootstrap/cache\n\
\n\
# Crear directorios de public\n\
mkdir -p /var/www/html/public/img/productos\n\
mkdir -p /var/www/html/public/img/categorias\n\
mkdir -p /var/www/html/public/img/usuarios\n\
mkdir -p /var/www/html/public/uploads\n\
mkdir -p /var/www/html/public/archivos\n\
\n\
# Establecer propietario\n\
chown -R www-data:www-data /var/www/html/storage\n\
chown -R www-data:www-data /var/www/html/bootstrap/cache\n\
chown -R www-data:www-data /var/www/html/vendor\n\
chown -R www-data:www-data /var/www/html/public/img\n\
chown -R www-data:www-data /var/www/html/public/uploads\n\
chown -R www-data:www-data /var/www/html/public/archivos\n\
chown -R www-data:www-data /var/www/html/public/storage 2>/dev/null || true\n\
\n\
# Establecer permisos\n\
chmod -R 775 /var/www/html/storage\n\
chmod -R 777 /var/www/html/storage/framework/views\n\
chmod -R 775 /var/www/html/bootstrap/cache\n\
chmod -R 775 /var/www/html/vendor\n\
chmod -R 777 /var/www/html/public/img\n\
chmod -R 777 /var/www/html/public/uploads\n\
chmod -R 777 /var/www/html/public/archivos\n\
\n\
# Crear enlace simbólico\n\
if [ ! -L /var/www/html/public/storage ]; then\n\
    echo "Creando enlace simbólico de storage..."\n\
    php artisan storage:link 2>/dev/null || true\n\
fi\n\
\n\
# Limpiar vistas compiladas antiguas para evitar problemas de permisos\n\
rm -rf /var/www/html/storage/framework/views/*.php 2>/dev/null || true\n\
\n\
# Warm-up de caché de Laravel (ejecutar como www-data para permisos correctos)\n\
echo "Generando caché de Laravel..."\n\
cd /var/www/html\n\
su -s /bin/bash www-data -c "php artisan config:cache" 2>/dev/null || true\n\
su -s /bin/bash www-data -c "php artisan route:cache" 2>/dev/null || true\n\
su -s /bin/bash www-data -c "php artisan view:cache" 2>/dev/null || true\n\
\n\
echo "Configuración completada. Iniciando servicios..."\n\
exec "$@"' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Exponer puerto 80
EXPOSE 80

# Usar script de inicialización
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
