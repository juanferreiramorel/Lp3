# Implementación de Sistema Multiempresa

## Resumen
Este documento describe la implementación del sistema multiempresa en el proyecto Lp3.

## Arquitectura

### Modelo de Datos

```
empresas (Nueva tabla)
  ├── id_empresa (PK)
  ├── razon_social
  ├── nombre_comercial
  ├── ruc
  ├── direccion
  ├── telefono
  ├── email
  ├── logo
  └── created_at, updated_at

sucursales (Modificada)
  ├── id_sucursal (PK)
  ├── id_empresa (FK → empresas.id_empresa) ← NUEVO
  ├── descripcion
  ├── direccion
  ├── telefono
  └── id_ciudad (FK)

Relación:
  ventas → apertura_cierre_cajas → cajas → sucursales → empresas
  recibos_simples → sucursales → empresas
  cobros → cuentas_a_cobrar → ventas → ... → empresas
```

## Pasos de Instalación

### 1. Ejecutar Script SQL

Ejecutar el archivo `/sql/multiempresa_system.sql` en la base de datos PostgreSQL:

```bash
psql -U tu_usuario -d nombre_base_datos -f sql/multiempresa_system.sql
```

O desde el cliente pgAdmin/DBeaver:
- Abrir el archivo `sql/multiempresa_system.sql`
- Ejecutar el script completo

**Importante:** El script ya incluye:
- Creación de tabla `empresas`
- Alteración de tabla `sucursales` con campo `id_empresa`
- Datos de ejemplo (2 empresas)
- Asignación automática de sucursales existentes a la empresa 1

### 2. Asignar Empresas a Sucursales Existentes

Si tienes sucursales que no fueron asignadas automáticamente:

```sql
-- Ver sucursales sin empresa
SELECT * FROM sucursales WHERE id_empresa IS NULL;

-- Asignar empresa a una sucursal
UPDATE sucursales SET id_empresa = 1 WHERE id_sucursal = X;
```

### 3. Permisos de Usuario

Agregar permisos para el módulo de empresas:

```sql
-- Insertar permisos (ajustar según tu tabla de permisos)
INSERT INTO permissions (name, guard_name) VALUES
('empresas index', 'web'),
('empresas create', 'web'),
('empresas edit', 'web'),
('empresas destroy', 'web');

-- Asignar permisos a roles (ejemplo para rol admin)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name LIKE 'empresas%';
```

### 4. Crear Vistas para CRUD de Empresas (Opcional)

El controlador `EmpresaController` está creado. Para completar el CRUD, crea las siguientes vistas en `resources/views/empresas/`:

- `index.blade.php` - Listado de empresas
- `create.blade.php` - Formulario de creación
- `edit.blade.php` - Formulario de edición
- `show.blade.php` - Ver detalle
- `table.blade.php` - Tabla AJAX

**Puedes basarte en las vistas de `sucursales` como ejemplo.**

## Archivos Modificados

### Controladores
- ✅ `VentaController.php` - Método `factura()` actualizado
- ✅ `ReciboSimpleController.php` - Método `imprimir()` actualizado
- ✅ `EmpresaController.php` - NUEVO (CRUD completo)

### Vistas
- ✅ `ventas/factura.blade.php` - Datos dinámicos de empresa
- ✅ `recibossimples/recibo.blade.php` - Datos dinámicos de empresa
- ✅ `cuentasacobrar/recibo.blade.php` - YA tenía datos dinámicos

### Rutas
- ✅ `web.php` - Agregada ruta resource para `empresas`

### SQL
- ✅ `sql/multiempresa_system.sql` - Script de creación e inicialización

## Funcionalidades Implementadas

### ✅ Facturas
- Los datos de empresa se obtienen dinámicamente desde:
  `ventas → apertura → caja → sucursal → empresa`
- Campos mostrados: nombre_comercial, RUC, dirección, teléfono, email, logo

### ✅ Recibos Simples
- Los datos de empresa se obtienen dinámicamente desde:
  `recibos_simples → sucursal → empresa`
- Campos mostrados: nombre_comercial, RUC, dirección, teléfono, email, logo

### ✅ Recibos de Cuentas a Cobrar
- Ya funcionaba correctamente con datos de sucursal
- Ahora también puede acceder a datos de empresa vía sucursal

### ✅ Gestión de Empresas
- CRUD completo en `EmpresaController`
- Rutas RESTful configuradas
- Validaciones implementadas
- Restricción: No se puede eliminar empresa con sucursales asociadas

## Datos de Ejemplo Incluidos

El script SQL incluye 2 empresas de ejemplo:

### Empresa 1: UTIC
- RUC: 80000000-1
- Dirección: Atyra 1750 c/Capitán Rivas
- Teléfono: 021 200 100

### Empresa 2: J&O Departamentos
- RUC: 80000001-2
- Dirección: Calle Leonardo Salinas, Barrio Kennedy - Capiatá
- Teléfono: 0981 123 456

## Próximos Pasos Sugeridos

1. **Crear vistas del CRUD de empresas** (basándote en sucursales)
2. **Agregar campo `id_empresa` al formulario de sucursales** para seleccionar la empresa al crear/editar
3. **Actualizar otros reportes** que necesiten mostrar datos de empresa
4. **Implementar filtro por empresa** en reportes y listados
5. **Agregar validación** para evitar operaciones entre sucursales de diferentes empresas (opcional)

## Verificación

### Verificar estructura de base de datos:
```sql
-- Verificar tabla empresas
SELECT * FROM empresas;

-- Verificar que sucursales tienen empresa asignada
SELECT s.*, e.nombre_comercial
FROM sucursales s
LEFT JOIN empresas e ON s.id_empresa = e.id_empresa;

-- Verificar que facturas traen datos de empresa
SELECT v.id_venta, v.factura_nro, e.nombre_comercial, e.ruc
FROM ventas v
JOIN apertura_cierre_cajas acc ON v.id_apertura = acc.id_apertura
JOIN cajas cj ON acc.id_caja = cj.id_caja
JOIN sucursales s ON cj.id_sucursal = s.id_sucursal
LEFT JOIN empresas e ON s.id_empresa = e.id_empresa
LIMIT 10;
```

### Verificar que las facturas muestran datos correctos:
1. Ir a Ventas
2. Seleccionar una venta
3. Click en "Imprimir Factura"
4. Verificar que muestra los datos de la empresa (no hardcodeados)

### Verificar que los recibos muestran datos correctos:
1. Ir a Recibos Simples
2. Seleccionar un recibo
3. Click en "Imprimir"
4. Verificar que muestra los datos de la empresa (no hardcodeados)

## Soporte

Para cualquier duda sobre la implementación, revisar:
- Controladores: `app/Http/Controllers/VentaController.php`, `ReciboSimpleController.php`, `EmpresaController.php`
- Vistas: `resources/views/ventas/factura.blade.php`, `resources/views/recibossimples/recibo.blade.php`
- SQL: `sql/multiempresa_system.sql`

---

**Fecha de implementación:** 2025-11-13
**Implementado por:** Claude Code (Asistente IA)
