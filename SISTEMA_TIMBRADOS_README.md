# Sistema de Timbrados para Facturación

## 📁 Ubicación de los Archivos Creados

### Modelos (app/Models/)
- ✅ `Timbrado.php` - Modelo principal de timbrados
- ✅ `Empresa.php` - Modelo de empresa con relación a timbrados
- ✅ `Venta.php` - Modelo de venta con relación a timbrados

### Controladores (app/Http/Controllers/)
- ✅ `TimbradoController.php` - CRUD completo de timbrados

### Vistas (resources/views/timbrados/)
- ✅ `index.blade.php` - Listado de timbrados
- ✅ `create.blade.php` - Formulario de creación
- ✅ `edit.blade.php` - Formulario de edición
- ✅ `show.blade.php` - Detalles y estadísticas
- ✅ `table.blade.php` - Tabla de listado
- ✅ `fields.blade.php` - Campos del formulario

### Migraciones (database/migrations/)
- ✅ `2025_11_14_000001_create_timbrados_table.php`

### Scripts SQL (sql/)
- ✅ `crear_sistema_timbrados.sql` - Script manual de migración

### Archivos Modificados
- ✅ `app/Http/Controllers/VentaController.php` - Integración con timbrados
- ✅ `resources/views/ventas/factura.blade.php` - Muestra datos de timbrado
- ✅ `resources/views/ventas/fields.blade.php` - Selector de timbrados
- ✅ `routes/web.php` - Rutas de timbrados

---

## 🚀 Pasos para Activar el Sistema

### 1. Ejecutar la Migración SQL

Conectarte a PostgreSQL y ejecutar:

```bash
psql -U tu_usuario -d lp2 -f sql/crear_sistema_timbrados.sql
```

O desde pgAdmin/DBeaver, abre el archivo `sql/crear_sistema_timbrados.sql` y ejecútalo.

### 2. Asignar Permisos a Roles

Ejecutar en PostgreSQL (reemplaza `1` con el ID de tu rol admin):

```sql
-- Asignar permisos de timbrados al rol admin
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, 1  -- Reemplazar 1 por el ID de tu rol
FROM permissions p
WHERE p.name LIKE 'timbrados%'
ON CONFLICT DO NOTHING;
```

### 3. Acceder al Sistema

En tu navegador, visita:
```
http://tu-dominio.com/timbrados
```

---

## 📋 Cómo Usar el Sistema

### Crear un Timbrado

1. Ir a **Timbrados** → **Agregar Timbrado**
2. Completar el formulario:
   - **Empresa**: Seleccionar la empresa
   - **Número de Timbrado**: Número de autorización de la SET
   - **Fecha Inicio**: Fecha desde cuando es válido
   - **Fecha Vencimiento**: Fecha hasta cuando es válido
   - **Establecimiento**: 001 (3 dígitos)
   - **Punto de Expedición**: 001 (3 dígitos)
   - **Rango Inicial**: Número inicial (ej: 1)
   - **Rango Final**: Número final (ej: 10000)
   - **Observaciones**: Notas adicionales (opcional)
3. Guardar

### Usar Timbrado en Ventas

1. Ir a **Ventas** → **Crear Venta**
2. En el formulario verás un campo **"Timbrado"**
3. Seleccionar el timbrado vigente que deseas usar
4. El número de factura se generará automáticamente
5. Completar el resto del formulario y guardar

### Ver Estadísticas de un Timbrado

1. Ir a **Timbrados**
2. Click en el ícono del ojo 👁️ de cualquier timbrado
3. Verás:
   - Facturas disponibles
   - Facturas usadas
   - Porcentaje de uso
   - Días hasta vencimiento
   - Facturas asociadas

---

## ⚠️ Validaciones Automáticas

El sistema automáticamente:

✅ **Valida vigencia**: No permite usar timbrados vencidos
✅ **Valida disponibilidad**: No permite usar timbrados agotados
✅ **Actualiza estados**: Cambia a VENCIDO o AGOTADO automáticamente
✅ **Incrementa contador**: Suma 1 al usar cada factura
✅ **Previene eliminación**: No se puede borrar si tiene facturas

---

## 🎨 Interfaz Visual

### Indicadores de Estado

- 🟢 **VIGENTE**: Badge verde - Timbrado activo y disponible
- 🔴 **VENCIDO**: Badge rojo - Timbrado fuera de vigencia
- 🟡 **AGOTADO**: Badge amarillo - Facturas agotadas
- ⚫ **CANCELADO**: Badge gris - Timbrado cancelado

### Alertas

- ⚠️ **Próximo a vencer**: < 30 días
- ⚠️ **Próximo a agotarse**: > 90% usado

---

## 🔍 Filtros Disponibles

En el listado de timbrados puedes filtrar por:
- **Empresa**
- **Estado** (Vigente, Vencido, Agotado, Cancelado)
- **Búsqueda** (número de timbrado o empresa)

---

## 📊 Estructura de Base de Datos

### Tabla: `timbrados`
```sql
- id_timbrado (PK)
- id_empresa (FK → empresas)
- numero_timbrado (UNIQUE)
- fecha_inicio
- fecha_vencimiento
- rango_inicio
- rango_fin
- numero_actual (contador)
- establecimiento (001)
- punto_expedicion (001)
- estado (VIGENTE|VENCIDO|AGOTADO|CANCELADO)
- observaciones
```

### Tabla: `ventas` (columnas agregadas)
```sql
- id_timbrado (FK → timbrados)
- numero_factura_timbrado
```

---

## 🔗 Rutas del Sistema

```
GET    /timbrados                    - Listado
GET    /timbrados/create             - Formulario crear
POST   /timbrados                    - Guardar
GET    /timbrados/{id}               - Ver detalles
GET    /timbrados/{id}/edit          - Formulario editar
PUT    /timbrados/{id}               - Actualizar
DELETE /timbrados/{id}               - Eliminar
POST   /timbrados/{id}/cancelar      - Cancelar
GET    /timbrados/vigentes-empresa   - API AJAX
```

---

## 🛠️ Troubleshooting

### No aparece el selector de timbrados en ventas
- Verificar que exista al menos un timbrado VIGENTE para la empresa de tu sucursal
- Verificar que tengas una caja abierta

### Error al crear venta con timbrado
- Verificar que el timbrado esté vigente
- Verificar que tenga facturas disponibles
- Verificar que la fecha de vencimiento no haya pasado

### No puedo editar/eliminar un timbrado
- Los timbrados con facturas asociadas no se pueden editar ni eliminar
- Usar la opción "Cancelar" en su lugar

---

## 📞 Soporte

Si tienes problemas:
1. Verificar que la migración se ejecutó correctamente
2. Verificar permisos de usuario
3. Revisar logs en `storage/logs/laravel.log`

---

**Versión**: 1.0.0
**Fecha**: 2025-11-14
**Desarrollado por**: Claude AI
