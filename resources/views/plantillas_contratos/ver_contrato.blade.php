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
                    <button class="btn btn-primary float-right mr-2" onclick="imprimirContrato()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a class="btn btn-warning float-right mr-2" href="{{ route('plantillas_contratos.editar_contrato', $contrato->id_contrato_generado) }}">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('sweetalert::alert')

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
            <div class="card-body" style="background-color: white; padding: 20px;">
                <!-- Contenido oculto -->
                <textarea id="contenido_contrato_vista" style="display:none;">{{ $contrato->contenido_generado }}</textarea>

                <!-- Editor de solo lectura -->
                <div id="editor-vista-contrato-wrapper" class="contrato-lectura">
                    <div id="editor-vista-contrato"></div>
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
            let editorVistaContrato;
            const tituloVentanaContrato = @json("Contrato - {$contrato->clie_apellido}, {$contrato->clie_nombre}");
            const estilosImpresionContrato = `
                body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.6; padding: 2cm; margin: 0; color: #000000; }
                h1 { font-size: 28pt !important; font-weight: bold; margin-top: 16pt; margin-bottom: 8pt; color: #1a1a1a; line-height: 1.3; }
                h2 { font-size: 22pt !important; font-weight: bold; margin-top: 14pt; margin-bottom: 7pt; color: #1a1a1a; line-height: 1.3; }
                h3 { font-size: 18pt !important; font-weight: bold; margin-top: 12pt; margin-bottom: 6pt; color: #1a1a1a; line-height: 1.3; }
                h4 { font-size: 16pt !important; font-weight: bold; margin-top: 10pt; margin-bottom: 5pt; color: #1a1a1a; }
                h5 { font-size: 14pt !important; font-weight: bold; margin-top: 8pt; margin-bottom: 4pt; color: #1a1a1a; }
                h6 { font-size: 12pt !important; font-weight: bold; margin-top: 6pt; margin-bottom: 3pt; color: #1a1a1a; }
                p { margin-top: 0; margin-bottom: 10pt; text-align: justify; }
                ul, ol { padding-left: 1.5em; margin-bottom: 10pt; }
                li { margin-bottom: 5pt; }
                blockquote { border-left: 4px solid #3b82f6; padding-left: 16px; margin: 16px 0; font-style: italic; color: #4b5563; }
                a { color: #2563eb; text-decoration: underline; }
            `;

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

            window.imprimirContrato = function imprimirContrato() {
                const campoContenido = document.getElementById('contenido_contrato_vista');
                const contenidoHTML = editorVistaContrato?.getData() || (campoContenido?.value || '');

                const ventanaImpresion = window.open('', '', 'width=800,height=600');
                if (!ventanaImpresion) {
                    alert('No se pudo abrir la ventana de impresión. Verifique que el bloqueador de ventanas emergentes esté desactivado.');
                    return;
                }

                const documento = ventanaImpresion.document;
                documento.open();
                documento.write(`<html><head><title>${tituloVentanaContrato}</title><style>${estilosImpresionContrato}</style></head><body>${contenidoHTML}</body></html>`);
                documento.close();

                setTimeout(() => {
                    ventanaImpresion.focus();
                    ventanaImpresion.print();
                    ventanaImpresion.close();
                }, 250);
            };
        </script>
    @endpush
@endsection


