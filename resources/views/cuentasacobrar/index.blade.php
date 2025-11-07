@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Cuentas a Cobrar</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix">
           @includeIf('layouts.buscador', ['url' => url()->current()])
        </div>

        <!-- agregar la clase tabla-container para mostrar los valores filtrados de table-->
        <div class="card tabla-container">
            @include('cuentasacobrar.table')
        </div>
    </div>

    <!-- Modal para mostrar el detalle de cuentas a cobrar -->
    <div class="modal fade" id="modalDetalleCuentas" tabindex="-1" role="dialog" aria-labelledby="modalDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="modalDetalleLabel">Detalle de Cuentas a Cobrar - Factura <span id="modal-factura-nro"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-detalle" class="text-center" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando detalle...</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabla-detalle-cuentas">
                            <thead>
                                <tr>
                                    <th>N° Cuenta</th>
                                    <th>N° Cuota</th>
                                    <th>Importe</th>
                                    <th>Saldo</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Pagos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalle-cuentas">
                                <!-- Se llenará dinámicamente con AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cobrar Cuenta -->
    <div class="modal fade" id="modalCobrarCuenta" tabindex="-1" role="dialog" aria-labelledby="modalCobrarLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="modalCobrarLabel">Cobrar Cuota</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form-cobrar-cuenta">
                    <div class="modal-body">
                        <input type="hidden" id="cobro-id-cta" name="id_cta">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>N° Cuenta:</label>
                                    <input type="text" class="form-control" id="cobro-nro-cuenta" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>N° Cuota:</label>
                                    <input type="text" class="form-control" id="cobro-nro-cuota" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Importe Total:</label>
                                    <input type="text" class="form-control" id="cobro-importe-total" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Saldo Pendiente: <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-danger font-weight-bold" id="cobro-saldo-pendiente-display" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vencimiento:</label>
                                    <input type="text" class="form-control" id="cobro-vencimiento" readonly>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Fecha de Cobro: <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="cobro-fecha" name="fecha_cobro" required>
                        </div>

                        <div class="form-group">
                            <label>Método de Pago: <span class="text-danger">*</span></label>
                            <select class="form-control" id="cobro-metodo-pago" name="id_metodo_pago" required>
                                <option value="">Seleccione un método de pago</option>
                                @if(isset($metodos_pago))
                                    @foreach($metodos_pago as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Monto a Pagar: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-right" id="cobro-monto-pagar" name="monto"
                                   placeholder="0" onkeyup="formatCobro(this)" required>
                            <small class="form-text text-muted">Puede ingresar un monto parcial o total</small>
                        </div>

                        <div class="form-group">
                            <label>N° Voucher:</label>
                            <input type="text" class="form-control" id="cobro-nro-voucher" name="nro_voucher"
                                   placeholder="Número de comprobante (opcional)">
                        </div>

                        <div class="alert alert-info" id="cobro-saldo-info" style="display: none;">
                            <strong>Saldo Pendiente:</strong> <span id="cobro-saldo-pendiente">0</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btn-confirmar-cobro">
                            <i class="fas fa-check"></i> Confirmar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Historial de Pagos -->
    <div class="modal fade" id="modalHistorialPagos" tabindex="-1" role="dialog" aria-labelledby="modalHistorialLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="modalHistorialLabel">
                        Historial de Pagos - Cuenta N° <span id="historial-nro-cuenta"></span> - Cuota <span id="historial-nro-cuota"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-historial" class="text-center" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando historial...</p>
                    </div>
                    <div id="sin-pagos-mensaje" class="alert alert-info" style="display: none;">
                        <i class="fas fa-info-circle"></i> No hay pagos registrados para esta cuota.
                    </div>
                    <div class="table-responsive" id="tabla-historial-container">
                        <table class="table table-bordered table-striped" id="tabla-historial-pagos">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Método Pago</th>
                                    <th>N° Voucher</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-historial-pagos">
                                <!-- Se llenará dinámicamente con AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Función para actualizar el detalle de cuentas
        function actualizarDetalleCuentas(idVenta) {
            $.ajax({
                url: '{{ url("cuentasacobrar") }}/' + idVenta + '/detalle',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    $.each(data, function(index, item) {
                        var fecha = new Date(item.vencimiento);
                        var fechaFormateada = ('0' + fecha.getDate()).slice(-2) + '/' +
                            ('0' + (fecha.getMonth() + 1)).slice(-2) + '/' +
                            fecha.getFullYear();

                        var importeFormateado = Number(item.importe).toLocaleString('es-PY');
                        var saldoFormateado = Number(item.saldo).toLocaleString('es-PY');

                        var estadoBadge = '';
                        var estadoMostrar = item.estado_actual || item.estado;
                        var botonPagar = '';

                        if (estadoMostrar === 'PENDIENTE') {
                            estadoBadge = '<span class="badge badge-warning">PENDIENTE</span>';
                            botonPagar = '<button class="btn btn-sm btn-success btn-cobrar-cuenta" ' +
                                'data-id-cta="' + item.nro_cuenta + '" ' +
                                'data-nro-cuota="' + item.nro_cuota + '" ' +
                                'data-importe="' + item.importe + '" ' +
                                'data-saldo="' + item.saldo + '" ' +
                                'data-vencimiento="' + fechaFormateada + '">' +
                                '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                        } else if (estadoMostrar === 'VENCIDA') {
                            estadoBadge = '<span class="badge badge-danger">VENCIDA</span>';
                            botonPagar = '<button class="btn btn-sm btn-warning btn-cobrar-cuenta" ' +
                                'data-id-cta="' + item.nro_cuenta + '" ' +
                                'data-nro-cuota="' + item.nro_cuota + '" ' +
                                'data-importe="' + item.importe + '" ' +
                                'data-saldo="' + item.saldo + '" ' +
                                'data-vencimiento="' + fechaFormateada + '">' +
                                '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                        } else if (estadoMostrar === 'PARCIAL') {
                            estadoBadge = '<span class="badge badge-info">PARCIAL</span>';
                            botonPagar = '<button class="btn btn-sm btn-primary btn-cobrar-cuenta" ' +
                                'data-id-cta="' + item.nro_cuenta + '" ' +
                                'data-nro-cuota="' + item.nro_cuota + '" ' +
                                'data-importe="' + item.importe + '" ' +
                                'data-saldo="' + item.saldo + '" ' +
                                'data-vencimiento="' + fechaFormateada + '">' +
                                '<i class="fas fa-dollar-sign"></i> Pagar Saldo</button>';
                        } else if (estadoMostrar === 'PAGADO') {
                            estadoBadge = '<span class="badge badge-success">PAGADO</span>';
                            botonPagar = '<span class="text-muted"><i class="fas fa-check"></i> Pagado</span>';
                        } else {
                            estadoBadge = '<span class="badge badge-secondary">' + estadoMostrar + '</span>';
                        }

                        var botonHistorial = '<button class="btn btn-sm btn-info btn-ver-historial" ' +
                            'data-id-cta="' + item.nro_cuenta + '" ' +
                            'data-nro-cuota="' + item.nro_cuota + '">' +
                            '<i class="fas fa-history"></i></button>';

                        html += '<tr>' +
                            '<td>' + item.nro_cuenta + '</td>' +
                            '<td>' + item.nro_cuota + '</td>' +
                            '<td>' + importeFormateado + '</td>' +
                            '<td class="' + (item.saldo > 0 ? 'text-danger font-weight-bold' : 'text-success') + '">' +
                                saldoFormateado +
                            '</td>' +
                            '<td>' + fechaFormateada + '</td>' +
                            '<td>' + estadoBadge + '</td>' +
                            '<td>' + botonHistorial + '</td>' +
                            '<td>' + botonPagar + '</td>' +
                            '</tr>';
                    });

                    $('#tbody-detalle-cuentas').html(html);
                }
            });
        }

        // Función para actualizar la tabla principal
        function actualizarTablaPrincipal() {
            $.ajax({
                url: window.location.href,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $('.tabla-container').html(response);
                }
            });
        }

        $(document).ready(function() {
            // limpiar filtros con jquery
            $('#btn-limpiar').click(function() {
                $('#sucursales').val('').trigger('change');
                $('#productos').val('').trigger('change');
                $('.buscar').val('');
                $('#form-busqueda').submit();
            });

            // Manejar clic en botón "Ver Detalle"
            $(document).on('click', '.btn-ver-detalle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idVenta = $(this).data('id-venta');
                var facturaNum = $(this).data('factura');

                // Actualizar el título del modal
                $('#modal-factura-nro').text(facturaNum);

                // Mostrar loading y limpiar tabla
                $('#loading-detalle').show();
                $('#tbody-detalle-cuentas').html('');

                // Abrir el modal
                $('#modalDetalleCuentas').modal('show');

                // Hacer petición AJAX para obtener el detalle
                $.ajax({
                    url: '{{ url("cuentasacobrar") }}/' + idVenta + '/detalle',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#loading-detalle').hide();

                        if (data.length === 0) {
                            $('#tbody-detalle-cuentas').html(
                                '<tr><td colspan="8" class="text-center">No hay cuentas a cobrar para esta factura</td></tr>'
                            );
                            return;
                        }

                        var html = '';
                        $.each(data, function(index, item) {
                            // Formatear fecha
                            var fecha = new Date(item.vencimiento);
                            var fechaFormateada = ('0' + fecha.getDate()).slice(-2) + '/' +
                                                ('0' + (fecha.getMonth() + 1)).slice(-2) + '/' +
                                                fecha.getFullYear();

                            // Formatear importe y saldo
                            var importeFormateado = Number(item.importe).toLocaleString('es-PY');
                            var saldoFormateado = Number(item.saldo).toLocaleString('es-PY');

                            // Determinar badge de estado
                            var estadoBadge = '';
                            var estadoMostrar = item.estado_actual || item.estado;
                            var botonPagar = '';

                            if (estadoMostrar === 'PENDIENTE') {
                                estadoBadge = '<span class="badge badge-warning">PENDIENTE</span>';
                                botonPagar = '<button class="btn btn-sm btn-success btn-cobrar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                            } else if (estadoMostrar === 'VENCIDA') {
                                estadoBadge = '<span class="badge badge-danger">VENCIDA</span>';
                                botonPagar = '<button class="btn btn-sm btn-warning btn-cobrar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                            } else if (estadoMostrar === 'PARCIAL') {
                                estadoBadge = '<span class="badge badge-info">PARCIAL</span>';
                                botonPagar = '<button class="btn btn-sm btn-primary btn-cobrar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar Saldo</button>';
                            } else if (estadoMostrar === 'PAGADO') {
                                estadoBadge = '<span class="badge badge-success">PAGADO</span>';
                                botonPagar = '<span class="text-muted"><i class="fas fa-check"></i> Pagado</span>';
                            } else {
                                estadoBadge = '<span class="badge badge-secondary">' + estadoMostrar + '</span>';
                            }

                            // Botón para ver historial de pagos
                            var botonHistorial = '<button class="btn btn-sm btn-info btn-ver-historial" ' +
                                'data-id-cta="' + item.nro_cuenta + '" ' +
                                'data-nro-cuota="' + item.nro_cuota + '">' +
                                '<i class="fas fa-history"></i></button>';

                            html += '<tr>' +
                                '<td>' + item.nro_cuenta + '</td>' +
                                '<td>' + item.nro_cuota + '</td>' +
                                '<td>' + importeFormateado + '</td>' +
                                '<td class="' + (item.saldo > 0 ? 'text-danger font-weight-bold' : 'text-success') + '">' +
                                    saldoFormateado +
                                '</td>' +
                                '<td>' + fechaFormateada + '</td>' +
                                '<td>' + estadoBadge + '</td>' +
                                '<td>' + botonHistorial + '</td>' +
                                '<td>' + botonPagar + '</td>' +
                                '</tr>';
                        });

                        $('#tbody-detalle-cuentas').html(html);
                    },
                    error: function(xhr, status, error) {
                        $('#loading-detalle').hide();
                        $('#tbody-detalle-cuentas').html(
                            '<tr><td colspan="8" class="text-center text-danger">Error al cargar el detalle: ' + error + '</td></tr>'
                        );
                    }
                });
            });

            // Manejar clic en botón "Pagar" de cada cuota
            $(document).on('click', '.btn-cobrar-cuenta', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idCta = $(this).data('id-cta');
                var nroCuota = $(this).data('nro-cuota');
                var importe = $(this).data('importe');
                var saldo = $(this).data('saldo');
                var vencimiento = $(this).data('vencimiento');

                // Llenar datos en el modal de cobro
                $('#cobro-id-cta').val(idCta);
                $('#cobro-nro-cuenta').val(idCta);
                $('#cobro-nro-cuota').val(nroCuota);
                $('#cobro-importe-total').val(Number(importe).toLocaleString('es-PY'));
                $('#cobro-saldo-pendiente-display').val(Number(saldo).toLocaleString('es-PY'));
                $('#cobro-vencimiento').val(vencimiento);

                // Establecer fecha actual
                var today = new Date();
                var fecha = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');
                $('#cobro-fecha').val(fecha);

                // Guardar saldo en un data attribute para cálculos
                $('#modalCobrarCuenta').data('saldo-actual', saldo);

                // Limpiar campos del formulario
                $('#cobro-metodo-pago').val('');
                $('#cobro-monto-pagar').val('');
                $('#cobro-nro-voucher').val('');
                $('#cobro-saldo-info').hide();

                // Abrir modal de cobro
                $('#modalCobrarCuenta').modal('show');
            });

            // Calcular saldo pendiente al cambiar el monto a pagar
            $('#cobro-monto-pagar').on('keyup change', function() {
                var saldoActual = $('#modalCobrarCuenta').data('saldo-actual') || 0;
                var montoPagar = $(this).val().replace(/\./g, '');

                saldoActual = parseFloat(saldoActual);
                montoPagar = parseFloat(montoPagar) || 0;

                if (montoPagar > 0 && montoPagar < saldoActual) {
                    var saldoPendiente = saldoActual - montoPagar;
                    $('#cobro-saldo-pendiente').text(Number(saldoPendiente).toLocaleString('es-PY'));
                    $('#cobro-saldo-info').show();
                } else {
                    $('#cobro-saldo-info').hide();
                }
            });

            // Manejar submit del formulario de cobro
            $('#form-cobrar-cuenta').on('submit', function(e) {
                e.preventDefault();

                var idCta = $('#cobro-id-cta').val();
                var formData = {
                    id_metodo_pago: $('#cobro-metodo-pago').val(),
                    monto: $('#cobro-monto-pagar').val(),
                    nro_voucher: $('#cobro-nro-voucher').val(),
                    fecha_cobro: $('#cobro-fecha').val(),
                    _token: '{{ csrf_token() }}'
                };

                // Validar campos requeridos
                if (!formData.fecha_cobro) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Debe ingresar la fecha de cobro',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                    return;
                }

                if (!formData.id_metodo_pago) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Debe seleccionar un método de pago',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                    return;
                }

                if (!formData.monto || formData.monto === '0') {
                    Swal.fire({
                        title: 'Error',
                        text: 'Debe ingresar un monto a pagar',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                    return;
                }

                // Deshabilitar botón durante el proceso
                $('#btn-confirmar-cobro').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                // Realizar petición AJAX
                $.ajax({
                    url: '{{ url("cuentasacobrar") }}/' + idCta + '/cobrar',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#btn-confirmar-cobro').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar Pago');

                        if (response.success) {
                            // Cerrar modal de cobro
                            $('#modalCobrarCuenta').modal('hide');

                            // Mostrar mensaje de éxito y recargar
                            Swal.fire({
                                title: 'Éxito',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'Ok'
                            }).then(function() {
                                // Recargar la página para ver los cambios
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#btn-confirmar-cobro').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar Pago');

                        var errorMessage = 'Error al procesar el pago';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });
                    }
                });
            });

            // Guardar id_venta cuando se abre el modal de detalle para poder recargar después
            $('#modalDetalleCuentas').on('show.bs.modal', function(e) {
                var button = $(e.relatedTarget);
                if (button.length) {
                    var idVenta = button.data('id-venta');
                    $(this).data('id-venta-actual', idVenta);
                }
            });

            // Manejar clic en botón "Ver Historial" de pagos
            $(document).on('click', '.btn-ver-historial', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idCta = $(this).data('id-cta');
                var nroCuota = $(this).data('nro-cuota');

                // Actualizar el título del modal
                $('#historial-nro-cuenta').text(idCta);
                $('#historial-nro-cuota').text(nroCuota);

                // Mostrar loading y limpiar tabla
                $('#loading-historial').show();
                $('#sin-pagos-mensaje').hide();
                $('#tabla-historial-container').hide();
                $('#tbody-historial-pagos').html('');

                // Guardar id_cta para recargar después de anular
                $('#modalHistorialPagos').data('id-cta-actual', idCta);

                // Abrir el modal
                $('#modalHistorialPagos').modal('show');

                // Hacer petición AJAX para obtener el historial
                $.ajax({
                    url: '{{ url("cuentasacobrar/cobros") }}/' + idCta,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#loading-historial').hide();

                        if (data.length === 0) {
                            $('#sin-pagos-mensaje').show();
                            $('#tabla-historial-container').hide();
                            return;
                        }

                        $('#tabla-historial-container').show();

                        var html = '';
                        $.each(data, function(index, item) {
                            // Formatear fecha
                            var fecha = new Date(item.cobro_fecha);
                            var fechaFormateada = ('0' + fecha.getDate()).slice(-2) + '/' +
                                                ('0' + (fecha.getMonth() + 1)).slice(-2) + '/' +
                                                fecha.getFullYear();

                            // Formatear importe
                            var importeFormateado = Number(item.cobro_importe).toLocaleString('es-PY');

                            // Determinar badge de estado y botón
                            var estadoBadge = '';
                            var botonAnular = '';

                            if (item.cobro_estado === 'COBRADO') {
                                estadoBadge = '<span class="badge badge-success">COBRADO</span>';
                                botonAnular = '<button class="btn btn-sm btn-danger btn-anular-cobro" ' +
                                    'data-id-cobro="' + item.id_cobro + '">' +
                                    '<i class="fas fa-ban"></i> Anular</button>';
                            } else if (item.cobro_estado === 'ANULADO') {
                                estadoBadge = '<span class="badge badge-secondary">ANULADO</span>';
                                if (item.usuario_anulo) {
                                    var fechaAnulado = '';
                                    if (item.fechahora_anulado) {
                                        var fa = new Date(item.fechahora_anulado);
                                        fechaAnulado = ' (' + ('0' + fa.getDate()).slice(-2) + '/' +
                                            ('0' + (fa.getMonth() + 1)).slice(-2) + '/' +
                                            fa.getFullYear() + ')';
                                    }
                                    botonAnular = '<small class="text-muted">Por: ' + item.usuario_anulo + fechaAnulado + '</small>';
                                }
                            } else {
                                estadoBadge = '<span class="badge badge-secondary">' + item.cobro_estado + '</span>';
                            }

                            html += '<tr>' +
                                '<td>' + fechaFormateada + '</td>' +
                                '<td class="text-right">' + importeFormateado + '</td>' +
                                '<td>' + (item.metodo_pago || '-') + '</td>' +
                                '<td>' + (item.nro_voucher || '-') + '</td>' +
                                '<td>' + (item.usuario_registro || '-') + '</td>' +
                                '<td>' + estadoBadge + '</td>' +
                                '<td>' + botonAnular + '</td>' +
                                '</tr>';
                        });

                        $('#tbody-historial-pagos').html(html);
                    },
                    error: function(xhr, status, error) {
                        $('#loading-historial').hide();
                        $('#sin-pagos-mensaje').hide();
                        $('#tabla-historial-container').show();
                        $('#tbody-historial-pagos').html(
                            '<tr><td colspan="7" class="text-center text-danger">Error al cargar el historial: ' + error + '</td></tr>'
                        );
                    }
                });
            });

            // Manejar clic en botón "Anular" cobro
            $(document).on('click', '.btn-anular-cobro', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idCobro = $(this).data('id-cobro');
                var boton = $(this);

                Swal.fire({
                    title: '¿Está seguro?',
                    text: "Esta acción anulará el cobro y actualizará el saldo de la cuota",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Deshabilitar botón
                        boton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Anulando...');

                        // Realizar petición AJAX
                        $.ajax({
                            url: '{{ url("cuentasacobrar/cobros") }}/' + idCobro + '/anular',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Anulado',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'Ok'
                                    }).then(function() {
                                        // Cerrar modal de historial
                                        $('#modalHistorialPagos').modal('hide');

                                        // Recargar la página para ver los cambios
                                        location.reload();
                                    });
                                } else {
                                    boton.prop('disabled', false).html('<i class="fas fa-ban"></i> Anular');
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message,
                                        icon: 'error',
                                        confirmButtonText: 'Ok'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                boton.prop('disabled', false).html('<i class="fas fa-ban"></i> Anular');

                                var errorMessage = 'Error al anular el cobro';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    title: 'Error',
                                    text: errorMessage,
                                    icon: 'error',
                                    confirmButtonText: 'Ok'
                                });
                            }
                        });
                    }
                });
            });
        });

        // Función para formatear el monto en el campo de cobro
        function formatCobro(input) {
            var value = input.value.replace(/\./g, '');
            if (!isNaN(value) && value !== '') {
                input.value = formatMoney(parseInt(value));
            }
        }

        // Función para formatear montos con separador de miles
        function formatMoney(n, c, d, t) {
            let s, i, j;
            c = isNaN(c = Math.abs(c)) ? 0 : c;
            d = d === undefined ? "," : d;
            t = t === undefined ? "." : t;
            s = n < 0 ? "-" : "";
            i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c)));
            j = (j = i.length) > 3 ? j % 3 : 0;
            return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) +
                (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
        }
    </script>
@endpush
