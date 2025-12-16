@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-file-alt"></i>
                        Detalle de Plantilla
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('plantillas_contratos.index') }}">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a class="btn btn-primary float-right mr-2" href="{{ route('plantillas_contratos.edit', $plantilla->id_plantilla_contrato) }}">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a class="btn btn-success float-right mr-2" href="{{ route('plantillas_contratos.generar_formulario', $plantilla->id_plantilla_contrato) }}">
                        <i class="fas fa-file-contract"></i> Generar Contrato
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4>{{ $plantilla->nombre_plantilla }}</h4>
                        @if ($plantilla->descripcion)
                            <p class="text-muted">{{ $plantilla->descripcion }}</p>
                        @endif
                    </div>
                    <div class="col-md-4 text-right">
                        @if ($plantilla->activo)
                            <span class="badge badge-success badge-lg">Activa</span>
                        @else
                            <span class="badge badge-secondary badge-lg">Inactiva</span>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong><i class="fas fa-user"></i> Creado por:</strong>
                        <p>{{ $plantilla->usuario_nombre ?? 'No especificado' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-calendar"></i> Fecha de Creación:</strong>
                        <p>{{ \Carbon\Carbon::parse($plantilla->fecha_creacion)->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-clock"></i> Última Modificación:</strong>
                        <p>{{ \Carbon\Carbon::parse($plantilla->fecha_modificacion)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3"><i class="fas fa-file-alt"></i> Contenido de la Plantilla</h5>

                <div class="card" style="background-color: #e0e0e0;">
                    <div class="card-body">
                        <!-- Contenido oculto -->
                        <textarea id="contenido_plantilla_vista" style="display:none;">{{ $plantilla->contenido_plantilla }}</textarea>

                        <!-- Editor de solo lectura con estilo de hoja -->
                        <div id="editor-vista-plantilla-wrapper" class="editor-hoja-papel contrato-lectura">
                            <div id="editor-vista-plantilla"></div>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3"><i class="fas fa-keyboard"></i> Palabras Clave Disponibles</h5>

                <div class="row">
                    @foreach ($palabrasClave as $categoria => $palabras)
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary">{{ $categoria }}</h6>
                            <ul class="list-unstyled">
                                @foreach ($palabras as $palabra => $descripcion)
                                    <li class="mb-2">
                                        <code style="background-color: #e7f3ff; color: #0066cc; padding: 2px 8px; border-radius: 4px;">{{ $palabra }}</code>
                                        <small class="text-muted"> - {{ $descripcion }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

        @push('page_css')

        <style>
            /* Estilos específicos para modo lectura */
            .contrato-lectura .ck.ck-toolbar {
                display: none !important;
            }
            
            .contrato-lectura .ck-editor__main > .ck-editor__editable {
                min-height: 500px !important;
                max-height: 70vh !important;
            }
        </style>
    @endpush

        @push('page_scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (!window.CKEDITOR_CONFIG || !window.CKEDITOR_CONTRATO) {
                    console.error('CKEditor no está disponible en show');
                    return;
                }

                const editorElement = document.getElementById('editor-vista-plantilla');
                const contenidoField = document.getElementById('contenido_plantilla_vista');

                if (!editorElement || !contenidoField) {
                    return;
                }

                const { ClassicEditor } = window.CKEDITOR_CONFIG;

                const editorConfig = window.CKEDITOR_CONTRATO.buildConfig({
                    toolbar: {
                        items: []
                    }
                });

                editorConfig.readOnly = true;

                ClassicEditor
                    .create(editorElement, editorConfig)
                    .then(editor => {
                        editor.setData(contenidoField.value || '');
                        editor.enableReadOnlyMode('plantilla-show');
                        console.log('✅ CKEditor 5 (solo lectura) inicializado en show');
                    })
                    .catch(error => {
                        console.error('❌ Error al inicializar CKEditor en show:', error);
                    });
            });
        </script>
    @endpush
@endsection


