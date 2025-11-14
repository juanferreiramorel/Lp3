# Instrucciones para Probar Imágenes de Empresas

## Problema Reportado
Las imágenes de empresas no se visualizan en:
1. Formulario de edición de empresas
2. Facturas y recibos

## Configuración Verificada ✅

### 1. Enlace Simbólico
```bash
# El enlace simbólico está correctamente configurado:
ls -la /home/user/Lp3/public/storage
# Resultado: storage -> /home/user/Lp3/storage/app/public
```

### 2. Directorio de Logos
```bash
# El directorio de logos existe:
ls -la /home/user/Lp3/storage/app/public/logos/
# El directorio está vacío porque no se han subido logos todavía
```

### 3. Código de Vistas
- **empresas/fields.blade.php**: ✅ Configurado correctamente
  ```blade
  @if(isset($empresa) && $empresa->logo)
      <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo actual" style="max-width: 150px; max-height: 150px;">
  @endif
  ```

- **ventas/factura.blade.php**: ✅ Configurado correctamente
  ```blade
  @if($ventas->empresa_logo)
      <img alt="Logo" src="{{ asset('storage/' . $ventas->empresa_logo) }}">
  @endif
  ```

- **recibossimples/recibo.blade.php**: ✅ Configurado correctamente
  ```blade
  @if($recibo->empresa_logo)
      <img alt="Logo" src="{{ asset('storage/' . $recibo->empresa_logo) }}">
  @endif
  ```

### 4. Controlador
- **EmpresaController**: ✅ Genera nombres únicos y guarda en `storage/app/public/logos/`

## Pasos para Probar

### Paso 1: Crear una Empresa con Logo
1. Ir a **Configuraciones → Empresas**
2. Hacer clic en **Crear Nueva Empresa**
3. Completar los campos obligatorios:
   - Razón Social
   - RUC
4. **Seleccionar una imagen** en el campo "Logo de la Empresa"
5. Guardar

### Paso 2: Verificar que se Guardó el Logo
```bash
# Ver archivos en el directorio de logos:
ls -la /home/user/Lp3/storage/app/public/logos/

# También verificar vía web:
ls -la /home/user/Lp3/public/storage/logos/
```

### Paso 3: Verificar en Base de Datos
```sql
SELECT id_empresa, razon_social, logo FROM empresas;
```
El campo `logo` debe tener un valor como: `logos/1731625843_6735a4b3c5f12.jpg`

### Paso 4: Editar la Empresa
1. Editar la empresa creada
2. **Verificar que aparezca la vista previa del logo**
3. Si no aparece, revisar:
   - La consola del navegador (F12 → Console) para errores 404
   - Permisos del directorio storage

### Paso 5: Asignar Empresa a una Sucursal
1. Ir a **Configuraciones → Sucursales**
2. Editar una sucursal existente o crear una nueva
3. En el combo **"Empresa"**, seleccionar la empresa creada
4. Guardar

### Paso 6: Probar en Factura/Recibo
1. Crear una venta desde la sucursal configurada
2. Imprimir la factura o recibo
3. **Verificar que aparezca el logo de la empresa**

## Solución de Problemas

### Si no aparece la imagen en el formulario de edición:

1. **Verificar que el archivo existe físicamente:**
   ```bash
   ls -la /home/user/Lp3/storage/app/public/logos/
   ```

2. **Verificar en la base de datos:**
   ```sql
   SELECT logo FROM empresas WHERE id_empresa = 1;
   ```

3. **Verificar el enlace simbólico:**
   ```bash
   ls -la /home/user/Lp3/public/ | grep storage
   ```
   Si no existe, crear:
   ```bash
   ln -s /home/user/Lp3/storage/app/public /home/user/Lp3/public/storage
   ```

4. **Verificar permisos:**
   ```bash
   chmod -R 755 /home/user/Lp3/storage/app/public/logos/
   ```

### Si no aparece en facturas/recibos:

1. **Verificar que la sucursal tiene empresa asignada:**
   ```sql
   SELECT s.id_sucursal, s.descripcion, s.id_empresa, e.razon_social
   FROM sucursales s
   LEFT JOIN empresas e ON s.id_empresa = e.id_empresa;
   ```

2. **Verificar que la consulta JOIN está trayendo el logo:**
   ```sql
   SELECT v.id_venta, e.logo as empresa_logo
   FROM ventas v
   JOIN apertura_cierre_cajas acc ON v.id_apertura = acc.id_apertura
   JOIN cajas cj ON acc.id_caja = cj.id_caja
   JOIN sucursales s ON cj.id_sucursal = s.id_sucursal
   LEFT JOIN empresas e ON s.id_empresa = e.id_empresa
   WHERE v.id_venta = [ID_DE_TU_VENTA];
   ```

3. **Verificar en navegador:**
   - Abrir la factura/recibo
   - Abrir las herramientas de desarrollador (F12)
   - Ir a la pestaña "Console" o "Network"
   - Buscar errores 404 para las imágenes

## Formato de Nombres de Archivo

Los logos se guardan con nombres únicos para evitar conflictos:
- Formato: `timestamp_uniqid.extension`
- Ejemplo: `1731625843_6735a4b3c5f12.jpg`
- Ubicación: `storage/app/public/logos/`
- Acceso web: `public/storage/logos/`
- URL en blade: `{{ asset('storage/logos/1731625843_6735a4b3c5f12.jpg') }}`

## Mejoras Implementadas

### 1. Nombres Únicos
- ✅ Se generan automáticamente usando `time() . '_' . uniqid()`
- ✅ Evita conflictos cuando múltiples usuarios suben archivos con el mismo nombre

### 2. Limpieza Automática
- ✅ Al actualizar: se elimina el logo anterior antes de guardar el nuevo
- ✅ Previene acumulación de archivos huérfanos

### 3. Validación
- ✅ Solo acepta imágenes (jpg, png, gif)
- ✅ Tamaño máximo: 2MB
- ✅ Validación en backend y frontend

## Notas Importantes

1. **El directorio de logos está vacío inicialmente** - esto es normal. Se poblará cuando se suban logos.

2. **El campo empresa en sucursales es opcional** - si no se asigna empresa, los recibos/facturas no mostrarán logo.

3. **Los productos usan el mismo sistema** - Las imágenes de productos también usan nombres únicos ahora.

4. **Permisos de escritura** - Asegúrate que el servidor web tenga permisos para escribir en `storage/app/public/logos/`.

## Próximos Pasos

1. Subir un logo de prueba
2. Verificar que se vea en el formulario de edición
3. Asignar la empresa a una sucursal
4. Crear una venta y verificar que el logo aparezca en la factura/recibo

Si después de seguir estos pasos las imágenes aún no se ven, revisar:
- Logs de Laravel: `storage/logs/laravel.log`
- Consola del navegador (F12)
- Permisos del servidor web
