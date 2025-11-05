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
    {!! Form::select('id_sucursal', $sucursales, null, [
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
        });
    </script>
@endpush