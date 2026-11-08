<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title m-0">Formas de Pago</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-default d-inline-flex align-items-center" id="agregar-pago">
                <i class="fas fa-plus mr-2" aria-hidden="true"></i><span>Agregar Forma de Pago</span>
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
                            {{-- data-autofill marks an amount the form filled in. It is
                                 cleared the moment the cashier types, so their number is
                                 never overwritten. --}}
                            <input type="text" name="importe_pago[]" class="form-control form-control-sm importe-pago-input text-right"
                                value="0" data-autofill="1" inputmode="numeric" required>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <input type="text" name="nro_voucher[]" class="form-control form-control-sm">
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <input type="text" name="observacion_pago[]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-pago"
                                    title="Quitar esta forma de pago" aria-label="Quitar esta forma de pago">
                                <i class="fas fa-trash" aria-hidden="true"></i>
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
                <span id="total-productos" class="font-weight-bold" style="font-variant-numeric: tabular-nums;">0</span> Gs.
            </div>
            <div class="col-md-6 col-12 text-md-right">
                <strong>Total Pagado: </strong>
                <span id="total-pagado" class="font-weight-bold" style="font-variant-numeric: tabular-nums;">0</span> Gs.
            </div>
        </div>
        <div class="mt-2">
            {{-- The label lives in its own node. The previous version called
                 .before() on every recalculation, which appended a fresh text
                 node each time instead of replacing the old one. --}}
            <div class="alert" id="diferencia-alert" role="status" style="display: none;">
                <strong id="diferencia-label"></strong>
                <span id="diferencia-monto" class="font-weight-bold" style="font-variant-numeric: tabular-nums;"></span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // --- Saldo pendiente -------------------------------------------------
    // The cashier should never have to do the subtraction. A new payment row
    // opens holding whatever is still owed, and the amounts already typed are
    // left alone.

    function leerImporte($input) {
        return parseInt(String($input.val()).replace(/\./g, ''), 10) || 0;
    }

    function totalProductos() {
        return parseInt(String($('#total').val()).replace(/\./g, ''), 10) || 0;
    }

    // What is still owed, ignoring the row passed in (if any).
    function saldoPendiente(excluir) {
        let pagado = 0;
        $('.importe-pago-input').each(function() {
            if (excluir && this === excluir) return;
            pagado += leerImporte($(this));
        });
        return Math.max(totalProductos() - pagado, 0);
    }

    // Keep rows the cashier has not touched in step with the balance.
    function refrescarAutocompletados(excluir) {
        const $pendientes = $('.importe-pago-input[data-autofill="1"]').filter(function() {
            return this !== excluir;
        });

        if ($pendientes.length !== 1) return;

        const $fila = $pendientes.first();
        $fila.val(formatMoneyPago(saldoPendiente($fila[0])));
    }

    // Agregar nueva forma de pago
    $('#agregar-pago').on('click', function() {
        const restante = saldoPendiente();
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
                        value="${formatMoneyPago(restante)}" data-autofill="1" inputmode="numeric" required>
                </td>
                <td class="d-none d-md-table-cell">
                    <input type="text" name="nro_voucher[]" class="form-control form-control-sm">
                </td>
                <td class="d-none d-lg-table-cell">
                    <input type="text" name="observacion_pago[]" class="form-control form-control-sm">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-pago"
                            title="Quitar esta forma de pago" aria-label="Quitar esta forma de pago">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#formas-pago-tbody').append(newRow);
        $('#formas-pago-tbody tr:last select').trigger('focus');
        actualizarTotalesPago();
    });

    // Eliminar forma de pago
    $(document).on('click', '.remove-pago', function() {
        if ($('#formas-pago-tbody tr').length > 1) {
            $(this).closest('tr').remove();
            refrescarAutocompletados();
            actualizarTotalesPago();
        } else {
            alert('Debe haber al menos una forma de pago');
        }
    });

    // Typing claims the amount: it stops being auto-filled from here on.
    $(document).on('input', '.importe-pago-input', function() {
        $(this).attr('data-autofill', '0');
    });

    // Formatear importes al perder foco
    $(document).on('blur', '.importe-pago-input', function() {
        const valor = leerImporte($(this));
        $(this).val(formatMoneyPago(valor));
        refrescarAutocompletados(this);
        actualizarTotalesPago();
    });

    // Actualizar totales de pago
    function actualizarTotalesPago() {
        let totalPagado = 0;
        $('.importe-pago-input').each(function() {
            totalPagado += leerImporte($(this));
        });

        const total = totalProductos();

        $('#total-productos').text(formatMoneyPago(total));
        $('#total-pagado').text(formatMoneyPago(totalPagado));

        const diferencia = total - totalPagado;
        const $alerta = $('#diferencia-alert');

        $alerta.removeClass('alert-warning alert-success alert-info');

        if (diferencia > 0) {
            $('#diferencia-label').text('Falta pagar:');
            $('#diferencia-monto').text(formatMoneyPago(diferencia) + ' Gs.');
            $alerta.addClass('alert-warning').show();
        } else if (diferencia < 0) {
            $('#diferencia-label').text('Excedente:');
            $('#diferencia-monto').text(formatMoneyPago(-diferencia) + ' Gs.');
            $alerta.addClass('alert-warning').show();
        } else {
            $('#diferencia-label').text('Los pagos cubren el total.');
            $('#diferencia-monto').text('');
            $alerta.addClass('alert-success').show();
        }
    }

    // Llamada desde modal_producto.blade.php cuando cambia el detalle.
    // Al cargar el primer producto, la fila intacta toma el total completo.
    window.actualizarTotalPago = function(total) {
        refrescarAutocompletados();
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
    refrescarAutocompletados();
    actualizarTotalesPago();
});
</script>
@endpush
