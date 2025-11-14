<!-- Id Apertura Field -->
{!! Form::hidden('id_apertura', isset($apertura_caja) ? $apertura_caja->id_apertura : null, [
    'class' => 'form-control',
]) !!}
<!-- Id Pedido Field (si viene desde pedido) -->
{!! Form::hidden('id_pedido', isset($pedido) ? $pedido->id_pedido : null, [
    'class' => 'form-control',
]) !!}
<!-- Fecha Venta Field -->
<div class="form-group col-sm-4">
    {!! Form::label('fecha_venta', 'Fecha Venta:') !!}
    {!! Form::date('fecha_venta', \Carbon\Carbon::now()->format('Y-m-d'), [
        'class' => 'form-control',
        'id' => 'fecha_venta',
        'required',
        'readonly',
    ]) !!}
</div>

<!-- Timbrado Field -->
<div class="form-group col-sm-4">
    {!! Form::label('id_timbrado', 'Timbrado: *') !!}
    @if(isset($timbrados_vigentes) && count($timbrados_vigentes) > 0)
        {!! Form::select('id_timbrado',
            $timbrados_vigentes->pluck('numero_timbrado', 'id_timbrado')->prepend('Seleccione un timbrado', ''),
            null,
            [
                'class' => 'form-control',
                'id' => 'id_timbrado',
                'required' => true,
            ]
        ) !!}
        <small class="form-text text-muted">El número de factura se generará automáticamente del timbrado</small>
    @else
        <input type="text" class="form-control" value="No hay timbrados vigentes disponibles" disabled>
        <small class="form-text text-danger">
            <i class="fas fa-exclamation-triangle"></i> No puede crear ventas sin timbrados vigentes.
            @can('timbrados create')
                <a href="{{ route('timbrados.create') }}">Crear timbrado ahora</a>
            @endcan
        </small>
    @endif
</div>

<!-- Factura Nro Field -->
<div class="form-group col-sm-4">
    {!! Form::label('factura_nro_display', 'Número de Factura (Preview):') !!}
    {!! Form::text(
        'factura_nro_display',
        null,
        ['class' => 'form-control bg-light', 'readonly' => 'readonly', 'id' => 'factura_nro_display', 'placeholder' => 'Seleccione un timbrado primero'],
    ) !!}
    <small class="form-text text-success">
        <i class="fas fa-info-circle"></i> El número se generará automáticamente del timbrado seleccionado
    </small>
</div>

