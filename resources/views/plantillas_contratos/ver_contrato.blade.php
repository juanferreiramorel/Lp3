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
            <div class="card-body" style="background-color: #e0e0e0; padding: 20px;">
                <!-- Contenido oculto -->
                <textarea id="contenido_contrato_vista" style="display:none;">{{ $contrato->contenido_generado }}</textarea>

                <!-- Editor de solo lectura con estilo de hoja de papel -->
                <div id="editor-vista-contrato-wrapper" class="editor-hoja-papel contrato-lectura">
                    <div id="editor-vista-contrato"></div>
                </div>
            </div>
        </div>
    </div>

        @push('page_css')


        <style>
            /* Contenedor principal que simula el escritorio/visor PDF */
            .card-body.p-0 {
                background-color: #525659; /* Color de fondo típico de visores PDF */
                padding: 40px 0 !important;
                text-align: center;
            }

            /* Estilos específicos para modo lectura y corrección Firefox */
            .contrato-lectura .ck.ck-toolbar {
                display: none !important;
            }
            
            /* Simulación de Hoja A4 */
            .contrato-lectura .ck-editor__main > .ck-editor__editable {
                width: 21cm !important;
                min-height: 29.7cm !important;
                padding: 2cm 2cm !important; /* Márgenes estándar de impresión (coincide con @page) */
                margin: 0 auto !important;
                background-color: white !important;
                box-shadow: 0 0 10px rgba(0,0,0,0.5) !important;
                border: 1px solid #ddd !important;
                text-align: left;
                
                /* Tipografía exacta de impresión */
                font-family: "Times New Roman", Times, serif !important;
                font-size: 12pt !important;
                line-height: 1.6 !important; /* Coincide con impresión */
                color: #000 !important;
            }

            /* Mejoras de renderizado de texto */
            .ck-content p {
                margin-bottom: 1em !important; /* Standard spacing */
                margin-top: 0 !important;
                text-indent: 0;
            }
            
            .ck-content h1, .ck-content h2, .ck-content h3 {
                margin-top: 12pt;
                margin-bottom: 6pt;
            }

            /* Optimización para justificado en Firefox */
            .ck-content [style*="text-align:justify"] {
                text-align: justify;
                text-justify: inter-word;
            }


        </style>
    @endpush

        @push('page_scripts')
        <script>
            let editorVistaContrato;
            const tituloVentanaContrato = @json("Contrato - {$contrato->clie_apellido}, {$contrato->clie_nombre}");

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
                // Obtener el contenido del editor
                const contenidoHTML = editorVistaContrato?.getData() || 
                    document.getElementById('contenido_contrato_vista')?.value || '';

                // Estilos simples y directos para impresión
                const estilosImpresion = `
                    @page { size: A4; margin: 2cm; }
                    body { 
                        font-family: "Times New Roman", Times, serif; 
                        font-size: 12pt; 
                        line-height: 1.6; 
                        color: #000; 
                        background: #fff;
                        margin: 0;
                        padding: 0;
                    }
                    p {
                        margin-bottom: 1em;
                        margin-top: 0;
                    }
                    h1, h2, h3 {
                        margin-top: 12pt;
                        margin-bottom: 6pt;
                        line-height: 1.2;
                    }
                    h1, h2, h3, h4, h5, h6 { font-weight: bold; margin: 10pt 0 6pt 0; }
                    h1 { font-size: 16pt; text-align: center; text-decoration: underline; }
                    h2 { font-size: 14pt; }
                    h3 { font-size: 13pt; }
                    h4, h5, h6 { font-size: 12pt; }
                    p { margin: 0 0 8pt 0; text-align: justify; }
                    ul, ol { padding-left: 20pt; margin: 0 0 8pt 0; }
                    li { margin-bottom: 4pt; }
                    table { width: 100%; border-collapse: collapse; margin: 8pt 0; }
                    th, td { border: 1px solid #000; padding: 4pt 6pt; text-align: left; }
                    th { background: #f0f0f0; font-weight: bold; }
                    img { max-width: 100%; height: auto; }
                    strong, b { font-weight: bold; }
                    em, i { font-style: italic; }
                    u { text-decoration: underline; }
                `;

                const ventana = window.open('', 'ImprimirContrato', 'width=800,height=600');
                if (!ventana) {
                    alert('Por favor, permita ventanas emergentes para imprimir.');
                    return;
                }

                ventana.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${tituloVentanaContrato}</title>
    <style>${estilosImpresion}</style>
</head>
<body>${contenidoHTML}</body>
</html>`);
                ventana.document.close();

                // Dar tiempo para renderizar y luego imprimir
                setTimeout(function() {
                    ventana.print();
                }, 500);
            };
        </script>
    @endpush
@endsection


