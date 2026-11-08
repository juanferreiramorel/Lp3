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
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Exponer puerto 80
EXPOSE 80

# Usar script de inicialización
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
