# Módulo de Plantillas de Contratos

## Descripción

Sistema CRUD completo para la gestión de plantillas de contratos con reemplazo automático de palabras clave. Permite crear plantillas personalizadas que se pueden generar automáticamente con los datos de los clientes.

## Características

- ✅ CRUD completo de plantillas de contratos
- ✅ Sistema de palabras clave con reemplazo automático (formato: `--palabra--`)
- ✅ Generación de contratos personalizados por cliente
- ✅ **Vista previa editable antes de guardar** - Edite el contrato generado antes de guardarlo definitivamente
- ✅ **Edición de contratos ya generados** - Modifique contratos existentes en cualquier momento
- ✅ **Eliminación de contratos generados** - Borre contratos que ya no necesite
- ✅ Historial completo de contratos generados
- ✅ Exportación a PDF con diseño profesional
- ✅ Impresión directa desde el navegador
- ✅ Búsqueda y filtrado de plantillas y contratos
- ✅ Sistema de permisos integrado con Spatie

## Instalación

### 1. Ejecutar scripts SQL

Ejecuta los siguientes scripts en tu base de datos PostgreSQL en este orden:

```bash
# 1. Crear las tablas
psql -U tu_usuario -d tu_base_datos -f sql/create_plantillas_contratos.sql

# 2. Crear los permisos
psql -U tu_usuario -d tu_base_datos -f sql/permisos_plantillas_contratos.sql
```

### 2. Verificar archivos creados

El módulo incluye los siguientes archivos:

#### Modelos
- `app/Models/PlantillaContrato.php` - Modelo principal
- `app/Models/ContratoGenerado.php` - Historial de contratos
- `app/Models/Cliente.php` - Modelo de cliente (actualizado)

#### Controladores
- `app/Http/Controllers/PlantillaContratoController.php` - Controlador principal con todos los métodos CRUD

#### Rutas
- Rutas agregadas en `routes/web.php`

#### Vistas
- `resources/views/plantillas_contratos/index.blade.php` - Listado de plantillas
- `resources/views/plantillas_contratos/create.blade.php` - Crear plantilla
- `resources/views/plantillas_contratos/edit.blade.php` - Editar plantilla
- `resources/views/plantillas_contratos/show.blade.php` - Ver detalle de plantilla
- `resources/views/plantillas_contratos/fields.blade.php` - Campos del formulario
- `resources/views/plantillas_contratos/table.blade.php` - Tabla de plantillas
- `resources/views/plantillas_contratos/generar.blade.php` - Formulario para generar contrato
- `resources/views/plantillas_contratos/previa_editable.blade.php` - **Vista previa editable del contrato**
- `resources/views/plantillas_contratos/ver_contrato.blade.php` - Ver contrato generado
- `resources/views/plantillas_contratos/editar_contrato.blade.php` - **Editar contrato existente**
- `resources/views/plantillas_contratos/contratos_generados.blade.php` - Listado de contratos generados
- `resources/views/plantillas_contratos/pdf.blade.php` - Plantilla PDF

#### Menú
- Enlace agregado en `resources/views/layouts/menu.blade.php`

## Estructura de Base de Datos

### Tabla: `plantillas_contratos`

```sql
CREATE TABLE plantillas_contratos (
    id_plantilla_contrato SERIAL PRIMARY KEY,
    nombre_plantilla VARCHAR(255) NOT NULL,
    descripcion TEXT,
    contenido_plantilla TEXT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES users(id)
);
```

### Tabla: `contratos_generados`

```sql
CREATE TABLE contratos_generados (
    id_contrato_generado SERIAL PRIMARY KEY,
    id_plantilla_contrato INTEGER REFERENCES plantillas_contratos(id_plantilla_contrato),
    id_cliente INTEGER REFERENCES clientes(id_cliente),
    contenido_generado TEXT NOT NULL,
    observaciones TEXT,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES users(id)
);
```

## Palabras Clave Disponibles

### Datos del Cliente

| Palabra Clave | Descripción | Ejemplo |
|--------------|-------------|---------|
| `--nombre_cliente--` | Nombre del cliente | Juan |
| `--apellido_cliente--` | Apellido del cliente | Pérez |
| `--nombre_completo--` | Nombre completo (nombre + apellido) | Juan Pérez |
| `--ci_cliente--` | Número de documento (CI) | 1234567 |
| `--telefono_cliente--` | Teléfono del cliente | 0981-123456 |
| `--direccion_cliente--` | Dirección del cliente | Av. España 123 |
| `--ciudad_cliente--` | Nombre de la ciudad | Asunción |
| `--departamento_cliente--` | Nombre del departamento | Central |
| `--fecha_nacimiento--` | Fecha de nacimiento (formato dd/mm/yyyy) | 15/05/1990 |
| `--edad_cliente--` | Edad del cliente en años | 33 |

