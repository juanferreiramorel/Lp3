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
@if(isset($timbrados_vigentes) && count($timbrados_vigentes) > 0)
<div class="form-group col-sm-4">
    {!! Form::label('id_timbrado', 'Timbrado:') !!}
    {!! Form::select('id_timbrado',
        $timbrados_vigentes->pluck('numero_timbrado', 'id_timbrado')->prepend('Sin timbrado', ''),
        null,
        [
            'class' => 'form-control',
            'id' => 'id_timbrado',
        ]
    ) !!}
    <small class="form-text text-muted">Seleccione un timbrado para generar factura timbrada</small>
</div>
@endif

<!-- Factura Nro Field -->
<div class="form-group col-sm-4">
    {!! Form::label('factura_nro', 'Factura Nro:') !!}
    {!! Form::text(
        'factura_nro',
        isset($apertura_caja)
            ? ($apertura_caja->establecimiento . '-' . $apertura_caja->punto_expedicion . '-' . $apertura_caja->nro_factura)
            : null,
        ['class' => 'form-control', 'readonly' => 'readonly', 'id' => 'factura_nro'],
    ) !!}
    {!! Form::hidden('factura_nro_original', isset($apertura_caja) ? ($apertura_caja->establecimiento . '-' . $apertura_caja->punto_expedicion . '-' . $apertura_caja->nro_factura) : null, ['id' => 'factura_nro_original']) !!}
    <small class="form-text text-muted" id="factura_help">
        @if(isset($timbrados_vigentes) && count($timbrados_vigentes) > 0)
            <span id="help_sin_timbrado">Número de caja. Seleccione un timbrado para generar factura timbrada</span>
            <span id="help_con_timbrado" style="display: none;" class="text-success"><i class="fas fa-check-circle"></i> Se generará automáticamente con el timbrado seleccionado</span>
        @else
            No hay timbrados vigentes disponibles
        @endif
    </small>
</div>

@push('page_scripts')
<script>
$(document).ready(function() {
    // Comportamiento cuando se selecciona/deselecciona un timbrado
    $('#id_timbrado').on('change', function() {
        var timbradoId = $(this).val();
        var facturaOriginal = $('#factura_nro_original').val();

        if (timbradoId && timbradoId !== '') {
            // Hay timbrado seleccionado
            $('#factura_nro').val('Se generará automáticamente');
            $('#factura_nro').addClass('bg-light text-muted font-italic');
            $('#help_sin_timbrado').hide();
            $('#help_con_timbrado').show();
        } else {
            // No hay timbrado seleccionado
            $('#factura_nro').val(facturaOriginal);
            $('#factura_nro').removeClass('bg-light text-muted font-italic');
            $('#help_sin_timbrado').show();
            $('#help_con_timbrado').hide();
        }
    });

    // Antes de enviar el formulario, restaurar el valor original si hay timbrado
    $('form').on('submit', function() {
        var timbradoId = $('#id_timbrado').val();
        var facturaOriginal = $('#factura_nro_original').val();

        if (timbradoId && timbradoId !== '') {
            // Restaurar el valor original para que el servidor lo procese
            $('#factura_nro').val(facturaOriginal);
        }
    });

    // Ejecutar al cargar si ya hay un timbrado seleccionado
    if ($('#id_timbrado').length && $('#id_timbrado').val()) {
        $('#id_timbrado').trigger('change');
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


<!-- Total Field -->
<div class="form-group col-sm-6">
    {!! Form::label('total', 'Total:') !!}
    {!! Form::text('total', isset($venta) ? number_format($venta->total, 0, ',', '.') : (isset($ventas) ? number_format($ventas->total, 0, ',', '.') : null), [
        'class' => 'form-control',
        'id' => 'total',
        'readonly',
    ]) !!}
</div>

@includeIf('ventas.modal_producto')

<!-- definir un campo oculto para almacenar el detalle enviado -->
<input type="hidden" id="detalle_data" name="detalle_data">

<!-- Js -->
@push('scripts')
    {{-- Bootstrap ya está incluido en AdminLTE --}}
    <script>
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

            // Función para formatear números con separador de miles
            function formatMoney(n) {
                return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Función para limpiar formato de número
            function cleanNumber(str) {
                return parseFloat(str.toString().replace(/\./g, '')) || 0;
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

            // Función para calcular el total general
            function calcularTotal() {
                let total = 0;
                $('.subtotal-input').each(function() {
                    total += cleanNumber($(this).val());
                });
                $('#total').val(formatMoney(total));
            }
        });
    </script>
@endpush
