<!-- Id Apertura Field -->
{!! Form::hidden('id_apertura', null, ['class' => 'form-control']) !!}
<!-- Fecha Pedido Field -->
<div class="form-group col-sm-4">
    {!! Form::label('fecha_pedido', 'Fecha Pedido:') !!}
    {!! Form::date('fecha_pedido', isset($pedido) ? \Carbon\Carbon::parse($pedido->fecha_pedido)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d'), [
        'class' => 'form-control',
        'id' => 'fecha_pedido',
        'required',
        'readonly',
    ]) !!}
</div>

<!-- Factura Nro Field -->
<div class="form-group col-sm-4 d-none">
    {!! Form::label('factura_nro', 'Factura Nro:') !!}
    {!! Form::text('factura_nro', null, ['class' => 'form-control', 'readonly']) !!}
</div>

<!-- User Id Field -->
<div class="form-group col-sm-4">
    {!! Form::label('user_id', 'Responsable:') !!}
    {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
    {!! Form::hidden('user_id', auth()->user()->id, ['class' => 'form-control']) !!}
</div>

<!-- Id Cliente Field -->
<div class="form-group col-sm-4">
    {!! Form::label('id_cliente', 'Cliente:') !!}
    {!! Form::select('id_cliente', $clientes, null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Seleccione un cliente',
    ]) !!}
</div>
<!-- sucursal -->
<div class="form-group col-sm-4">
    {!! Form::label('id_sucursal', 'Sucursal:') !!}
    {!! Form::select('id_sucursal', $sucursales, null, [
        'class' => 'form-control',
        'id' => 'id_sucursal',
        'required',
    ]) !!}
</div>

<!-- Cantidad cuota Field -->
<div class="form-group col-sm-6" id="div-cantidad-cuota" style="display: none;">
    {!! Form::label('cantidad_cuota', 'Cantidad Cuota:') !!}
    {!! Form::number('cantidad_cuota', null, [
        'class' => 'form-control',
        'placeholder' => 'Ingrese la cantidad de cuotas',
        'id' => 'cantidad_cuota'
    ]) !!}
</div>

<!-- Detalle de pedido -->
<div class="form-group col-sm-12">
    @includeIf('pedidos.detalle')
</div>

<!-- Total Field (Hidden - se calcula automáticamente) -->
{!! Form::hidden('total', isset($pedido) ? $pedido->total_pedido : 0, ['id' => 'total']) !!}

<!-- Hidden fields for tax totals -->
{!! Form::hidden('subtotal_sin_impuestos', 0, ['id' => 'subtotal_sin_impuestos']) !!}
{!! Form::hidden('total_iva_5', 0, ['id' => 'total_iva_5']) !!}
{!! Form::hidden('total_iva_10', 0, ['id' => 'total_iva_10']) !!}
{!! Form::hidden('total_con_impuestos', 0, ['id' => 'total_con_impuestos']) !!}

<!-- Desglose de Impuestos (Estilo Odoo) -->
<div class="col-sm-12 mt-3">
    <div class="row">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-right" id="subtotal_sin_impuestos_display">0 Gs.</td>
                        </tr>
                        <tr id="row_iva_5" style="display: none;">
                            <td>IVA 5%:</td>
                            <td class="text-right" id="total_iva_5_display">0 Gs.</td>
                        </tr>
                        <tr id="row_iva_10" style="display: none;">
                            <td>IVA 10%:</td>
                            <td class="text-right" id="total_iva_10_display">0 Gs.</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong style="font-size: 1.1em;">Total:</strong></td>
                            <td class="text-right"><strong style="font-size: 1.1em;" id="total_con_impuestos_display">0 Gs.</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@includeIf('pedidos.modal_producto')

