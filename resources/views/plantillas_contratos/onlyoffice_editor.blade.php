@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-file-word text-primary"></i>
                        Editor de Contrato
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('plantillas_contratos.index') }}">Contratos</a></li>
                        <li class="breadcrumb-item active">Editor</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <!-- Información del contrato -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-file-contract"></i>
                        <strong>{{ $contrato->plantilla->nombre_plantilla ?? 'Contrato' }}</strong> - 
                        {{ $contrato->clie_apellido ?? '' }}, {{ $contrato->clie_nombre ?? '' }}
                    </span>
                    <div>
                        <a href="{{ route('plantillas_contratos.ver_contrato', $contrato->id_contrato_generado) }}" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor OnlyOffice -->
        <div class="card">
            <div class="card-body p-0">
                <div id="onlyoffice-editor-container">
                    <!-- Mensaje de carga -->
                    <div id="editor-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <h4 class="mt-3 text-muted">Iniciando editor de documentos...</h4>
                        <p class="text-muted">
                            <i class="fas fa-file-word"></i> 
                            Conectando con OnlyOffice Document Server
                        </p>
                    </div>

                    <!-- Error de conexión -->
                    <div id="editor-error" class="text-center py-5" style="display: none;">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 48px;"></i>
                        <h4 class="mt-3 text-danger">No se pudo conectar con el editor</h4>
                        <p class="text-muted" id="error-message">
                            Verifique que OnlyOffice Document Server esté funcionando.
                        </p>
                        <button class="btn btn-primary" onclick="initEditor()">
                            <i class="fas fa-sync"></i> Reintentar
                        </button>
                        <a href="{{ route('plantillas_contratos.editar_contrato', $contrato->id_contrato_generado) }}" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Usar editor clásico
                        </a>
                    </div>

                    <!-- Placeholder del editor -->
                    <div id="onlyoffice-editor" style="height: 85vh; display: none;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_css')
<style>
    .content-wrapper {
        overflow: hidden;
    }
    
    #onlyoffice-editor-container {
        min-height: 85vh;
        background: #f4f4f4;
    }
    
    #onlyoffice-editor {
        width: 100%;
        height: 85vh;
    }
    
    /* Ocultar scrollbar del body cuando el editor está activo */
    body.editor-active {
        overflow: hidden;
    }
</style>
@endpush

@push('page_scripts')
<script>
    const contratoId = @json($contrato->id_contrato_generado);
    const onlyofficeServerUrl = @json(config('onlyoffice.document_server_url'));
    let docEditor = null;

    async function checkServerHealth() {
        try {
            const response = await fetch('/onlyoffice/health');
            const data = await response.json();
            return data.status === 'ok';
        } catch (e) {
            console.error('Error checking OnlyOffice health:', e);
            return false;
        }
    }

    async function getEditorConfig() {
        try {
            const response = await fetch(`/onlyoffice/config/${contratoId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Error al obtener configuración del editor');
            }
            
            return await response.json();
        } catch (e) {
            console.error('Error getting editor config:', e);
            throw e;
        }
    }

    async function initEditor() {
        const loadingEl = document.getElementById('editor-loading');
        const errorEl = document.getElementById('editor-error');
        const editorEl = document.getElementById('onlyoffice-editor');
        const errorMsgEl = document.getElementById('error-message');

        // Mostrar loading
        loadingEl.style.display = 'block';
        errorEl.style.display = 'none';
        editorEl.style.display = 'none';

        try {
            // Verificar que el servidor esté disponible
            const isHealthy = await checkServerHealth();
            
            if (!isHealthy) {
                throw new Error('OnlyOffice Document Server no está disponible. Asegúrese de que el contenedor esté corriendo.');
            }

            // Obtener configuración del editor
            const { config, apiUrl } = await getEditorConfig();

            // Cargar el script de OnlyOffice
            await loadOnlyOfficeScript(apiUrl);

            // Ocultar loading
            loadingEl.style.display = 'none';
            editorEl.style.display = 'block';

            // Inicializar el editor
            docEditor = new DocsAPI.DocEditor('onlyoffice-editor', config);

            // Añadir clase al body
            document.body.classList.add('editor-active');

        } catch (error) {
            console.error('Error initializing OnlyOffice editor:', error);
            
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            errorMsgEl.textContent = error.message || 'Error desconocido';
        }
    }

    function loadOnlyOfficeScript(apiUrl) {
        return new Promise((resolve, reject) => {
            // Verificar si ya está cargado
            if (window.DocsAPI) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = apiUrl;
            script.async = true;
            
            script.onload = () => {
                console.log('OnlyOffice API cargado');
                resolve();
            };
            
            script.onerror = () => {
                reject(new Error('No se pudo cargar el script de OnlyOffice'));
            };

            document.head.appendChild(script);
        });
    }

    // Iniciar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initEditor();
    });

    // Limpiar al salir
    window.addEventListener('beforeunload', function() {
        if (docEditor) {
            docEditor.destroyEditor();
        }
    });
</script>
@endpush
