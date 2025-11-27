<!-- Id Orden de Compra Field (si viene desde orden) -->
{!! Form::hidden('id_orden_compra', isset($orden) ? $orden->id_orden_compra : null, [
    'class' => 'form-control',
]) !!}

<!-- Proveedor -->
<div class="form-group col-sm-6">
  {!! Form::label('id_proveedor', 'Proveedor:') !!}
  {!! Form::select('id_proveedor', $proveedores ?? [], isset($compra) ? $compra->id_proveedor : null, [
      'class' => 'form-control',
      'placeholder' => 'Seleccione...',
      'required'
  ]) !!}
</div>

<!-- Fecha -->
<div class="form-group col-sm-3">
  {!! Form::label('fecha_compra', 'Fecha Compra:') !!}
  {!! Form::date('fecha_compra', isset($compra) ? $compra->fecha_compra : \Carbon\Carbon::now()->format('Y-m-d'), [
      'class' => 'form-control',
      'required'
  ]) !!}
</div>

<!-- Factura Nro Field -->
<div class="form-group col-sm-3">
    {!! Form::label('factura', 'Factura Nro:') !!}
    {!! Form::text('factura', null, ['class' => 'form-control']) !!}
</div>

<!-- Usuario (solo visual) -->
<div class="form-group col-sm-3">
  {!! Form::label('usuario', 'Usuario:') !!}
  <input type="text" class="form-control" value="{{ auth()->user()->name ?? '' }}" readonly>
</div>
<input type="hidden" name="user_id" value="{{ auth()->id() }}">

<!-- Total (hidden) -->
{!! Form::hidden('total', isset($compra) ? $compra->total : 0, ['id' => 'total']) !!}

<!-- Condicion venta Field -->
<div class="form-group col-sm-4">
    {!! Form::label('condicion_compra', 'Condición de Compra:') !!}
    {!! Form::select('condicion_compra', $condicion_compra, null, [
        'class' => 'form-control',
        'id' => 'condicion_compra',
        'required',
    ]) !!}
</div>

<!-- sucursal -->
<div class="form-group col-sm-4">
    {!! Form::label('id_sucursal', 'Sucursal:') !!}
    {!! Form::select('id_sucursal', $sucursales, isset($compra) ? $compra->id_sucursal : null, [
        'class' => 'form-control',
        'id' => 'id_sucursal',
        'required',
    ]) !!}
</div>

<!-- Intervalo de Vencimiento Field -->
<div class="form-group col-sm-6" id="div-intervalo" style="display: none;"> 
    {!! Form::label('intervalo', 'Intervalo de Vencimiento:') !!}
    {!! Form::select('intervalo', $intervalo, null, [
        'class' => 'form-control',
        'placeholder' => 'Seleccione un intervalo',
        'id' => 'intervalo'
    ]) !!}
</div>

<!-- Cantidad cuota Field -->
<div class="form-group col-sm-6" id="div-cantidad-cuota" style="display: none;">
    {!! Form::label('cantidad_cuotas', 'Cantidad Cuota:') !!}
    {!! Form::number('cantidad_cuotas', null, [
        'class' => 'form-control',
        'placeholder' => 'Ingrese la cantidad de cuotas',
        'id' => 'cantidad_cuotas'
    ]) !!}
</div>

@includeIf('compras.modal_producto')

@push('scripts')
    <script>
        // Función para formatear números con separador de miles (GLOBAL)
        function formatMoney(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Función para limpiar formato de número (GLOBAL)
        function cleanNumber(str) {
            return parseFloat(str.toString().replace(/\./g, '')) || 0;
        }

        // Función para calcular el total general (GLOBAL)
        function calcularTotal() {
            let total = 0;
            $('.subtotal-input').each(function() {
                total += cleanNumber($(this).val());
            });
            // Actualizar campo hidden con valor numérico sin formato
            $('#total').val(total);
            calcularTotalesImpuestos();
        }

        // Función para calcular los totales de impuestos (GLOBAL)
        function calcularTotalesImpuestos() {
            let subtotal_sin_impuestos = 0;
            let total_iva_5 = 0;
            let total_iva_10 = 0;
            let tiene_iva_5 = false;
            let tiene_iva_10 = false;

            // Recorrer cada fila de la tabla
            $('#selectedProducts tr').each(function() {
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
                let query = document.getElementById('productSearchQuery').value;
                fetch('{{ url('buscar-productoscompras') }}?query=' + query + '&cod_suc=' + $("#id_sucursal").val())// capturar valor de sucursal utilzando val()
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalResults').innerHTML = html; // innerHTML es para cargar en el modal
                    })
                    .catch(error => {
                        console.error('Error:', error); // mostrar error en consola
                    });
            });

            // Ocultar o mostra campos segun seleccion de condicion de compra
            $("#condicion_compra").on("change", function() {
                var condicion_compra = $(this).val();// es capturar el dato selecciona con this.val()
                if(condicion_compra == 'CONTADO') {
                    //hide es para ocultar
                    $("#div-intervalo").hide();
                    $("#div-cantidad-cuota").hide();
                    // prop es para asignar una propiedad al campo input y decirle no requerido
                    $("#intervalo").prop('required', false);
                    $("#cantidad_cuotas").prop('required', false);
                    // Limpiar los valores cuando es CONTADO
                    $("#intervalo").val('');
                    $("#cantidad_cuotas").val('');
                } else {
                    //show es para mostrar
                    $("#div-intervalo").show();
                    $("#div-cantidad-cuota").show();
                    // prop es para asignar una propiedad al campo input y decirle es requerido
                    $("#intervalo").prop('required', true);
                    $("#cantidad_cuotas").prop('required', true);
                }
            });

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

            // Calcular total al cargar la página (cuando viene desde orden)
            calcularTotal();
        });
    </script>
@endpush