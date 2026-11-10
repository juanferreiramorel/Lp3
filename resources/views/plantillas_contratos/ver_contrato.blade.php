@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-file-contract"></i>
                        Contrato Generado
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('plantillas_contratos.contratos_generados') }}">
                        <i class="fas fa-list"></i> Ver Todos
                    </a>
                    <a class="btn btn-danger float-right mr-2" href="{{ route('plantillas_contratos.descargar_pdf', $contrato->id_contrato_generado) }}" target="_blank">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                    {{-- Prints the PDF itself, in the browser's viewer, so the
                         printout and the download are the same document. --}}
                    <a class="btn btn-primary float-right mr-2" href="{{ route('plantillas_contratos.descargar_pdf', ['id' => $contrato->id_contrato_generado, 'ver' => 1]) }}" target="_blank" rel="noopener">
                        <i class="fas fa-print"></i> Imprimir
                    </a>
                    <a class="btn btn-warning float-right mr-2" href="{{ route('plantillas_contratos.editar_contrato', $contrato->id_contrato_generado) }}">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <!-- Información del Contrato -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong><i class="fas fa-file-alt"></i> Plantilla:</strong>
                        <p>{{ $contrato->nombre_plantilla }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-user"></i> Cliente:</strong>
                        <p>{{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-id-card"></i> CI:</strong>
                        <p>{{ $contrato->clie_ci }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar"></i> Fecha de Generación:</strong>
                        <p>{{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                @if ($contrato->observaciones)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong><i class="fas fa-sticky-note"></i> Observaciones:</strong>
                            <p>{{ $contrato->observaciones }}</p>
                        </div>
                    </div>
                @endif

                <div class="row mt-2">
                    <div class="col-12">
                        <strong><i class="fas fa-user-tie"></i> Generado por:</strong>
                        <p>{{ $contrato->usuario_nombre ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido del Contrato -->
        <div class="card" id="contrato-contenido">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-file-signature"></i> Contenido del Contrato</h5>
            </div>
            <div class="card-body">
                <!-- Contenido oculto -->
                <textarea id="contenido_contrato_vista" style="display:none;">{{ $contrato->contenido_generado }}</textarea>

                <!-- Editor de solo lectura con estilo de hoja de papel -->
                <div id="editor-vista-contrato-wrapper" class="editor-hoja-papel contrato-lectura">
                    <div id="editor-vista-contrato"></div>
                </div>
            </div>
        </div>
    </div>

        @push('page_scripts')
        <script>
            let editorVistaContrato;

            document.addEventListener('DOMContentLoaded', function() {
                if (!window.CKEDITOR_CONFIG || !window.CKEDITOR_CONTRATO) {
                    console.error('CKEditor no está disponible en ver_contrato');
                    return;
                }

                const editorElement = document.getElementById('editor-vista-contrato');
                const contenidoField = document.getElementById('contenido_contrato_vista');

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
                        editorVistaContrato = editor;
                        editor.setData(contenidoField.value || '');
                        editor.enableReadOnlyMode('contrato-view');
                        console.log('✅ CKEditor 5 (solo lectura) inicializado en ver_contrato');
                    })
                    .catch(error => {
                        console.error('❌ Error al inicializar CKEditor en ver_contrato:', error);
                    });
            });
        </script>
    @endpush
@endsection


