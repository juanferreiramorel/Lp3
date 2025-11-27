<div class="row">
    <!-- Fecha Orden -->
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('fecha_orden', 'Fecha Orden:') !!}
            {!! Form::date('fecha_orden', isset($ordenCompra) ? $ordenCompra->fecha_orden : \Carbon\Carbon::now()->format('Y-m-d'), [
                'class' => 'form-control',
                'id' => 'fecha_orden',
                'required',
                'readonly',
            ]) !!}
        </div>
    </div>

    <!-- Usuario -->
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('usuario', 'Usuario/Responsable:') !!}
            {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
            {!! Form::hidden('user_id', auth()->user()->id) !!}
        </div>
    </div>

    <!-- Proveedor -->
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('id_proveedor', 'Proveedor:') !!}
            {!! Form::select('id_proveedor', $proveedores, null, [
                'class' => 'form-control',
                'required',
                'placeholder' => 'Seleccione un proveedor',
            ]) !!}
        </div>
    </div>

    <!-- Sucursal -->
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('id_sucursal', 'Sucursal:') !!}
            {!! Form::select('id_sucursal', $sucursales, null, [
                'class' => 'form-control',
                'id' => 'id_sucursal',
                'required',
                'placeholder' => 'Seleccione una sucursal',
            ]) !!}
        </div>
    </div>

    <!-- Observaciones -->
    <div class="col-md-8">
        <div class="form-group">
            {!! Form::label('observaciones', 'Observaciones:') !!}
            {!! Form::textarea('observaciones', null, [
                'class' => 'form-control',
                'rows' => 3,
            ]) !!}
        </div>
    </div>

    <!-- Total -->
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('total', 'Total:') !!}
            {!! Form::text('total', null, [
                'class' => 'form-control',
                'id' => 'total',
                'readonly',
                'style' => 'font-size: 1.5rem; font-weight: bold; text-align: right;'
            ]) !!}
        </div>
    </div>
</div>

<!-- Detalle de productos -->
@includeIf('ordencompra.detalle')

<!-- Modal de búsqueda -->
@includeIf('ordencompra.modal_producto')

@push('scripts')
<script>
    // Abrir modal y cargar productos por defecto
    document.getElementById('buscar').addEventListener('click', function() {
        $('#productSearchModal').modal('show');
        fetch('{{ url('buscar-productos-oc') }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Funciones de formateo
    function formatMoney(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function cleanNumber(str) {
        return parseFloat(str.toString().replace(/\./g, '')) || 0;
    }

    // Cálculo automático al cambiar cantidad
    $(document).on('change keyup', '.cantidad-input', function() {
        let row = $(this).closest('tr');
        let cantidad = parseInt($(this).val()) || 0;
        let precioInput = row.find('.precio-input');
        let precio = cleanNumber(precioInput.val());
        let subtotal = cantidad * precio;

        row.find('.subtotal-input').val(formatMoney(subtotal));
        calcularTotal();
    });

    // Cálculo automático al cambiar precio
    $(document).on('change keyup', '.precio-input', function() {
        let row = $(this).closest('tr');
        let cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
        let precio = cleanNumber($(this).val());
        let subtotal = cantidad * precio;

        row.find('.subtotal-input').val(formatMoney(subtotal));
        $(this).val(formatMoney(precio)); // Reformatear mientras escribe

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

    // Calcular total general
    function calcularTotal() {
        let total = 0;
        $('.subtotal-input').each(function() {
            total += cleanNumber($(this).val());
        });
        $('#total').val(formatMoney(total));
        calcularTotalesImpuestos();
    }

    // Función para calcular los totales de impuestos
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

    // Calcular total al cargar la página (modo edición)
    $(document).ready(function() {
        calcularTotal();
    });
</script>
@endpush
