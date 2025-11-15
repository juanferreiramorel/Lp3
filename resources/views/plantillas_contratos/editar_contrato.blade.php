@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-edit"></i>
                        Editar Contrato Generado
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('plantillas_contratos.ver_contrato', $contrato->id_contrato_generado) }}">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')

        <div class="row">
            <!-- Información del Contrato -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Contrato</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Plantilla:</dt>
                            <dd class="col-sm-7">{{ $contrato->nombre_plantilla }}</dd>

                            <dt class="col-sm-5">Cliente:</dt>
                            <dd class="col-sm-7">{{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</dd>

                            <dt class="col-sm-5">CI:</dt>
                            <dd class="col-sm-7">{{ $contrato->clie_ci }}</dd>

                            <dt class="col-sm-5">Generado:</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</dd>

                            <dt class="col-sm-5">Por:</dt>
                            <dd class="col-sm-7">{{ $contrato->usuario_nombre ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Atención:</strong> Los cambios realizados sobrescribirán el contenido actual del contrato.
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-edit"></i> Editar Contenido del Contrato</h3>
                    </div>

                    {!! Form::open(['route' => ['plantillas_contratos.actualizar_contrato', $contrato->id_contrato_generado], 'method' => 'PUT']) !!}

                    <div class="card-body">
                        <!-- Contenido del Contrato -->
                        <div class="form-group">
                            {!! Form::label('contenido_generado', 'Contenido del Contrato:') !!}
                            {!! Form::textarea('contenido_generado', $contrato->contenido_generado, [
                                'class' => 'form-control',
                                'id' => 'contenido_generado',
                                'style' => 'display:none;'
                            ]) !!}

                            <!-- Contenedor del editor con altura controlada -->
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

                                <!-- Editor -->
                                <div id="editor-contrato-wrapper" class="contrato-editor d-none">
                                    <div id="editor-contrato"></div>
                                </div>
                            </div>

                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-edit"></i>
                                Modifique el contenido según sea necesario. Puede copiar y pegar desde Word.
                            </small>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            {!! Form::label('observaciones', 'Observaciones:') !!}
                            {!! Form::textarea('observaciones', $contrato->observaciones, [
                                'class' => 'form-control',
                                'rows' => 3,
                                'placeholder' => 'Agregue o modifique las observaciones'
                            ]) !!}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                {!! Form::button('<i class="fas fa-save"></i> Actualizar Contrato', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-primary btn-lg btn-block'
                                ]) !!}
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('plantillas_contratos.ver_contrato', $contrato->id_contrato_generado) }}"
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
            .editor-wrap {
                max-height: 600px;
                position: relative;
                overflow: visible;
            }

            .editor-loading {
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: #f9fafb;
            }

            .contrato-editor .ck.ck-editor {
                background: white;
                border-radius: 6px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                overflow: visible;
                margin-bottom: 30px;
                position: relative;
                z-index: 1;
            }

            .contrato-editor .ck.ck-toolbar {
                border: none !important;
                border-bottom: 2px solid #e5e7eb !important;
                background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%) !important;
                padding: 12px 15px !important;
                border-radius: 6px 6px 0 0 !important;
            }

            .contrato-editor .ck-editor__main > .ck-editor__editable {
                border: none !important;
                font-family: 'Times New Roman', Times, serif !important;
                font-size: 12pt !important;
                min-height: 400px !important;
                max-height: 500px !important;
                overflow-y: auto !important;
                padding: 2cm 1.5cm !important;
                line-height: 1.6 !important;
                background: white !important;
            }

            .contrato-editor .ck.ck-editor__main {
                border-radius: 0 0 6px 6px;
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
            let editorContrato;

            document.addEventListener('DOMContentLoaded', function() {
                if (!window.CKEDITOR_CONFIG || !window.CKEDITOR_CONTRATO) {
                    console.error('CKEditor no está disponible en editar_contrato');
                    return;
                }

                const editorElement = document.getElementById('editor-contrato');
                const contenidoField = document.getElementById('contenido_generado');
                const loading = document.getElementById('editor-loading');
                const wrapper = document.getElementById('editor-contrato-wrapper');

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
                        editorContrato = editor;

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

                        console.log('✅ CKEditor 5 inicializado en editar_contrato');
                    })
                    .catch(error => {
                        console.error('❌ Error al inicializar CKEditor en editar_contrato:', error);
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

