<div class="card card-success">
    <div class="card-header">
        <h3 class="card-title">Formas de Pago</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-success btn-block d-sm-inline" id="agregar-pago">
                <i class="fas fa-plus"></i><span class="d-none d-sm-inline"> Agregar Forma de Pago</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Método de Pago</th>
                        <th style="min-width: 100px;">Importe</th>
                        <th class="d-none d-md-table-cell" style="min-width: 100px;">Nro. Voucher</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 120px;">Observación</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
            <tbody id="formas-pago-tbody">
                @if(isset($cobros) && count($cobros) > 0)
                    @foreach($cobros as $cobro)
                    <tr>
                        <td>
                            {!! Form::select('metodo_pago[]', $metodos_pago, $cobro->id_metodo_pago, [
                                'class' => 'form-control form-control-sm',
                                'required'
                            ]) !!}
                        </td>
                        <td>
                            <input type="text" name="importe_pago[]" class="form-control form-control-sm importe-pago-input text-right"
                                value="{{ number_format($cobro->importe, 0, ',', '.') }}" required>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <input type="text" name="nro_voucher[]" class="form-control form-control-sm"
                                value="{{ $cobro->nro_voucher }}">
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <input type="text" name="observacion_pago[]" class="form-control form-control-sm"
                                value="{{ $cobro->observacion }}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-pago">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            {!! Form::select('metodo_pago[]', $metodos_pago, null, [
                                'class' => 'form-control form-control-sm',
                                'required',
                                'placeholder' => 'Seleccione...'
                            ]) !!}
                        </td>
                        <td>
                            <input type="text" name="importe_pago[]" class="form-control form-control-sm importe-pago-input text-right"
                                value="0" required>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <input type="text" name="nro_voucher[]" class="form-control form-control-sm">
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <input type="text" name="observacion_pago[]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-pago">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
            </table>
        </div>
        <div class="mt-3 row">
            <div class="col-md-6 col-12 mb-2 mb-md-0">
                <strong>Total de Productos: </strong>
                <span id="total-productos" class="text-primary font-weight-bold">0</span> Gs.
            </div>
            <div class="col-md-6 col-12 text-md-right">
                <strong>Total Pagado: </strong>
                <span id="total-pagado" class="text-success font-weight-bold">0</span> Gs.
            </div>
        </div>
        <div class="mt-2">
            <div class="alert alert-info" id="diferencia-alert" style="display: none;">
                <strong>Diferencia: </strong>
                <span id="diferencia-monto" class="font-weight-bold"></span> Gs.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Agregar nueva forma de pago
    $('#agregar-pago').on('click', function() {
        const newRow = `
            <tr>
                <td>
                    <select name="metodo_pago[]" class="form-control form-control-sm" required>
                        <option value="">Seleccione...</option>
                        @foreach($metodos_pago as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="importe_pago[]" class="form-control form-control-sm importe-pago-input text-right"
                        value="0" required>
                </td>
                <td class="d-none d-md-table-cell">
                    <input type="text" name="nro_voucher[]" class="form-control form-control-sm">
                </td>
                <td class="d-none d-lg-table-cell">
                    <input type="text" name="observacion_pago[]" class="form-control form-control-sm">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-pago">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#formas-pago-tbody').append(newRow);
    });

    // Eliminar forma de pago
    $(document).on('click', '.remove-pago', function() {
        if ($('#formas-pago-tbody tr').length > 1) {
            $(this).closest('tr').remove();
            actualizarTotalesPago();
        } else {
            alert('Debe haber al menos una forma de pago');
        }
    });

    // Formatear importes al perder foco
    $(document).on('blur', '.importe-pago-input', function() {
        const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
        $(this).val(formatMoneyPago(valor));
        actualizarTotalesPago();
    });

    // Actualizar totales de pago
    function actualizarTotalesPago() {
        let totalPagado = 0;
        $('.importe-pago-input').each(function() {
            const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
            totalPagado += valor;
        });

        let totalProductos = parseInt($('#total').val().replace(/\./g, '')) || 0;

        $('#total-productos').text(formatMoneyPago(totalProductos));
        $('#total-pagado').text(formatMoneyPago(totalPagado));

        // Calcular diferencia
        let diferencia = totalProductos - totalPagado;
        if (diferencia !== 0) {
            $('#diferencia-monto').text(formatMoneyPago(Math.abs(diferencia)));
            if (diferencia > 0) {
                $('#diferencia-alert').removeClass('alert-success alert-danger').addClass('alert-warning').show();
                $('#diferencia-monto').before('Falta pagar: ');
            } else {
                $('#diferencia-alert').removeClass('alert-warning alert-danger').addClass('alert-success').show();
                $('#diferencia-monto').before('Excedente: ');
            }
        } else {
            $('#diferencia-alert').removeClass('alert-warning alert-success').addClass('alert-success').show();
            $('#diferencia-monto').text('Pagos coinciden con el total');
            $('#diferencia-monto').before('');
        }
    }

    // Función para actualizar desde el total de productos (llamada desde modal_producto.blade.php)
    window.actualizarTotalPago = function(total) {
        actualizarTotalesPago();
    };

    // Formatear números
    function formatMoneyPago(n, c, d, t) {
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

    // Actualizar al cargar la página
    actualizarTotalesPago();
});
</script>
@endpush