@push('page_scripts')
<script>
$(document).ready(function() {
    // Cargar timbrados cuando cambia la sucursal
    $('#id_sucursal').on('change', function() {
        var sucursalId = $(this).val();
        var $timbradoSelect = $('#id_timbrado');
        var $display = $('#factura_nro_display');

        if (sucursalId && sucursalId !== '') {
            // Limpiar select de timbrado y preview
            $timbradoSelect.html('<option value="">⏳ Cargando timbrados...</option>');
            $timbradoSelect.prop('disabled', true);
            $display.val('Seleccione primero una sucursal y timbrado');

            // Hacer llamada AJAX para obtener timbrados de la empresa de la sucursal
            $.ajax({
                url: '{{ route("timbrados.vigentesEmpresaPorSucursal") }}',
                type: 'GET',
                data: { id_sucursal: sucursalId },
                success: function(response) {
                    if (response.success && response.timbrados) {
                        // Limpiar y repoblar el select
                        $timbradoSelect.html('<option value="">Seleccione un timbrado</option>');

                        if (response.timbrados.length > 0) {
                            $.each(response.timbrados, function(index, timbrado) {
                                $timbradoSelect.append(
                                    $('<option></option>')
                                        .attr('value', timbrado.id_timbrado)
                                        .text(timbrado.numero_timbrado + ' (Vence: ' + timbrado.fecha_vencimiento + ', Disponibles: ' + timbrado.facturas_disponibles + ')')
                                );
                            });
                            $timbradoSelect.prop('disabled', false);
                        } else {
                            $timbradoSelect.html('<option value="">No hay timbrados vigentes disponibles</option>');
                            $display.val('⚠️ No hay timbrados vigentes para esta sucursal');
                            $display.removeClass('text-success').addClass('text-danger');
                        }
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error al cargar timbrados';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }

                    $timbradoSelect.html('<option value="">Error al cargar timbrados</option>');
                    $display.val('⚠️ ' + errorMsg);
                    $display.removeClass('text-success').addClass('text-danger');
                }
            });
        } else {
            // Sin selección de sucursal
            $timbradoSelect.html('<option value="">Seleccione primero una sucursal</option>');
            $timbradoSelect.prop('disabled', true);
            $display.val('Seleccione primero una sucursal');
        }
    });

    // Actualizar preview cuando se selecciona un timbrado
    $('#id_timbrado').on('change', function() {
        var timbradoId = $(this).val();
        var $display = $('#factura_nro_display');

        if (timbradoId && timbradoId !== '') {
            // Mostrar "Cargando..."
            $display.val('⏳ Obteniendo próximo número...');
            $display.removeClass('text-danger text-success').addClass('text-muted font-italic');

            // Hacer llamada AJAX para obtener el preview
            $.ajax({
                url: '{{ route("timbrados.previewSiguienteNumero") }}',
                type: 'GET',
                data: { id_timbrado: timbradoId },
                success: function(response) {
                    if (response.success) {
                        // Mostrar el número exacto que se generará
                        $display.val('📄 Próximo número: ' + response.numero_factura);
                        $display.removeClass('text-danger text-muted').addClass('text-success font-weight-bold');

                        // Opcional: mostrar info adicional en consola
                        console.log('Timbrado:', response.timbrado);
                        console.log('Disponibles:', response.facturas_disponibles);
                        console.log('Uso:', response.porcentaje_uso + '%');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error al obtener el número';

                    if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                        errorMsg = xhr.responseJSON.mensaje;
                    }

                    $display.val('⚠️ ' + errorMsg);
                    $display.removeClass('text-success text-muted').addClass('text-danger');
                }
            });
        } else {
            // Sin selección
            $display.val('Seleccione un timbrado primero');
            $display.removeClass('text-success').addClass('text-danger');
        }
    });

    // Ejecutar al cargar si ya hay un timbrado seleccionado
    if ($('#id_timbrado').length && $('#id_timbrado').val()) {
        $('#id_timbrado').trigger('change');
    }

    // Ejecutar al cargar si ya hay una sucursal seleccionada
    if ($('#id_sucursal').length && $('#id_sucursal').val()) {
        $('#id_sucursal').trigger('change');
    }
});
</script>
@endpush

<!-- User Id Field -->
<div class="form-group col-sm-4">
    {!! Form::label('user_id', 'Responsable:') !!}
    {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
    {!! Form::hidden('user_id', auth()->user()->id, ['class' => 'form-control']) !!}
</div>

<!-- Id Cliente Field -->
<div class="form-group col-sm-4">
    {!! Form::label('id_cliente', 'Cliente:') !!}
    {!! Form::select('id_cliente', $clientes, isset($venta) ? $venta->id_cliente : null, [
        'class' => 'form-control select2',
        'required',
        'placeholder' => 'Seleccione un cliente',
    ]) !!}
</div>

<!-- Condicion venta Field -->
<div class="form-group col-sm-4">
    {!! Form::label('condicion_venta', 'Condición de Venta:') !!}
    {!! Form::select('condicion_venta', $condicion_venta, null, [
        'class' => 'form-control',
        'id' => 'condicion_venta',
        'required',
    ]) !!}
</div>

<!-- sucursal -->
<div class="form-group col-sm-4">
    {!! Form::label('id_sucursal', 'Sucursal:') !!}
    {!! Form::select('id_sucursal', $sucursales, isset($venta) ? $venta->id_sucursal : null, [
        'class' => 'form-control',
        'id' => 'id_sucursal',
        'required',
    ]) !!}
</div>

<!-- Intervalo de Vencimiento Field -->
<div class="form-group col-sm-6" id="div-intervalo" style="display: none;">
    {!! Form::label('intervalo', 'Intervalo de Vencimiento:') !!}
    {!! Form::select('intervalo', $intervalo_vencimiento, null, [
        'class' => 'form-control',
        'placeholder' => 'Seleccione un intervalo',
        'id' => 'intervalo',
    ]) !!}
</div>

<!-- Cantidad cuota Field -->
<div class="form-group col-sm-6" id="div-cantidad-cuota" style="display: none;">
    {!! Form::label('cantidad_cuota', 'Cantidad Cuota:') !!}
    {!! Form::number('cantidad_cuota', null, [
        'class' => 'form-control',
        'placeholder' => 'Ingrese la cantidad de cuotas',
        'id' => 'cantidad_cuota',
    ]) !!}
</div>

<!-- Detalle de venta -->
<div class="form-group col-sm-12">
    @includeIf('ventas.detalle')
</div>


<!-- Total Field (Hidden - se calcula automáticamente) -->
{!! Form::hidden('total', isset($venta) ? $venta->total : (isset($ventas) ? $ventas->total : 0), ['id' => 'total']) !!}

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

@includeIf('ventas.modal_producto')

<!-- definir un campo oculto para almacenar el detalle enviado -->
<input type="hidden" id="detalle_data" name="detalle_data">

<!-- Js -->
@push('scripts')
    {{-- Bootstrap ya está incluido en AdminLTE --}}
    <script>
        // Funciones globales para cálculo de totales (accesibles desde modal_producto.blade.php)

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

        // Función para limpiar formato de número
        function cleanNumber(str) {
            return parseFloat(str.toString().replace(/\./g, '')) || 0;
        }

        // Función para calcular el total general
        function calcularTotal() {
            let total = 0;
            $('.subtotal-input').each(function() {
                total += cleanNumber($(this).val());
            });
            // Actualizar campo hidden con valor numérico sin formato
            $('#total').val(total);
            calcularTotalesImpuestos();
        }

        // Función para calcular los totales de impuestos (Estilo Odoo)
        function calcularTotalesImpuestos() {
            let subtotal_sin_impuestos = 0;
            let total_iva_5 = 0;
            let total_iva_10 = 0;
            let tiene_iva_5 = false;
            let tiene_iva_10 = false;

            // Recorrer cada fila de la tabla
            $('.detalle-venta tbody tr').each(function() {
                let row = $(this);
                let subtotal_con_iva = cleanNumber(row.find('.subtotal-input').val());
                let tax_porcentaje = parseFloat(row.find('input[name="tax_porcentaje[]"]').val()) || 0;

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
        }

        // comenzar la carga con document ready
        $(document).ready(function() {

            /** CONSULTAR AJAX PARA LLENAR POR DEFECTO EL MODAL AL ABRIR SE CONSULTA LA URL */
            document.getElementById('buscar').addEventListener('click', function() {
                $('#productSearchModal').modal('show'); // Mostrar el modal
                fetch('{{ url('buscar-productos') }}?cod_suc=' + $("#id_sucursal")
                        .val()) // capturar valor de sucursal utilzando val()
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalResults').innerHTML =
                            html; // innerHTML es para cargar en el modal
                    })
                    .catch(error => {
                        console.error('Error:', error); // mostrar error en consola
                    });
            });

            // Ocultar o mostra campos segun seleccion de condicion de venta
            $("#condicion_venta").on("change", function() {
                var condicion_venta = $(this).val(); // es capturar el dato selecciona con this.val()
                if (condicion_venta == 'CONTADO') {
                    //hide es para ocultar
                    $("#div-intervalo").hide();
                    $("#div-cantidad-cuota").hide();
                    // prop es para asignar una propiedad al campo input y decirle no requerido
                    $("#intervalo").prop('required', false);
                    $("#cantidad_cuota").prop('required', false);
                } else {
                    //show es para mostrar
                    $("#div-intervalo").show();
                    $("#div-cantidad-cuota").show();
                    // prop es para asignar una propiedad al campo input y decirle es requerido
                    $("#intervalo").prop('required', true);
                    $("#cantidad_cuota").prop('required', true);
                }
            });

            // al enviar el formulario capturamos los datos del detalle
            $('form').on('submit', function(event) {
                // Capturar los datos de la tabla de detalles
                const detalleData = [];
                $(".detalle-venta tbody tr").each(function() {// recorrer cada fila de la tabla
                    const row = $(this);
                    const detalle = {
                        id_producto: row.find("[name='id_producto[]']").val(),
                        codigo: row.find("[name='codigo[]']").val(),
                        precio: parseFloat(row.find("[name='precio[]']").val().replace(/\./g, '')),
                        producto: row.find("[name='producto[]']").val(),
                        cantidad: row.find("[name='cantidad[]']").val(),
                        subtotal: parseFloat(row.find("[name='subtotal[]']").val().replace(/\./g, '')),
                    };
                    // agregar el objeto detalle al array
                    detalleData.push(detalle);
                });

                // Convertir los datos a JSON y asignarlos al campo oculto
                $("#detalle_data").val(JSON.stringify(detalleData));
            });

            // Repoblar la tabla con datos antiguos si existen
            const oldDetalleData = @json(old('detalle_data', '[]'));
            if (oldDetalleData.length > 0) {
                const detalleData = JSON.parse(oldDetalleData);
                detalleData.forEach(detalle => {
                    seleccionarProducto(
                        detalle.codigo || '',
                        detalle.producto || '',
                        detalle.precio || 0,
                        detalle.subtotal || detalle.precio,
                        detalle.cantidad || 1
                    );
                });
            }

            // Calcular subtotal y total cuando cambia cantidad
            $(document).on('change keyup', '.cantidad-input', function() {
                let row = $(this).closest('tr');
                let cantidad = parseInt($(this).val()) || 0;
                let precioInput = row.find('.precio-input');
                let precio = cleanNumber(precioInput.val());
                let subtotal = cantidad * precio;

                row.find('.subtotal-input').val(formatMoney(subtotal));
                calcularTotal();
            });

            // Calcular subtotal y total cuando cambia precio
            $(document).on('change keyup', '.precio-input', function() {
                let row = $(this).closest('tr');
                let cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
                let precio = cleanNumber($(this).val());
                let subtotal = cantidad * precio;

                row.find('.subtotal-input').val(formatMoney(subtotal));

                // Reformatear el precio mientras el usuario escribe
                $(this).val(formatMoney(precio));

                calcularTotal();
            });

            // Eliminar fila
            $(document).on('click', '.remove-item', function() {
                const row = $(this).closest('tr');
                Swal.fire({
                    title: '¿Está seguro?',
                    text: '¿Está seguro de eliminar este item?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        row.remove();
                        calcularTotal();
                    }
                });
            });

            // Calcular totales de impuestos al cargar la página (para modo edición)
            if ($('.detalle-venta tbody tr').length > 0) {
                calcularTotalesImpuestos();
            }
        });
    </script>
@endpush
