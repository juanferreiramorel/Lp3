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

<!-- Total -->
<div class="form-group col-sm-3">
  {!! Form::label('total', 'Total:') !!}
  {!! Form::text('total', isset($compra) ? number_format($compra->total ?? 0, 0, ',', '.') : '0', [
      'class' => 'form-control text-right',
      'readonly',
      'id' => 'total'
  ]) !!}
</div>

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

            // Calcular total al cargar la página (cuando viene desde orden)
            calcularTotal();
        });
    </script>
@endpush