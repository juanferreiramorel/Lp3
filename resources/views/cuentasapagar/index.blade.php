@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Cuentas a Pagar</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        @includeIf('cuentasapagar.filtros', [
            'proveedores' => $proveedores,
            'sucursales' => $sucursales,
            'estados_cuota' => $estados_cuota
        ])

        <!-- agregar la clase tabla-container para mostrar los valores filtrados de table-->
        <div class="card tabla-container">
            @include('cuentasapagar.table')
        </div>
    </div>

    @include('cuentasapagar.modals')
@endsection

@push('scripts')
    <script>
        // Permisos del usuario
        var canCreate = {{ auth()->user()->can('cuentasapagar create') ? 'true' : 'false' }};
        var canDelete = {{ auth()->user()->can('cuentasapagar destroy') ? 'true' : 'false' }};

        // Función para actualizar el detalle de cuentas
        function actualizarDetalleCuentas(idCompra) {
            $.ajax({
                url: '{{ url("cuentasapagar") }}/' + idCompra + '/detalle',
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
                            if (canCreate) {
                                botonPagar = '<button class="btn btn-sm btn-success btn-pagar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                            } else {
                                botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                            }
                        } else if (estadoMostrar === 'VENCIDO') {
                            estadoBadge = '<span class="badge badge-danger">VENCIDO</span>';
                            if (canCreate) {
                                botonPagar = '<button class="btn btn-sm btn-warning btn-pagar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                            } else {
                                botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                            }
                        } else if (estadoMostrar === 'PARCIAL') {
                            estadoBadge = '<span class="badge badge-info">PARCIAL</span>';
                            if (canCreate) {
                                botonPagar = '<button class="btn btn-sm btn-primary btn-pagar-cuenta" ' +
                                    'data-id-cta="' + item.nro_cuenta + '" ' +
                                    'data-nro-cuota="' + item.nro_cuota + '" ' +
                                    'data-importe="' + item.importe + '" ' +
                                    'data-saldo="' + item.saldo + '" ' +
                                    'data-vencimiento="' + fechaFormateada + '">' +
                                    '<i class="fas fa-dollar-sign"></i> Pagar Saldo</button>';
                            } else {
                                botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                            }
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
                $('#proveedores').val('').trigger('change');
                $('.buscar').val('');
                $('#form-busqueda').submit();
            });

            // Manejar clic en botón "Ver Detalle"
            $(document).on('click', '.btn-ver-detalle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idCompra = $(this).data('id-compra');
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
                    url: '{{ url("cuentasapagar") }}/' + idCompra + '/detalle',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#loading-detalle').hide();

                        if (data.length === 0) {
                            $('#tbody-detalle-cuentas').html(
                                '<tr><td colspan="8" class="text-center">No hay cuentas a pagar para esta factura</td></tr>'
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
                                if (canCreate) {
                                    botonPagar = '<button class="btn btn-sm btn-success btn-pagar-cuenta" ' +
                                        'data-id-cta="' + item.nro_cuenta + '" ' +
                                        'data-nro-cuota="' + item.nro_cuota + '" ' +
                                        'data-importe="' + item.importe + '" ' +
                                        'data-saldo="' + item.saldo + '" ' +
                                        'data-vencimiento="' + fechaFormateada + '">' +
                                        '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                                } else {
                                    botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                                }
                            } else if (estadoMostrar === 'VENCIDO') {
                                estadoBadge = '<span class="badge badge-danger">VENCIDO</span>';
                                if (canCreate) {
                                    botonPagar = '<button class="btn btn-sm btn-warning btn-pagar-cuenta" ' +
                                        'data-id-cta="' + item.nro_cuenta + '" ' +
                                        'data-nro-cuota="' + item.nro_cuota + '" ' +
                                        'data-importe="' + item.importe + '" ' +
                                        'data-saldo="' + item.saldo + '" ' +
                                        'data-vencimiento="' + fechaFormateada + '">' +
                                        '<i class="fas fa-dollar-sign"></i> Pagar</button>';
                                } else {
                                    botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                                }
                            } else if (estadoMostrar === 'PARCIAL') {
                                estadoBadge = '<span class="badge badge-info">PARCIAL</span>';
                                if (canCreate) {
                                    botonPagar = '<button class="btn btn-sm btn-primary btn-pagar-cuenta" ' +
                                        'data-id-cta="' + item.nro_cuenta + '" ' +
                                        'data-nro-cuota="' + item.nro_cuota + '" ' +
                                        'data-importe="' + item.importe + '" ' +
                                        'data-saldo="' + item.saldo + '" ' +
                                        'data-vencimiento="' + fechaFormateada + '">' +
                                        '<i class="fas fa-dollar-sign"></i> Pagar Saldo</button>';
                                } else {
                                    botonPagar = '<span class="text-muted"><i class="fas fa-lock"></i> Sin permiso</span>';
                                }
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
            $(document).on('click', '.btn-pagar-cuenta', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idCta = $(this).data('id-cta');
                var nroCuota = $(this).data('nro-cuota');
                var importe = $(this).data('importe');
                var saldo = $(this).data('saldo');
                var vencimiento = $(this).data('vencimiento');

                // Llenar datos en el modal de pago
                $('#pago-id-cta').val(idCta);
                $('#pago-nro-cuenta').val(idCta);
                $('#pago-nro-cuota').val(nroCuota);
                $('#pago-importe-total').val(Number(importe).toLocaleString('es-PY'));
                $('#pago-saldo-pendiente-display').val(Number(saldo).toLocaleString('es-PY'));
                $('#pago-vencimiento').val(vencimiento);

                // Establecer fecha actual
                var today = new Date();
                var fecha = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');
                $('#pago-fecha').val(fecha);

                // Guardar saldo en un data attribute para cálculos
                $('#modalPagarCuenta').data('saldo-actual', saldo);

                // Limpiar campos del formulario
                $('#pago-metodo-pago').val('');
                $('#pago-monto-pagar').val('');
                $('#pago-nro-voucher').val('');
                $('#pago-saldo-info').hide();

                // Abrir modal de pago
                $('#modalPagarCuenta').modal('show');
            });

            // Calcular saldo pendiente al cambiar el monto a pagar
            $('#pago-monto-pagar').on('keyup change', function() {
                var saldoActual = $('#modalPagarCuenta').data('saldo-actual') || 0;
                var montoPagar = $(this).val().replace(/\./g, '');

                saldoActual = parseFloat(saldoActual);
                montoPagar = parseFloat(montoPagar) || 0;

                if (montoPagar > 0 && montoPagar < saldoActual) {
                    var saldoPendiente = saldoActual - montoPagar;
                    $('#pago-saldo-pendiente').text(Number(saldoPendiente).toLocaleString('es-PY'));
                    $('#pago-saldo-info').show();
                } else {
                    $('#pago-saldo-info').hide();
                }
            });

            // Manejar submit del formulario de pago
            $('#form-pagar-cuenta').on('submit', function(e) {
                e.preventDefault();

                var idCta = $('#pago-id-cta').val();
                var formData = {
                    id_metodo_pago: $('#pago-metodo-pago').val(),
                    monto: $('#pago-monto-pagar').val(),
                    nro_voucher: $('#pago-nro-voucher').val(),
                    fecha_pago: $('#pago-fecha').val(),
                    _token: '{{ csrf_token() }}'
                };

                // Validar campos requeridos
                if (!formData.fecha_pago) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Debe ingresar la fecha de pago',
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
                $('#btn-confirmar-pago').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                // Realizar petición AJAX
                $.ajax({
                    url: '{{ url("cuentasapagar") }}/' + idCta + '/pagar',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#btn-confirmar-pago').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar Pago');

                        if (response.success) {
                            // Cerrar modal de pago
                            $('#modalPagarCuenta').modal('hide');

                            // Abrir el recibo en una nueva pestaña
                            if (response.id_pago) {
                                var urlRecibo = '{{ url("cuentasapagar/recibo") }}/' + response.id_pago;
                                window.open(urlRecibo, '_blank');
                            }

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
                        $('#btn-confirmar-pago').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar Pago');

                        var errorMessage = 'Error al procesar el pago';

                        // Intentar obtener el mensaje de error del servidor
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.message) {
                                    errorMessage = response.message;
                                }
                            } catch (e) {
                                errorMessage = 'Error al procesar el pago. Status: ' + xhr.status;
                            }
                        }

                        // Log del error completo en consola para debugging
                        console.error('Error completo:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON
                        });

                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });
                    }
                });
            });

            // Guardar id_compra cuando se abre el modal de detalle para poder recargar después
            $('#modalDetalleCuentas').on('show.bs.modal', function(e) {
                var button = $(e.relatedTarget);
                if (button.length) {
                    var idCompra = button.data('id-compra');
                    $(this).data('id-compra-actual', idCompra);
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
                    url: '{{ url("cuentasapagar/pagos") }}/' + idCta,
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
                            var fecha = new Date(item.pago_fecha);
                            var fechaFormateada = ('0' + fecha.getDate()).slice(-2) + '/' +
                                                ('0' + (fecha.getMonth() + 1)).slice(-2) + '/' +
                                                fecha.getFullYear();

                            // Formatear importe
                            var importeFormateado = Number(item.pago_importe).toLocaleString('es-PY');

                            // Determinar badge de estado y botón
                            var estadoBadge = '';
                            var botonesAccion = '';

                            // Botón de imprimir recibo (siempre visible)
                            var botonImprimir = '<button class="btn btn-sm btn-info btn-imprimir-recibo mr-1" ' +
                                'data-id-pago="' + item.id_pago + '" title="Imprimir Recibo">' +
                                '<i class="fas fa-print"></i></button>';

                            if (item.pago_estado === 'PAGADO') {
                                estadoBadge = '<span class="badge badge-success">PAGADO</span>';
                                botonesAccion = botonImprimir;
                                if (canDelete) {
                                    botonesAccion += '<button class="btn btn-sm btn-danger btn-anular-pago" ' +
                                        'data-id-pago="' + item.id_pago + '">' +
                                        '<i class="fas fa-ban"></i> Anular</button>';
                                }
                            } else if (item.pago_estado === 'ANULADO') {
                                estadoBadge = '<span class="badge badge-secondary">ANULADO</span>';
                                if (item.usuario_anulo) {
                                    var fechaAnulado = '';
                                    if (item.fechahora_anulado) {
                                        var fa = new Date(item.fechahora_anulado);
                                        fechaAnulado = ' (' + ('0' + fa.getDate()).slice(-2) + '/' +
                                            ('0' + (fa.getMonth() + 1)).slice(-2) + '/' +
                                            fa.getFullYear() + ')';
                                    }
                                    botonesAccion = botonImprimir + '<br><small class="text-muted">Anulado por: ' + item.usuario_anulo + fechaAnulado + '</small>';
                                } else {
                                    botonesAccion = botonImprimir;
                                }
                            } else {
                                estadoBadge = '<span class="badge badge-secondary">' + item.pago_estado + '</span>';
                                botonesAccion = botonImprimir;
                            }

                            html += '<tr>' +
                                '<td>' + fechaFormateada + '</td>' +
                                '<td class="text-right">' + importeFormateado + '</td>' +
                                '<td>' + (item.metodo_pago || '-') + '</td>' +
                                '<td>' + (item.nro_voucher || '-') + '</td>' +
                                '<td>' + (item.usuario_registro || '-') + '</td>' +
                                '<td>' + estadoBadge + '</td>' +
                                '<td>' + botonesAccion + '</td>' +
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

            // Manejar clic en botón "Anular" pago
            $(document).on('click', '.btn-anular-pago', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idPago = $(this).data('id-pago');
                var boton = $(this);

                Swal.fire({
                    title: '¿Está seguro?',
                    text: "Esta acción anulará el pago y actualizará el saldo de la cuota",
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
                            url: '{{ url("cuentasapagar/pagos") }}/' + idPago + '/anular',
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

                                var errorMessage = 'Error al anular el pago';
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

            // Manejar clic en botón "Imprimir Recibo"
            $(document).on('click', '.btn-imprimir-recibo', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var idPago = $(this).data('id-pago');
                var urlRecibo = '{{ url("cuentasapagar/recibo") }}/' + idPago;

                // Abrir el recibo en una nueva ventana/pestaña
                window.open(urlRecibo, '_blank');
            });
        });

        // Función para formatear el monto en el campo de pago
        function formatPago(input) {
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
