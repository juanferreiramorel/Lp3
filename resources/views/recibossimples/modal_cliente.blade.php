<!-- Modal para crear nuevo cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog" aria-labelledby="modalClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteLabel">
                    <i class="fas fa-user-plus"></i> Nuevo Cliente
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="formNuevoCliente">
                @csrf

                {{-- The document comes first because it is the key that fills in
                     the rest: it is looked up against the DNIT registry, and it
                     is also what tells us the person is already a client. --}}
                <div class="cliente-lookup mb-4">
                    <label for="modal_clie_ci" class="d-block">CI / RUC <span class="text-danger" aria-hidden="true">*</span></label>
                    <div class="d-flex flex-column flex-sm-row" style="gap: 0.5rem;">
                        <input type="text" class="form-control" id="modal_clie_ci" name="clie_ci"
                               inputmode="numeric" autocomplete="off" autofocus
                               placeholder="1234567 o 80012345-1"
                               aria-describedby="lookup-ayuda lookup-estado">
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                id="btnBuscarDocumento">
                            <i class="fas fa-search mr-2" aria-hidden="true"></i>
                            <span>Buscar</span>
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" id="lookup-ayuda">
                        Buscamos el nombre en el padrón de la DNIT. Si no aparece, cargalo a mano.
                    </small>
                    <small class="text-danger d-none" id="error_clie_ci"></small>
                    <div class="alert mt-2 mb-0 py-2 px-3 d-none" id="lookup-estado" role="status"></div>
                </div>

                <hr>

                <div class="row">
                        <!-- Nombre Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_nombre">Nombre: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_clie_nombre" name="clie_nombre">
                            <small class="text-danger d-none" id="error_clie_nombre"></small>
                        </div>

                        <!-- Apellido Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_apellido">Apellido: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_clie_apellido" name="clie_apellido">
                            <small class="text-danger d-none" id="error_clie_apellido"></small>
                        </div>

                        <!-- Fecha Nacimiento Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_fecha_nac">Fecha de Nacimiento: <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_clie_fecha_nac" name="clie_fecha_nac">
                            <small class="text-danger d-none" id="error_clie_fecha_nac"></small>
                        </div>

                        <!-- Teléfono Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_telefono">Teléfono:</label>
                            <input type="text" class="form-control" id="modal_clie_telefono" name="clie_telefono">
                            <small class="text-danger d-none" id="error_clie_telefono"></small>
                        </div>

                        <!-- Dirección Field -->
                        <div class="form-group col-md-12 col-12">
                            <label for="modal_clie_direccion">Dirección:</label>
                            <input type="text" class="form-control" id="modal_clie_direccion" name="clie_direccion">
                            <small class="text-danger d-none" id="error_clie_direccion"></small>
                        </div>

                        <!-- Departamento Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_id_departamento">Departamento: <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="modal_id_departamento" name="id_departamento">
                                <option value="">Seleccionar Departamento...</option>
                                @if(isset($departamentos))
                                    @foreach($departamentos as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-danger d-none" id="error_id_departamento"></small>
                        </div>

                        <!-- Ciudad Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_id_ciudad">Ciudad: <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="modal_id_ciudad" name="id_ciudad">
                                <option value="">Seleccionar Ciudad...</option>
                            </select>
                            <small class="text-danger d-none" id="error_id_ciudad"></small>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media (max-width: 576px) {
        #modalCliente .modal-dialog {
            max-width: 100%;
            margin: 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado!');
        return;
    }

    jQuery(document).ready(function($) {
    // Evitar inicialización múltiple
    if (window.modalClienteInitialized) {
        return;
    }
    window.modalClienteInitialized = true;

    // ---- Consulta del documento contra el padrón de la DNIT ----------------
    (function configurarBuscadorDocumento() {
        const $documento = $('#modal_clie_ci');
        const $boton = $('#btnBuscarDocumento');
        const $estado = $('#lookup-estado');
        const url = @json(route('clientes.consultar.documento'));
        let enCurso = null;
        let ultimaConsulta = '';

        function mostrarEstado(tipo, html) {
            $estado
                .removeClass('d-none alert-success alert-warning alert-info')
                .addClass(tipo)
                .html(html);
        }

        function limpiarEstado() {
            $estado.addClass('d-none').empty();
        }

        function cargando(activo) {
            $boton.prop('disabled', activo);
            $boton.find('i')
                .toggleClass('fa-search', !activo)
                .toggleClass('fa-spinner fa-spin', activo);
            $boton.find('span').text(activo ? 'Buscando...' : 'Buscar');
        }

        function buscar() {
            const documento = $.trim($documento.val());

            if (documento === '') {
                mostrarEstado('alert-warning', 'Escribí un CI o RUC para buscar.');
                $documento.trigger('focus');
                return;
            }

            // Evita repetir la misma consulta cuando el campo no cambió.
            if (documento === ultimaConsulta && enCurso === null) {
                return;
            }

            if (enCurso) { enCurso.abort(); }

            ultimaConsulta = documento;
            cargando(true);
            mostrarEstado('alert-info', 'Consultando el padrón...');

            enCurso = $.getJSON(url, { documento: documento })
                .done(function(respuesta) {
                    if (respuesta.estado === 'encontrado') {
                        const datos = respuesta.datos || {};

                        $('#modal_clie_nombre').val(datos.nombre || '');
                        $('#modal_clie_apellido').val(datos.apellido || '');
                        if (datos.documento) { $documento.val(datos.documento); }
                        if (datos.fecha_nacimiento) {
                            $('#modal_clie_fecha_nac').val(datos.fecha_nacimiento);
                        }

                        const nombre = $.trim((datos.nombre || '') + ' ' + (datos.apellido || ''));
                        let mensaje = 'Encontrado: <strong></strong>.';
                        mostrarEstado('alert-success', mensaje);
                        $estado.find('strong').text(nombre);

                        if (datos.tipo === 'empresa') {
                            $estado.append(' Es una empresa, así que el apellido queda vacío.');
                        }
                        if (!datos.fecha_nacimiento) {
                            $estado.append(' Falta la fecha de nacimiento: cargala a mano.');
                        }

                        $('#modal_clie_telefono').trigger('focus');
                        return;
                    }

                    if (respuesta.estado === 'ya_registrado') {
                        const cliente = respuesta.cliente || {};
                        mostrarEstado('alert-warning', 'Ese documento ya es cliente: <strong></strong>. Cerrá esta ventana y buscalo en la lista.');
                        $estado.find('strong').text(cliente.nombre || '');
                        return;
                    }

                    mostrarEstado('alert-warning', respuesta.message || 'No encontramos ese documento. Cargá los datos a mano.');
                    $('#modal_clie_nombre').trigger('focus');
                })
                .fail(function(xhr, estadoTexto) {
                    if (estadoTexto === 'abort') { return; }

                    ultimaConsulta = '';
                    const respuesta = xhr.responseJSON || {};
                    mostrarEstado(
                        'alert-warning',
                        respuesta.message || 'No pudimos consultar el padrón. Cargá los datos a mano.'
                    );
                })
                .always(function() {
                    enCurso = null;
                    cargando(false);
                });
        }

        $boton.on('click', buscar);

        // Enter busca en lugar de enviar el formulario.
        $documento.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscar();
            }
        });

        $documento.on('blur', function() {
            if ($.trim($(this).val()) !== '') { buscar(); }
        });

        $documento.on('input', limpiarEstado);

        $('#modalCliente').on('hidden.bs.modal', function() {
            limpiarEstado();
            ultimaConsulta = '';
        });
    })();

    // Verificar que los elementos existen
    const $modal = $('#modalCliente');
    const $formContainer = $('#formNuevoCliente');
    const $departamentoSelect = $('#modal_id_departamento');
    const $ciudadSelect = $('#modal_id_ciudad');

    if ($modal.length === 0) {
        console.error('Modal de cliente no encontrado');
        return;
    }

    const departamentosUrl = '{{ route('departamentos.listadoSimple') }}';
    const ciudadesUrlTemplate = '{{ route('ciudades.porDepartamento', ['id_departamento' => '__ID__']) }}';
    let departamentosCargados = false;

    // Initialize Select2 for modal selects
    function initSelect2() {
        if (typeof $.fn.select2 !== 'undefined') {
            try {
                // Destruir select2 si ya existe
                if ($departamentoSelect.hasClass('select2-hidden-accessible')) {
                    $departamentoSelect.select2('destroy');
                }
                if ($ciudadSelect.hasClass('select2-hidden-accessible')) {
                    $ciudadSelect.select2('destroy');
                }

                $departamentoSelect.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: 'Seleccionar Departamento...',
                    allowClear: false
                });

                $ciudadSelect.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: 'Seleccionar Ciudad...',
                    allowClear: false
                });
            } catch(e) {
                console.error('Error al inicializar Select2:', e);
            }
        }
    }

    const resetSelect2ErrorState = (element) => {
        if (element.hasClass('select2-hidden-accessible')) {
            element.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        }
    };

    const applySelect2ErrorState = (element) => {
        if (element.hasClass('select2-hidden-accessible')) {
            element.next('.select2-container').find('.select2-selection').addClass('is-invalid');
        }
    };

    const clearModalErrors = () => {
        $modal.find('.text-danger').addClass('d-none').text('');
        $modal.find('.form-control').removeClass('is-invalid');
        resetSelect2ErrorState($departamentoSelect);
        resetSelect2ErrorState($ciudadSelect);
    };

    const poblarDepartamentos = (data) => {
        // Destruir Select2 temporalmente
        if ($departamentoSelect.hasClass('select2-hidden-accessible')) {
            $departamentoSelect.select2('destroy');
        }

        // Poblar opciones
        $departamentoSelect.empty().append('<option value="">Seleccionar Departamento...</option>');
        $.each(data, function(id, nombre) {
            $departamentoSelect.append(new Option(nombre, id, false, false));
        });

        // Reinicializar Select2
        $departamentoSelect.select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $modal,
            placeholder: 'Seleccionar Departamento...',
            allowClear: false
        });
    };

    const cargarDepartamentos = () => {
        if (departamentosCargados) {
            return $.Deferred().resolve().promise();
        }

        return $.ajax({
            url: departamentosUrl,
            type: 'GET'
        }).done(function(data) {
            poblarDepartamentos(data);
            departamentosCargados = true;
        }).fail(function(xhr) {
            console.error('Error al cargar departamentos:', xhr.responseText);
            $departamentoSelect.empty().append('<option value="">Error al cargar departamentos</option>');
        });
    };

    const poblarCiudades = (data) => {
        // Destruir Select2 temporalmente
        if ($ciudadSelect.hasClass('select2-hidden-accessible')) {
            $ciudadSelect.select2('destroy');
        }

        // Poblar opciones
        $ciudadSelect.empty().append('<option value="">Seleccionar Ciudad...</option>');
        $.each(data, function(id, nombre) {
            $ciudadSelect.append(new Option(nombre, id, false, false));
        });

        // Reinicializar Select2
        $ciudadSelect.select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $modal,
            placeholder: 'Seleccionar Ciudad...',
            allowClear: false
        });
    };

    const cargarCiudades = (departamentoId) => {
        if (!departamentoId) {
            poblarCiudades({});
            return;
        }

        const url = ciudadesUrlTemplate.replace('__ID__', departamentoId);

        $.ajax({
            url: url,
            type: 'GET'
        }).done(function(data) {
            poblarCiudades(data);
        }).fail(function(xhr) {
            console.error('Error al cargar ciudades:', xhr.responseText);
            poblarCiudades({});
        });
    };

    // Escuchar cambios en departamento
    $departamentoSelect.on('change', function() {
        cargarCiudades($(this).val());
    });

    // Cuando el modal se muestre completamente
    $modal.on('shown.bs.modal', function() {
        initSelect2();
        cargarDepartamentos();
    });

    // Limpiar formulario al cerrar modal
    $modal.on('hidden.bs.modal', function() {
        // Limpiar todos los campos manualmente
        $('#modal_clie_nombre').val('');
        $('#modal_clie_apellido').val('');
        $('#modal_clie_ci').val('');
        $('#modal_clie_fecha_nac').val('');
        $('#modal_clie_telefono').val('');
        $('#modal_clie_direccion').val('');

        clearModalErrors();
        $departamentoSelect.val('');
        if ($departamentoSelect.hasClass('select2-hidden-accessible')) {
            $departamentoSelect.trigger('change.select2');
        } else {
            $departamentoSelect.trigger('change');
        }
        poblarCiudades({});
    });

    // Guardar cliente - usando delegación de eventos
    $(document).on('click', '#btnGuardarCliente', function(e) {
        e.preventDefault();

        const btn = $(this);
        const $modalElement = $('#modalCliente');
        const $formContainer = $('#formNuevoCliente');

        // Limpiar errores previos
        clearModalErrors();

        // Deshabilitar botón
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        // Recopilar datos manualmente desde los inputs dentro del contenedor
        const formData = {
            clie_nombre: $('#modal_clie_nombre').val(),
            clie_apellido: $('#modal_clie_apellido').val(),
            clie_ci: $('#modal_clie_ci').val(),
            clie_fecha_nac: $('#modal_clie_fecha_nac').val(),
            clie_telefono: $('#modal_clie_telefono').val(),
            clie_direccion: $('#modal_clie_direccion').val(),
            id_departamento: $('#modal_id_departamento').val(),
            id_ciudad: $('#modal_id_ciudad').val()
        };

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!csrfToken) {
            console.error('CSRF Token no encontrado en la página');
            alert('Error: Token de seguridad no encontrado. Recargue la página.');
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cliente');
            return;
        }

        $.ajax({
            url: '{{ route("clientes.store.ajax") }}',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            success: function(response) {
                if (response.success) {
                    // Cerrar modal y limpiar backdrop
                    $modalElement.modal('hide');

                    // Forzar la eliminación del backdrop y restaurar el scroll
                    setTimeout(function() {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }, 100);

                    // Agregar nuevo cliente al select principal
                    const nuevoCliente = response.cliente;
                    const option = new Option(nuevoCliente.nombre_completo, nuevoCliente.id_cliente, true, true);
                    const $clienteSelect = $('#id_cliente');
                    if ($clienteSelect.find('option[value="' + nuevoCliente.id_cliente + '"]').length === 0) {
                        $clienteSelect.append(option);
                    }
                    $clienteSelect.val(nuevoCliente.id_cliente).trigger('change');

                    // Mostrar mensaje de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Cliente creado correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Cliente creado correctamente');
                    }
                }
            },
            error: function(xhr) {
                console.error('Error al guardar cliente:', xhr);

                if (xhr.status === 422) {
                    // Errores de validación
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const errorElement = $('#error_' + field);
                        const inputElement = $('#modal_' + field);

                        errorElement.removeClass('d-none').text(messages[0]);
                        inputElement.addClass('is-invalid');
                        applySelect2ErrorState(inputElement);
                    });
                } else {
                    let errorMsg = 'Error al crear el cliente. Por favor, intente nuevamente.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMsg = 'Error: ' + xhr.status + ' - ' + xhr.statusText;
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    } else {
                        alert(errorMsg);
                    }
                }
            },
            complete: function() {
                // Rehabilitar botón
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cliente');
            }
        });
    });
    }); // Cierre de jQuery(document).ready
})(); // Cierre de IIFE

</script>
@endpush
