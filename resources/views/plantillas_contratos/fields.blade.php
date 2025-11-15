<!-- Nombre Plantilla Field -->
<div class="form-group col-sm-8">
    {!! Form::label('nombre_plantilla', 'Nombre de la Plantilla:') !!}
    {!! Form::text('nombre_plantilla', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ej: Contrato de Prestación de Servicios']) !!}
</div>

<!-- Estado Field -->
<div class="form-group col-sm-4">
    <label>&nbsp;</label>
    <div class="form-check">
        {!! Form::hidden('activo', 0) !!}
        {!! Form::checkbox('activo', 1, isset($plantilla) ? $plantilla->activo : true, ['class' => 'form-check-input', 'id' => 'activo']) !!}
        {!! Form::label('activo', 'Plantilla Activa', ['class' => 'form-check-label']) !!}
    </div>
</div>

<!-- Descripción Field -->
<div class="form-group col-sm-12">
    {!! Form::label('descripcion', 'Descripción (Opcional):') !!}
    {!! Form::textarea('descripcion', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Breve descripción de la plantilla']) !!}
</div>

<div class="col-12">
    <hr>
    <h5 class="mb-3"><i class="fas fa-keyboard"></i> Palabras Clave Disponibles</h5>
</div>

<!-- Palabras Clave Disponibles -->
<div class="col-12 mb-4">
    <div class="card bg-light">
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle"></i>
                Puede usar estas palabras clave en el contenido de la plantilla. Serán reemplazadas automáticamente con los datos del cliente.
            </p>

            <div class="row">
                @foreach ($palabrasClave as $categoria => $palabras)
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-primary">{{ $categoria }}</h6>
                        <ul class="list-unstyled palabras-clave-list">
                            @foreach ($palabras as $palabra => $descripcion)
                                <li class="mb-2">
                                    <code class="palabra-clave-item" style="cursor: pointer; user-select: all;"
                                          onclick="insertarPalabra('{{ $palabra }}')"
                                          title="Click para insertar">{{ $palabra }}</code>
                                    <small class="text-muted"> - {{ $descripcion }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="alert alert-info mb-0 mt-3">
                <i class="fas fa-lightbulb"></i>
                <strong>Consejo:</strong> Haga click en cualquier palabra clave para insertarla automáticamente en el contenido.
            </div>
        </div>
    </div>
</div>

<!-- Contenido Plantilla Field -->
<div class="form-group col-sm-12">
    {!! Form::label('contenido_plantilla', 'Contenido de la Plantilla:') !!}
    {!! Form::textarea('contenido_plantilla', null, ['class' => 'form-control', 'id' => 'contenido_plantilla']) !!}

    <small class="form-text text-muted mt-2">
        <i class="fas fa-question-circle"></i>
        Use las palabras clave haciendo clic en ellas para que sean reemplazadas automáticamente.
    </small>
</div>

<style>
    .palabras-clave-list code {
        background-color: #e7f3ff;
        color: #0066cc;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        transition: all 0.2s;
    }

    .palabras-clave-list code:hover {
        background-color: #0066cc;
        color: white;
        transform: scale(1.05);
    }

    .ck-editor__editable {
        min-height: 400px;
        max-height: 600px;
        font-family: 'Times New Roman', Times, serif !important;
        font-size: 12pt !important;
        line-height: 1.6 !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
</style>

<script>
    let editorInstance;

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.CKEDITOR_CONFIG || !window.CKEDITOR_CONTRATO) {
            console.error('CKEditor no está disponible en window.CKEDITOR_CONFIG');
            return;
        }

        const editorElement = document.querySelector('#contenido_plantilla');
        if (!editorElement) {
            return;
        }

        const { ClassicEditor } = window.CKEDITOR_CONFIG;

        const editorConfig = window.CKEDITOR_CONTRATO.buildConfig({
            simpleUpload: {
                uploadUrl: "{{ route('plantillas_contratos.upload') }}",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }
        });

        ClassicEditor
            .create(editorElement, editorConfig)
            .then(editor => {
                editorInstance = editor;
                console.log('✅ CKEditor 5 local inicializado correctamente con carga de imágenes');
                mostrarNotificacion('Editor profesional listo para usar');
            })
            .catch(error => {
                console.error('❌ Error al inicializar CKEditor:', error);
                alert('Error al cargar el editor: ' + error.message);
            });
    });

    // Función para insertar palabras clave
    function insertarPalabra(palabra) {
        if (editorInstance) {
            const viewFragment = editorInstance.data.processor.toView(palabra);
            const modelFragment = editorInstance.data.toModel(viewFragment);
            editorInstance.model.insertContent(modelFragment);
            editorInstance.editing.view.focus();
            mostrarNotificacion('Palabra clave insertada: ' + palabra);
        }
    }

    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje) {
        const notificacion = document.createElement('div');
        notificacion.className = 'alert alert-success position-fixed';
        notificacion.style.cssText = 'top: 20px; right: 20px; z-index: 9999; animation: fadeIn 0.3s; max-width: 350px;';
        notificacion.innerHTML = '<i class="fas fa-check"></i> ' + mensaje;
        document.body.appendChild(notificacion);

        setTimeout(() => {
            notificacion.style.animation = 'fadeOut 0.3s';
            setTimeout(() => notificacion.remove(), 300);
        }, 2500);
    }
</script>
