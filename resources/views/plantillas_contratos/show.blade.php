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

                <div class="card bg-light">
                    <div class="card-body">
                        <!-- Contenido oculto -->
                        <textarea id="contenido_plantilla_vista" style="display:none;">{{ $plantilla->contenido_plantilla }}</textarea>

                        <!-- Editor de solo lectura -->
                        <div id="editor-vista-plantilla-wrapper" class="contrato-lectura">
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
            .contrato-lectura .ck.ck-editor {
                background: white;
                border-radius: 6px;
                border: 1px solid #dee2e6;
            }

            .contrato-lectura .ck.ck-toolbar {
                display: none !important;
            }

            .contrato-lectura .ck-editor__main > .ck-editor__editable {
                border: none !important;
                font-family: 'Times New Roman', Times, serif !important;
                font-size: 12pt !important;
                min-height: 400px !important;
                max-height: 600px !important;
                overflow-y: auto !important;
                padding: 2cm 1.5cm !important;
                line-height: 1.6 !important;
                background: white !important;
                color: #000000 !important;
            }

            .contrato-lectura .ck-content h1 {
                font-size: 28pt !important;
                font-weight: bold;
                margin-top: 16pt;
                margin-bottom: 8pt;
                color: #1a1a1a;
                line-height: 1.3;
            }

            .contrato-lectura .ck-content h2 {
                font-size: 22pt !important;
                font-weight: bold;
                margin-top: 14pt;
                margin-bottom: 7pt;
                color: #1a1a1a;
                line-height: 1.3;
            }

            .contrato-lectura .ck-content h3 {
                font-size: 18pt !important;
                font-weight: bold;
                margin-top: 12pt;
                margin-bottom: 6pt;
                color: #1a1a1a;
                line-height: 1.3;
            }

            .contrato-lectura .ck-content h4 {
                font-size: 16pt !important;
                font-weight: bold;
                margin-top: 10pt;
                margin-bottom: 5pt;
                color: #1a1a1a;
            }

            .contrato-lectura .ck-content h5 {
                font-size: 14pt !important;
                font-weight: bold;
                margin-top: 8pt;
                margin-bottom: 4pt;
                color: #1a1a1a;
            }

            .contrato-lectura .ck-content h6 {
                font-size: 12pt !important;
                font-weight: bold;
                margin-top: 6pt;
                margin-bottom: 3pt;
                color: #1a1a1a;
            }

            .contrato-lectura .ck-content p {
                margin-top: 0;
                margin-bottom: 10pt;
                text-align: justify;
            }

            .contrato-lectura .ck-content ul,
            .contrato-lectura .ck-content ol {
                padding-left: 1.5em;
                margin-bottom: 10pt;
            }

            .contrato-lectura .ck-content li {
                margin-bottom: 5pt;
            }

            .contrato-lectura .ck-content blockquote {
                border-left: 4px solid #3b82f6;
                padding-left: 16px;
                margin: 16px 0;
                font-style: italic;
                color: #4b5563;
            }

            .contrato-lectura .ck-content a {
                color: #2563eb;
                text-decoration: underline;
            }

            .contrato-lectura .ck-content a:hover {
                color: #1d4ed8;
            }

            .contrato-lectura .ck-editor__main > .ck-editor__editable::-webkit-scrollbar {
                width: 12px;
            }

            .contrato-lectura .ck-editor__main > .ck-editor__editable::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            .contrato-lectura .ck-editor__main > .ck-editor__editable::-webkit-scrollbar-thumb {
                background: #94a3b8;
                border-radius: 6px;
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


