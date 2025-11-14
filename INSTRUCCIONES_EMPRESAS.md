# 🏢 Instrucciones para Configurar el Sistema Multiempresa

## ⚠️ IMPORTANTE: Ejecutar Script SQL

Para que el módulo de empresas funcione correctamente, **DEBES ejecutar el script SQL** en tu base de datos PostgreSQL.

### Opción 1: Ejecutar desde terminal

```bash
psql -U tu_usuario -d nombre_base_datos -f sql/verificar_multiempresa.sql
```

### Opción 2: Ejecutar desde pgAdmin / DBeaver

1. Abrir pgAdmin o DBeaver
2. Conectarse a tu base de datos
3. Abrir el archivo `sql/verificar_multiempresa.sql`
4. Ejecutar todo el script

### Opción 3: Copiar y pegar en SQL Editor

1. Abrir el archivo `sql/verificar_multiempresa.sql`
2. Copiar todo el contenido
3. Pegarlo en tu editor SQL favorito (pgAdmin, DBeaver, etc.)
4. Ejecutar

---

## ✅ Lo que hace el script

El script `verificar_multiempresa.sql` hace lo siguiente automáticamente:

1. ✓ Verifica si la tabla `empresas` existe, si no la crea
2. ✓ Verifica si el campo `id_empresa` existe en `sucursales`, si no lo agrega
3. ✓ Crea las foreign keys necesarias
4. ✓ Inserta una empresa de ejemplo si no existen empresas
5. ✓ Asigna la primera empresa a todas las sucursales existentes
6. ✓ Muestra un resumen de empresas y sucursales

---

## 📝 Configurar Permisos (Solo si usas roles/permisos)

Si tu sistema usa roles y permisos, ejecuta este SQL:

```sql
-- Insertar permisos
INSERT INTO permissions (name, guard_name) VALUES
('empresas index', 'web'),
('empresas create', 'web'),
('empresas edit', 'web'),
('empresas destroy', 'web')
ON CONFLICT DO NOTHING;

-- Asignar permisos al rol admin (ajusta el role_id según tu sistema)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, 1  -- Cambiar 1 por el ID de tu rol admin
FROM permissions p
WHERE p.name LIKE 'empresas%'
ON CONFLICT DO NOTHING;
```

---

## 🎯 Cómo usar el módulo de empresas

### 1. Crear una empresa

1. Ir a **Configuraciones → Empresas**
2. Click en **"Agregar Empresa"**
3. Completar los campos:
   - **Razón Social** (obligatorio)
   - **Nombre Comercial** (opcional)
   - **RUC** (obligatorio, único)
   - **Dirección** (opcional)
   - **Teléfono** (opcional)
   - **Email** (opcional)
   - **Logo** (opcional, JPG/PNG/GIF, máx. 2MB)
4. Click en **"Guardar"**

### 2. Asignar empresa a sucursales

1. Ir a **Configuraciones → Sucursales**
2. Editar una sucursal
3. Seleccionar la empresa correspondiente
4. Guardar

### 3. Ver los logos en facturas/recibos

Una vez configurado:
- Las **facturas** mostrarán automáticamente el logo y datos de la empresa
- Los **recibos** mostrarán automáticamente el logo y datos de la empresa

Los datos se obtienen de la cadena:
```
Venta → Apertura Caja → Caja → Sucursal → Empresa
```

---

## 🔍 Verificar que todo funciona

### Verificar en base de datos:

```sql
-- Ver todas las empresas
SELECT * FROM empresas;

-- Ver sucursales con su empresa
SELECT s.id_sucursal, s.descripcion, e.razon_social, e.nombre_comercial
FROM sucursales s
LEFT JOIN empresas e ON s.id_empresa = e.id_empresa;

-- Ver si una venta tiene datos de empresa
SELECT v.id_venta, v.factura_nro, e.nombre_comercial, e.ruc, e.logo
FROM ventas v
JOIN apertura_cierre_cajas acc ON v.id_apertura = acc.id_apertura
JOIN cajas cj ON acc.id_caja = cj.id_caja
JOIN sucursales s ON cj.id_sucursal = s.id_sucursal
LEFT JOIN empresas e ON s.id_empresa = e.id_empresa
LIMIT 5;
```

### Verificar en la aplicación:

1. Crear una venta
2. Imprimir la factura
3. Verificar que aparezca:
   - Logo de la empresa
   - Nombre comercial
   - RUC
   - Dirección
   - Teléfono
   - Email

---

## 🐛 Solución de Problemas

### Problema: "No puedo crear empresa"

**Solución:**
1. Verificar que ejecutaste el script SQL
2. Revisar los logs en `storage/logs/laravel.log`
3. El log te dirá exactamente qué falló

### Problema: "Error al subir logo"

**Solución:**
1. Verificar que existe el directorio: `storage/app/public/logos`
2. Verificar permisos: `chmod -R 775 storage/app/public/logos`
3. Verificar enlace simbólico: `ls -la public/ | grep storage`

Si no existe el enlace:
```bash
ln -s /ruta/completa/storage/app/public /ruta/completa/public/storage
```

### Problema: "No aparece el logo en factura/recibo"

**Solución:**
1. Verificar que el enlace simbólico existe: `public/storage → storage/app/public`
2. Verificar que el logo se subió correctamente: `ls storage/app/public/logos`
3. Verificar en la base de datos que el campo logo tiene valor: `SELECT logo FROM empresas;`

### Problema: "La factura no muestra datos de empresa"

**Solución:**
1. Verificar que la sucursal tiene empresa asignada:
```sql
SELECT s.*, e.*
FROM sucursales s
LEFT JOIN empresas e ON s.id_empresa = e.id_empresa;
```

2. Si `id_empresa` es NULL, asignar una empresa:
```sql
UPDATE sucursales SET id_empresa = 1 WHERE id_sucursal = X;
```

---

## 📞 Soporte

Si después de seguir estas instrucciones sigues teniendo problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. El log te mostrará exactamente qué está fallando
3. Copia el mensaje de error completo para mejor diagnóstico

---

**Fecha de creación:** 2025-11-14
**Versión:** 1.0
