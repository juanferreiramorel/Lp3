@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        <i class="fas fa-edit"></i>
                        Vista Previa del Contrato - Puede Editar Antes de Guardar
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="row">
            <!-- Información del Cliente -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-user"></i> Datos del Cliente</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Nombre:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_nombre }} {{ $cliente->clie_apellido }}</dd>

                            <dt class="col-sm-5">CI:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_ci }}</dd>

                            <dt class="col-sm-5">Teléfono:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_telefono ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Dirección:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_direccion ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Ciudad:</dt>
                            <dd class="col-sm-7">{{ $cliente->ciudad ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Departamento:</dt>
                            <dd class="col-sm-7">{{ $cliente->departamento ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Plantilla Usada</h3>
                    </div>
                    <div class="card-body">
                        <h5>{{ $plantilla->nombre_plantilla }}</h5>
                        @if ($plantilla->descripcion)
                            <p class="text-muted">{{ $plantilla->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Revise y Edite el Contenido Antes de Guardar
                        </h3>
                    </div>

                    {!! Form::open(['route' => 'plantillas_contratos.guardar_contrato']) !!}

                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Puede modificar el contenido del contrato según sus necesidades.
                            Una vez guardado, podrá editarlo nuevamente si es necesario.
                        </div>

                        <!-- Campos ocultos -->
                        {!! Form::hidden('id_plantilla_contrato', $plantilla->id_plantilla_contrato) !!}
                        {!! Form::hidden('id_cliente', $cliente->id_cliente) !!}

                        <!-- Contenido del Contrato -->
                        <div class="form-group">
                            {!! Form::label('contenido_generado', 'Contenido del Contrato:') !!}
                            {!! Form::textarea('contenido_generado', $contenidoGenerado, [
                                'class' => 'form-control',
                                'id' => 'contenido_generado',
                                'style' => 'display:none;'
                            ]) !!}

                            <!-- Contenedor del editor con formato de hoja -->
                            <div class="editor-wrap">
                                <!-- Mensaje de carga -->
                                <div id="editor-loading" class="text-center p-5 editor-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted">
                                        <i class="fas fa-file-word"></i> Inicializando editor profesional...
                                    </p>
                                </div>

                                <!-- Editor con estilo de hoja de papel -->
                                <div id="editor-previa-wrapper" class="editor-hoja-papel d-none">
                                    <div id="editor-previa"></div>
                                </div>
                            </div>

                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-edit"></i>
                                Edite el contenido según sea necesario. Puede copiar y pegar desde Word.
                            </small>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            {!! Form::label('observaciones', 'Observaciones (Opcional):') !!}
                            {!! Form::textarea('observaciones', $observaciones, [
                                'class' => 'form-control',
                                'rows' => 3,
                                'placeholder' => 'Agregue notas o comentarios sobre este contrato'
                            ]) !!}
                        </div>

                        <!-- Información de la fecha -->
                        <div class="alert alert-light">
                            <strong><i class="fas fa-calendar"></i> Fecha del Contrato:</strong>
                            {{ \Carbon\Carbon::parse($fecha_contrato)->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                {!! Form::button('<i class="fas fa-save"></i> Guardar Contrato', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-success btn-lg btn-block'
                                ]) !!}
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('plantillas_contratos.generar_formulario', $plantilla->id_plantilla_contrato) }}"
                                   class="btn btn-default btn-lg btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>

    @push('page_css')

        <style>
            .editor-loading {
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: #f9fafb;
            }

            .card-footer {
                position: relative;
                z-index: 10 !important;
                background: white !important;
            }
        </style>
    @endpush

        @push('page_scripts')
        <script>
            let editorPrevia;

            document.addEventListener('DOMContentLoaded', function() {
                if (!window.CKEDITOR_CONFIG || !window.CKEDITOR_CONTRATO) {
                    console.error('CKEditor no está disponible en previa_editable');
                    return;
                }

                const editorElement = document.getElementById('editor-previa');
                const contenidoField = document.getElementById('contenido_generado');
                const loading = document.getElementById('editor-loading');
                const wrapper = document.getElementById('editor-previa-wrapper');

                if (!editorElement || !contenidoField) {
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
                        editorPrevia = editor;

                        if (loading) {
                            loading.style.display = 'none';
                        }
                        if (wrapper) {
                            wrapper.classList.remove('d-none');
                        }

                        const contenidoInicial = contenidoField.value;
                        if (contenidoInicial) {
                            editor.setData(contenidoInicial);
                        }

                        const sincronizarContenido = () => {
                            contenidoField.value = editor.getData();
                        };

                        editor.model.document.on('change:data', sincronizarContenido);
                        sincronizarContenido();

                        console.log('✅ CKEditor 5 inicializado en previa_editable');
                    })
                    .catch(error => {
                        console.error('❌ Error al inicializar CKEditor en previa_editable:', error);
                        if (loading) {
                            loading.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Error al cargar el editor:</strong> ${error.message}
                                </div>
                            `;
                        }
                    });
            });
        </script>
    @endpush
@endsection