### Datos del Sistema

| Palabra Clave | Descripción | Ejemplo |
|--------------|-------------|---------|
| `--fecha_actual--` | Fecha actual del sistema (formato dd/mm/yyyy) | 13/11/2025 |
| `--fecha_contrato--` | Fecha del contrato (por defecto fecha actual) | 13/11/2025 |
| `--usuario_sistema--` | Nombre del usuario que genera el contrato | Admin |

## Ejemplo de Plantilla

```
CONTRATO DE PRESTACIÓN DE SERVICIOS

Entre --nombre_completo--, con CI N° --ci_cliente--, con domicilio en
--direccion_cliente--, --ciudad_cliente--, --departamento_cliente--,
teléfono --telefono_cliente--, en adelante denominado "EL CLIENTE", y
LA EMPRESA, se celebra el presente contrato en fecha --fecha_contrato--
bajo las siguientes cláusulas:

PRIMERA: El cliente --nombre_completo-- solicita los servicios de [DESCRIPCIÓN].

SEGUNDA: El cliente se compromete a cumplir con los términos establecidos.

TERCERA: La vigencia del presente contrato es de [PLAZO] a partir de la
fecha de firma.

En prueba de conformidad se firma el presente contrato en dos ejemplares
de un mismo tenor y a un solo efecto, en fecha --fecha_actual--.


_____________________                    _____________________
    EL CLIENTE                              LA EMPRESA
--nombre_completo--
CI: --ci_cliente--
```

### Resultado después del reemplazo

```
CONTRATO DE PRESTACIÓN DE SERVICIOS

Entre Juan Pérez, con CI N° 1234567, con domicilio en Av. España 123,
Asunción, Central, teléfono 0981-123456, en adelante denominado "EL CLIENTE",
y LA EMPRESA, se celebra el presente contrato en fecha 13/11/2025 bajo las
siguientes cláusulas:

PRIMERA: El cliente Juan Pérez solicita los servicios de [DESCRIPCIÓN].

SEGUNDA: El cliente se compromete a cumplir con los términos establecidos.

TERCERA: La vigencia del presente contrato es de [PLAZO] a partir de la
fecha de firma.

En prueba de conformidad se firma el presente contrato en dos ejemplares
de un mismo tenor y a un solo efecto, en fecha 13/11/2025.


_____________________                    _____________________
    EL CLIENTE                              LA EMPRESA
Juan Pérez
CI: 1234567
```

## Uso del Módulo

### 1. Crear una Plantilla

1. Accede a **Plantillas Contratos** desde el menú lateral
2. Haz clic en **Agregar Plantilla**
3. Completa los campos:
   - **Nombre de la Plantilla**: Nombre descriptivo
   - **Descripción**: Breve descripción (opcional)
   - **Plantilla Activa**: Checkbox para activar/desactivar
   - **Contenido de la Plantilla**: Escribe el contrato usando las palabras clave
4. Haz clic en las palabras clave disponibles para insertarlas automáticamente
5. Guarda la plantilla

### 2. Generar un Contrato

1. En el listado de plantillas, haz clic en el botón verde **Generar Contrato**
2. Selecciona el cliente para el cual quieres generar el contrato
3. Opcionalmente, especifica una fecha del contrato
4. Agrega observaciones si es necesario
5. Haz clic en **Generar Contrato**
6. **El sistema mostrará una vista previa editable** con:
   - El contenido del contrato con todas las palabras clave reemplazadas
   - Los datos del cliente en un panel lateral
   - Un editor de texto donde puedes modificar el contrato antes de guardarlo
7. **Edita el contrato** según tus necesidades (este es el momento ideal para personalizar el contenido)
8. Haz clic en **Guardar Contrato** para guardarlo definitivamente

💡 **Ventaja**: Puedes revisar y modificar cada contrato antes de guardarlo, ajustando detalles específicos para cada cliente.

### 3. Editar un Contrato Ya Generado

1. Accede a **Contratos Generados** desde el botón en la página de plantillas
2. En el listado, localiza el contrato que deseas editar
3. Haz clic en el botón amarillo **Editar** (icono de lápiz)
4. Modifica el contenido del contrato y/o las observaciones
5. Haz clic en **Actualizar Contrato** para guardar los cambios