<!-- Js -->
@push('scripts')
    <script>
        // Funciones globales para cálculo de totales (accesibles desde modal_producto.blade.php)
        // Funciones simples idénticas a ventas

        // Función para formatear números con separador de miles (compatible con versión extendida)
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

        // Función para limpiar formato de número (simple: solo elimina puntos) - igual que ventas
        function cleanNumber(str) {
            if (!str) return 0;
            // Convertir a string, eliminar puntos y convertir a número
            let numero = str.toString().replace(/\./g, '');
            return parseFloat(numero) || 0;
        }

        // Alias para compatibilidad con código existente
        window.pedidosFormatMoney = formatMoney;
        window.pedidosCleanNumber = cleanNumber;

        // Función simplificada para actualizar subtotal de una fila (igual que ventas)
        window.pedidosActualizarSubtotalFila = function(row) {
            const cantidad = parseFloat(row.find('.cantidad-input').val()) || 0;
            const precioInput = row.find('.precio-input');
            const precio = cleanNumber(precioInput.val());
            const subtotal = cantidad * precio;
            
            row.find('.subtotal-input').val(formatMoney(subtotal));
        };

        // Función para calcular los totales de impuestos (Estilo Odoo) - usando cleanNumber simple
        window.pedidosCalcularTotalesImpuestos = function() {
            let subtotal_sin_impuestos = 0;
            let total_iva_5 = 0;
            let total_iva_10 = 0;
            let tiene_iva_5 = false;
            let tiene_iva_10 = false;

            // Recorrer cada fila de la tabla (igual que ventas)
            $('.detalle-pedido tbody tr').each(function() {
                let row = $(this);
                let subtotalVal = row.find('.subtotal-input').val();
                let subtotal_con_iva = cleanNumber(subtotalVal);
                // Obtener tax_porcentaje específicamente de esta fila - buscar dentro del td que contiene producto
                let taxInput = row.find('td').eq(1).find('input[name="tax_porcentaje[]"]');
                let tax_porcentaje = 0;
                if (taxInput.length > 0) {
                    let taxVal = taxInput.val();
                    tax_porcentaje = parseFloat(taxVal) || 0;
                }

                // Calcular precio sin impuesto y monto de impuesto (fórmula paraguaya)
                let precio_sin_impuesto = 0;
                let monto_impuesto = 0;

                if (tax_porcentaje > 0) {
                    precio_sin_impuesto = subtotal_con_iva / (1 + (tax_porcentaje / 100));
                    monto_impuesto = subtotal_con_iva - precio_sin_impuesto;
                } else {
                    precio_sin_impuesto = subtotal_con_iva;
                    monto_impuesto = 0;
                }

                // Acumular subtotal sin impuestos
                subtotal_sin_impuestos += precio_sin_impuesto;

                // Acumular impuestos según tipo
                if (tax_porcentaje == 5) {
                    total_iva_5 += monto_impuesto;
                    tiene_iva_5 = true;
                } else if (tax_porcentaje == 10) {
                    total_iva_10 += monto_impuesto;
                    tiene_iva_10 = true;
                }
            });

            // Calcular total con impuestos
            let total_con_impuestos = subtotal_sin_impuestos + total_iva_5 + total_iva_10;

            // Actualizar displays
            $('#subtotal_sin_impuestos_display').text(formatMoney(Math.round(subtotal_sin_impuestos)) + ' Gs.');
            $('#total_iva_5_display').text(formatMoney(Math.round(total_iva_5)) + ' Gs.');
            $('#total_iva_10_display').text(formatMoney(Math.round(total_iva_10)) + ' Gs.');
            $('#total_con_impuestos_display').text(formatMoney(Math.round(total_con_impuestos)) + ' Gs.');

            // Actualizar campos hidden si existen (para vista de IA)
            if ($('#subtotal_sin_impuestos').length) {
                $('#subtotal_sin_impuestos').val(Math.round(subtotal_sin_impuestos));
            }
            if ($('#total_iva_5').length) {
                $('#total_iva_5').val(Math.round(total_iva_5));
            }
            if ($('#total_iva_10').length) {
                $('#total_iva_10').val(Math.round(total_iva_10));
            }
            if ($('#total_con_impuestos').length) {
                $('#total_con_impuestos').val(Math.round(total_con_impuestos));
            }

            // Mostrar/ocultar filas de IVA dinámicamente
            if (tiene_iva_5) {
                $('#row_iva_5').show();
            } else {
                $('#row_iva_5').hide();
            }

            if (tiene_iva_10) {
                $('#row_iva_10').show();
            } else {
                $('#row_iva_10').hide();
            }
        };

        // Función para calcular el total general - usando cleanNumber simple (igual que ventas)
        window.pedidosCalcularTotal = function() {
            let total = 0;
            // Solo leer subtotales de la tabla de pedidos
            $('.detalle-pedido .subtotal-input').each(function() {
                let valor = $(this).val();
                if (valor && valor.trim() !== '') {
                    let numeroLimpio = cleanNumber(valor);
                    total += numeroLimpio;
                }
            });

            // Actualizar campo hidden con valor numérico sin formato
            $('#total').val(total);
            window.pedidosCalcularTotalesImpuestos();
        };

        (function($) {
            $(document).ready(function() {
                // Calcular totales al cargar la página
                window.pedidosCalcularTotal();

                $('#buscar').on('click', function() {
                    $('#productSearchModal').modal('show');
                    fetch("{{ url('buscar-productos-pedidos') }}?cod_suc=" + $("#id_sucursal").val())
                        .then(response => response.text())
                        .then(html => {
                            const modalResults = document.getElementById('modalResults');
                            if (modalResults) {
                                modalResults.innerHTML = html;
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });

                $('#condicion_pedido').on('change', function() {
                    const condicion_pedido = $(this).val();
                    if (condicion_pedido === 'CONTADO') {
                        $('#div-intervalo, #div-cantidad-cuota').hide();
                        $('#intervalo, #cantidad_cuota').prop('required', false);
                    } else {
                        $('#div-intervalo, #div-cantidad-cuota').show();
                        $('#intervalo, #cantidad_cuota').prop('required', true);
                    }
                });

                // Calcular subtotal y total cuando cambia cantidad (igual que ventas)
                $(document).on('change keyup', '.cantidad-input', function() {
                    let row = $(this).closest('tr');
                    let cantidad = parseInt($(this).val()) || 0;
                    let precioInput = row.find('.precio-input');
                    let precio = cleanNumber(precioInput.val());
                    let subtotal = cantidad * precio;

                    row.find('.subtotal-input').val(formatMoney(subtotal));
                    window.pedidosCalcularTotal();
                });

                // Calcular subtotal y total cuando cambia precio (igual que ventas)
                $(document).on('change keyup', '.precio-input', function() {
                    let row = $(this).closest('tr');
                    let cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
                    let precio = cleanNumber($(this).val());
                    let subtotal = cantidad * precio;

                    row.find('.subtotal-input').val(formatMoney(subtotal));

                    // Reformatear el precio mientras el usuario escribe
                    $(this).val(formatMoney(precio));

                    window.pedidosCalcularTotal();
                });

                // Eliminar fila
                $(document).on('click', '.remove-item', function() {
                    const row = $(this).closest('tr');
                    Swal.fire({
                        title: 'Esta seguro?',
                        text: 'Esta seguro de eliminar este item?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Si, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            row.remove();
                            window.pedidosCalcularTotal();
                        }
                    });
                });

                // Calcular totales de impuestos al cargar la página (para modo edición)
                if ($('.detalle-pedido tbody tr').length > 0) {
                    window.pedidosCalcularTotalesImpuestos();
                }
            });
        })(jQuery);
    </script>
@endpush
