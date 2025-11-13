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
            <div class="modal-body">
                <form id="formNuevoCliente">
                    @csrf
                    <div class="row">
                        <!-- Nombre Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_nombre">Nombre: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_clie_nombre" name="clie_nombre" required>
                            <small class="text-danger d-none" id="error_clie_nombre"></small>
                        </div>

                        <!-- Apellido Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_apellido">Apellido: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_clie_apellido" name="clie_apellido" required>
                            <small class="text-danger d-none" id="error_clie_apellido"></small>
                        </div>

                        <!-- CI Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_ci">CI/RUC: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_clie_ci" name="clie_ci" required>
                            <small class="text-danger d-none" id="error_clie_ci"></small>
                        </div>

                        <!-- Fecha Nacimiento Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_fecha_nac">Fecha de Nacimiento: <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_clie_fecha_nac" name="clie_fecha_nac" required>
                            <small class="text-danger d-none" id="error_clie_fecha_nac"></small>
                        </div>

                        <!-- Teléfono Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_telefono">Teléfono:</label>
                            <input type="text" class="form-control" id="modal_clie_telefono" name="clie_telefono">
                            <small class="text-danger d-none" id="error_clie_telefono"></small>
                        </div>

                        <!-- Dirección Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_clie_direccion">Dirección:</label>
                            <input type="text" class="form-control" id="modal_clie_direccion" name="clie_direccion">
                            <small class="text-danger d-none" id="error_clie_direccion"></small>
                        </div>

                        <!-- Departamento Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_id_departamento">Departamento: <span class="text-danger">*</span></label>
                            <select class="form-control select2-modal" id="modal_id_departamento" name="id_departamento" required>
                                <option value="">Seleccionar Departamento...</option>
                                @foreach($departamentos as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none" id="error_id_departamento"></small>
                        </div>

                        <!-- Ciudad Field -->
                        <div class="form-group col-md-6 col-12">
                            <label for="modal_id_ciudad">Ciudad: <span class="text-danger">*</span></label>
                            <select class="form-control select2-modal" id="modal_id_ciudad" name="id_ciudad" required>
                                <option value="">Seleccionar Ciudad...</option>
                            </select>
                            <small class="text-danger d-none" id="error_id_ciudad"></small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">
                    <i class="fas fa-save"></i> Guardar Cliente
                </button>
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

$(document).ready(function() {
    const $modal = $('#modalCliente');
    const $modalForm = $('#formNuevoCliente');
    const $modalSelects = $('.select2-modal');
    const $departamentoSelect = $('#modal_id_departamento');
    const $ciudadSelect = $('#modal_id_ciudad');
    const departamentosUrl = '{{ route('departamentos.listadoSimple') }}';
    const ciudadesUrlTemplate = '{{ route('ciudades.porDepartamento', ['id_departamento' => '__ID__']) }}';
    let departamentosCargados = $departamentoSelect.find('option').length > 1;

    // Initialize Select2 for modal selects
    $modalSelects.select2({
        theme: 'bootstrap4',
        width: '100%',
        dropdownParent: $modal
    });

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
        $modalSelects.each(function() {
            resetSelect2ErrorState($(this));
        });
    };

    const poblarDepartamentos = (data) => {
        $departamentoSelect.empty().append('<option value="">Seleccionar Departamento...</option>');
        $.each(data, function(id, nombre) {
            $departamentoSelect.append(new Option(nombre, id, false, false));
        });
        $departamentoSelect.trigger('change.select2');
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
        }).fail(function() {
            $departamentoSelect.empty().append('<option value="">Error al cargar departamentos</option>');
            $departamentoSelect.trigger('change.select2');
        });
    };

    const poblarCiudades = (data) => {
        $ciudadSelect.empty().append('<option value="">Seleccionar Ciudad...</option>');
        $.each(data, function(id, nombre) {
            $ciudadSelect.append(new Option(nombre, id, false, false));
        });
        $ciudadSelect.trigger('change.select2');
    };

    const cargarCiudades = (departamentoId) => {
        if (!departamentoId) {
            poblarCiudades({});
            return;
        }

        $ciudadSelect.empty().append('<option value="">Cargando...</option>');
        $ciudadSelect.trigger('change.select2');

        const url = ciudadesUrlTemplate.replace('__ID__', departamentoId);

        $.ajax({
            url: url,
            type: 'GET'
        }).done(function(data) {
            poblarCiudades(data);
        }).fail(function() {
            $ciudadSelect.empty().append('<option value="">Error al cargar ciudades</option>');
            $ciudadSelect.trigger('change.select2');
        });
    };

    $departamentoSelect.on('change', function() {
        cargarCiudades($(this).val());
    });

    $modal.on('shown.bs.modal', function() {
        cargarDepartamentos();
    });

    // Limpiar formulario al cerrar modal
    $modal.on('hidden.bs.modal', function() {
        $modalForm[0].reset();
        clearModalErrors();
        $departamentoSelect.val('').trigger('change.select2');
        poblarCiudades({});
    });

    // Guardar cliente
    $('#btnGuardarCliente').on('click', function() {
        const btn = $(this);
        const form = $modalForm;

        // Limpiar errores previos
        clearModalErrors();

        // Deshabilitar botón
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("clientes.store.ajax") }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Cerrar modal
                    $modal.modal('hide');

                    // Agregar nuevo cliente al select principal
                    const nuevoCliente = response.cliente;
                    const option = new Option(nuevoCliente.nombre_completo, nuevoCliente.id_cliente, true, true);
                    const $clienteSelect = $('#id_cliente');
                    if ($clienteSelect.find('option[value="' + nuevoCliente.id_cliente + '"]').length === 0) {
                        $clienteSelect.append(option);
                    }
                    $clienteSelect.val(nuevoCliente.id_cliente).trigger('change');

                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Cliente creado correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al crear el cliente. Por favor, intente nuevamente.'
                    });
                }
            },
            complete: function() {
                // Rehabilitar botón
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cliente');
            }
        });
    });
});

</script>
@endpush