💡 **Útil para**: Corregir errores, actualizar información o ajustar términos después de guardar el contrato.

### 4. Eliminar un Contrato Generado

1. Accede a **Contratos Generados**
2. Localiza el contrato que deseas eliminar
3. Haz clic en el botón rojo **Eliminar** (icono de papelera)
4. Confirma la eliminación en el diálogo de confirmación

⚠️ **Atención**: Esta acción es permanente y no se puede deshacer.

### 5. Ver y Descargar Contratos

1. Accede a **Contratos Generados** desde el botón en la página de plantillas
2. Visualiza el historial completo de contratos generados
3. Opciones disponibles en cada contrato:
   - **Ver** (ojo): Visualizar el contrato en pantalla
   - **Editar** (lápiz): Modificar el contenido del contrato
   - **Descargar PDF** (PDF): Exportar el contrato a PDF
   - **Eliminar** (papelera): Borrar el contrato permanentemente
   - **Imprimir**: Imprimir directamente desde el navegador (disponible en la vista de detalle)

### 6. Editar una Plantilla

1. En el listado de plantillas, haz clic en el botón de editar
2. Modifica los campos necesarios
3. Guarda los cambios
4. La fecha de modificación se actualizará automáticamente

### 7. Eliminar una Plantilla

⚠️ **Importante**: No se puede eliminar una plantilla si tiene contratos generados asociados. En ese caso, puedes desactivarla usando el checkbox "Plantilla Activa".

## Permisos

El módulo incluye los siguientes permisos:

- `plantillas_contratos index` - Ver listado de plantillas
- `plantillas_contratos create` - Crear nuevas plantillas
- `plantillas_contratos edit` - Editar plantillas existentes
- `plantillas_contratos destroy` - Eliminar plantillas

Asigna estos permisos a los roles correspondientes desde el módulo de **Roles y Permisos**.

## Rutas del Módulo

```php
// Listado de plantillas
GET /plantillas-contratos

// Crear plantilla
GET /plantillas-contratos/create
POST /plantillas-contratos

// Ver plantilla
GET /plantillas-contratos/{id}

// Editar plantilla
GET /plantillas-contratos/{id}/edit
PATCH /plantillas-contratos/{id}

// Eliminar plantilla
DELETE /plantillas-contratos/{id}

// Formulario para generar contrato
GET /plantillas-contratos/{id}/generar-formulario

// Generar contrato (muestra vista previa editable)
POST /plantillas-contratos/{id}/generar

// Guardar contrato después de edición
POST /plantillas-contratos/guardar-contrato

// Listado de contratos generados
GET /plantillas-contratos/contratos-generados

// Ver contrato generado
GET /plantillas-contratos/contrato/{id}

// Editar contrato generado
GET /plantillas-contratos/contrato/{id}/editar
PUT /plantillas-contratos/contrato/{id}/actualizar

// Eliminar contrato generado
DELETE /plantillas-contratos/contrato/{id}/eliminar

// Descargar PDF
GET /plantillas-contratos/contrato/{id}/pdf
```

## Características Técnicas

### Backend
- Laravel 10.x
- PostgreSQL
- Eloquent ORM + consultas SQL nativas
- Spatie Laravel Permission para control de acceso
- DomPDF para generación de PDFs

### Frontend
- Blade Templates
- Bootstrap 4
- AdminLTE
- Font Awesome
- SweetAlert2
- jQuery
- Select2

### Seguridad
- Middleware de autenticación
- Control de permisos por ruta
- Validación de datos en servidor
- Protección contra SQL Injection
- CSRF protection

## Notas Adicionales

### Extender las Palabras Clave

Para agregar nuevas palabras clave, edita el método `generarContrato()` en el modelo `PlantillaContrato.php` y actualiza el array `$reemplazos`:

```php
$reemplazos = [
    '--nueva_palabra--' => 'Valor de la palabra',
    // ... resto de palabras clave
];
```

También actualiza el método `getPalabrasClave()` para mostrar la nueva palabra en la interfaz:

```php
public static function getPalabrasClave()
{
    return [
        'Datos del Cliente' => [
            '--nueva_palabra--' => 'Descripción de la palabra',
            // ...
        ],
    ];
}
```

### Personalizar el PDF

Edita la vista `resources/views/plantillas_contratos/pdf.blade.php` para cambiar el diseño del PDF generado.

## Soporte

Para reportar problemas o solicitar mejoras, contacta al equipo de desarrollo.

---

**Versión**: 1.0.0
**Fecha**: 13/11/2025
**Autor**: Sistema Lp3
