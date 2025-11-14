<!-- Fecha Recibo Field -->
<div class="form-group col-sm-3 col-6">
    {!! Form::label('fecha_recibo', 'Fecha Recibo:') !!}
    {!! Form::date('fecha_recibo', isset($recibo) ? $recibo->fecha_recibo : \Carbon\Carbon::now()->format('Y-m-d'), [
        'class' => 'form-control',
        'id' => 'fecha_recibo',
        'required',
    ]) !!}
</div>

<!-- User Id Field -->
<div class="form-group col-sm-3 col-6">
    {!! Form::label('user_id', 'Responsable:') !!}
    {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
    {!! Form::hidden('user_id', auth()->user()->id, ['class' => 'form-control']) !!}
</div>

<!-- Id Cliente Field -->
<div class="form-group col-sm-6 col-12">
    {!! Form::label('id_cliente', 'Cliente:') !!}
    <div class="cliente-field-wrapper d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
        <div class="flex-grow-1 mb-2 mb-sm-0">
            {!! Form::select('id_cliente', $clientes, isset($recibo) ? $recibo->id_cliente : null, [
                'class' => 'form-control select2',
                'required',
                'placeholder' => 'Seleccione un cliente',
            ]) !!}
        </div>
        <button type="button"
            class="btn btn-success btn-nuevo-cliente ml-0 ml-sm-2 mt-0"
            data-toggle="modal"
            data-target="#modalCliente"
            title="Crear nuevo cliente">
            <span class="btn-nuevo-cliente__icon">
                <i class="fas fa-user-plus"></i>
            </span>
            <span class="btn-nuevo-cliente__text">Nuevo cliente</span>
        </button>
    </div>
</div>

<!-- Sucursal -->
<div class="form-group col-sm-3 col-12">
    {!! Form::label('id_sucursal', 'Sucursal:') !!}
    {!! Form::select('id_sucursal', $sucursales, isset($recibo) ? $recibo->id_sucursal : null, [
        'class' => 'form-control',
        'id' => 'id_sucursal',
        'required',
    ]) !!}
</div>

<!-- Detalle de productos -->
<div class="form-group col-sm-12">
    @includeIf('recibossimples.detalle')
</div>

<!-- Total Field -->
<div class="form-group col-sm-6 col-12">
    {!! Form::label('total', 'Total:') !!}
    {!! Form::text('total', isset($recibo) ? number_format($recibo->total, 0, ',', '.') : '0', [
        'class' => 'form-control',
        'id' => 'total',
        'readonly',
    ]) !!}
</div>

<!-- Espacio vacío -->
<div class="form-group col-sm-6 d-none d-sm-block"></div>

<!-- Formas de Pago -->
<div class="col-sm-12">
    @includeIf('recibossimples.formas_pago')
</div>

@includeIf('recibossimples.modal_producto')
@include('recibossimples.modal_cliente', [
    'departamentos' => collect($departamentos ?? [])->toArray(),
    'ciudadesPorDepartamento' => $ciudadesPorDepartamento ?? []
])

@push('styles')
<style>
    .cliente-field-wrapper {
        gap: 0.5rem;
    }

    .btn-nuevo-cliente {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        padding: 0.45rem 1rem;
        border-radius: 0.4rem;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        width: 100%;
    }

    .btn-nuevo-cliente__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-right: 0.35rem;
    }

    .btn-nuevo-cliente:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.45);
    }

    .btn-nuevo-cliente:active {
        transform: translateY(0);
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.35);
    }

    .btn-nuevo-cliente__text {
        font-size: 0.95rem;
        white-space: nowrap;
    }

    @media (min-width: 576px) {
        .btn-nuevo-cliente {
            width: auto;
            min-width: 170px;
        }
    }

    @media (max-width: 575.98px) {
        .btn-nuevo-cliente__text {
            font-size: 0.9rem;
        }
    }
</style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            /** CONSULTAR AJAX PARA LLENAR POR DEFECTO EL MODAL AL ABRIR */
            document.getElementById('buscar').addEventListener('click', function() {
                $('#productSearchModal').modal('show');
                fetch('{{ url('buscar-productos-recibos') }}?cod_suc=' + $("#id_sucursal").val())
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalResults').innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });

            // Validar antes de enviar el formulario
            $('form').on('submit', function(e) {
                // Validar que haya al menos un producto
                if ($('#selectedProducts tr').length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un producto al recibo');
                    return false;
                }

                // Validar que haya al menos una forma de pago
                if ($('#formas-pago-tbody tr').length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos una forma de pago');
                    return false;
                }

                // Validar que los totales coincidan
                let totalProductos = parseInt($('#total').val().replace(/\./g, '')) || 0;
                let totalPagado = 0;
                $('.importe-pago-input').each(function() {
                    totalPagado += parseInt($(this).val().replace(/\./g, '')) || 0;
                });

                if (totalProductos !== totalPagado) {
                    e.preventDefault();
                    alert('El total de pagos (' + formatMoney(totalPagado) + ') debe ser igual al total de productos (' + formatMoney(totalProductos) + ')');
                    return false;
                }

                return true;
            });

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
        });
    </script>
@endpush
