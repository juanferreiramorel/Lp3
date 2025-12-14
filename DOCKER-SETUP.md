# Configuración Docker para TAPERP

## Cambios realizados

### 1. Variables de entorno
- El proyecto usa tu archivo `.env` actual directamente
- Solo se sobrescribe `DB_HOST=db` para apuntar al contenedor de PostgreSQL
- Todas tus configuraciones (API OCR, credenciales, etc.) se mantienen intactas

### 2. Configuración de PostgreSQL
- Base de datos: `lp2` (tu BD actual)
- Usuario: `postgres`
- Password: `rootpassword`
- Puerto en host: `5433` (cambié a 5433 para no interferir con tu PostgreSQL local en 5432)
- Puerto interno: `5432`

### 3. Solución del error de build
Se excluyó `public/storage` del build de Docker porque es un enlace simbólico que causa problemas.
El enlace se recreará automáticamente dentro del contenedor.

## Comandos rápidos

### Construir e iniciar
```bash
docker-compose up -d --build
```

### Ver logs
```bash
docker-compose logs -f app
```

### Detener
```bash
docker-compose down
```

### Acceder al contenedor de la app
```bash
docker-compose exec app bash
```

### Ejecutar migraciones
```bash
docker-compose exec app php artisan migrate
```

### Limpiar caché
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

## Acceso a servicios

- **Aplicación Laravel**: http://localhost:8000
- **PostgreSQL**: localhost:5433 (desde tu máquina local)
- **PgAdmin**: http://localhost:5050
  - Email: admin@admin.com
  - Password: admin

## Configurar PgAdmin

1. Accede a http://localhost:5050
2. Login: admin@admin.com / admin
3. Agregar servidor:
   - **General**: Name = "TAPERP Database"
   - **Connection**:
     - Host: `db`
     - Port: `5432`
     - Database: `lp2`
     - Username: `postgres`
     - Password: `rootpassword`

## Conectar desde tu máquina local

Si quieres conectarte a la base de datos Docker desde tu máquina (DBeaver, pgAdmin local, etc.):

```
Host: localhost
Port: 5433
Database: lp2
Username: postgres
Password: rootpassword
```

## Migrar datos desde tu PostgreSQL local

Si quieres importar tu base de datos actual al contenedor:

### Opción 1: Dump y restore
```bash
# Hacer backup de tu BD local
pg_dump -h localhost -p 5432 -U postgres -d lp2 > lp2_backup.sql

# Restaurar en el contenedor
docker-compose exec -T db psql -U postgres -d lp2 < lp2_backup.sql
```

### Opción 2: Usar pgAdmin
1. Conecta a tu BD local en pgAdmin
2. Click derecho en la BD → Backup
3. Conecta a la BD Docker en pgAdmin
4. Click derecho en la BD → Restore → Selecciona el backup

## Trabajar en modo desarrollo

### Opción 1: Usar Docker para todo
```bash
# Iniciar Docker
docker-compose up -d

# Trabajar normalmente, los cambios en el código se reflejan inmediatamente
# Laravel se ejecuta desde http://localhost:8000
```

### Opción 2: Usar solo PostgreSQL en Docker
Si prefieres ejecutar Laravel localmente pero usar PostgreSQL en Docker:

1. Inicia solo la base de datos:
```bash
docker-compose up -d db
```

2. Modifica tu `.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=5433
```

3. Ejecuta Laravel localmente:
```bash
php artisan serve
```

## Troubleshooting

### Error: "Could not find driver"
El contenedor ya tiene instalado `pdo_pgsql`, pero si hay problemas:
```bash
docker-compose exec app php -m | grep pgsql
```

### Permisos en storage
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### La aplicación no carga
```bash
# Ver logs del contenedor
docker-compose logs -f app

# Ver logs de Nginx
docker-compose exec app tail -f /var/log/nginx/error.log

# Ver logs de PHP-FPM
docker-compose exec app tail -f /var/log/supervisor/supervisord.log
```

### Recrear todo desde cero
```bash
docker-compose down -v
docker-compose up -d --build
docker-compose exec app php artisan migrate
```

## Notas importantes

1. **Tu `.env` se mantiene**: No se modifica tu archivo `.env`, Docker lo lee y solo sobrescribe `DB_HOST`
2. **Puerto PostgreSQL**: Cambié el puerto a 5433 para no conflictuar con tu instalación local
3. **Volúmenes**: Los datos de PostgreSQL se guardan en un volumen Docker (`postgres_data`), no se perderán al reiniciar
4. **AI OCR Service**: Tu configuración de AI OCR apunta a `http://172.25.208.1:3005`, asegúrate de que sea accesible desde Docker
