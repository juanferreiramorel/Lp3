<!-- Id Recibo Field -->
<div class="col-sm-3 col-6">
    {!! Form::label('id_recibo', 'Nro Recibo:') !!}
    <p>{{ $recibo->id_recibo }}</p>
</div>

<!-- Fecha Recibo Field -->
<div class="col-sm-3 col-6">
    {!! Form::label('fecha_recibo', 'Fecha Recibo:') !!}
    <p>{{ \Carbon\Carbon::parse($recibo->fecha_recibo)->format('d/m/Y') }}</p>
</div>

<!-- Cliente Field -->
<div class="col-sm-3 col-12">
    {!! Form::label('cliente', 'Cliente:') !!}
    <p>{{ $recibo->cliente }}</p>
</div>

<!-- CI Field -->
<div class="col-sm-3 col-12">
    {!! Form::label('ci', 'CI/RUC:') !!}
    <p>{{ $recibo->clie_ci }}</p>
</div>

<!-- Sucursal Field -->
<div class="col-sm-4 col-12">
    {!! Form::label('sucursal', 'Sucursal:') !!}
    <p>{{ $recibo->sucursal }}</p>
</div>

<!-- Usuario Field -->
<div class="col-sm-4 col-12">
    {!! Form::label('usuario', 'Responsable:') !!}
    <p>{{ $recibo->usuario }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-4 col-12">
    {!! Form::label('estado', 'Estado:') !!}
    <p>
        <span class="badge bg-{{ $recibo->estado == 'ACTIVO' ? 'success' : 'danger' }}">
            {{ $recibo->estado }}
        </span>
    </p>
</div>

<!-- Total Field -->
<div class="col-sm-12">
    <h4>{!! Form::label('total', 'Total:') !!}</h4>
    <h3>{{ number_format($recibo->total, 0, ',', '.') }} Gs.</h3>
</div>

<!-- Detalle de Conceptos -->
<div class="col-sm-12">
    <h4>Detalle de Conceptos</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Precio Unitario</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalle_recibo as $detalle)
                    <tr>
                        <td>{{ $detalle->concepto }}</td>
                        <td class="text-center">{{ $detalle->cantidad }}</td>
                        <td class="text-right">{{ number_format($detalle->precio_unitario, 0, ',', '.') }} Gs.</td>
                        <td class="text-right">{{ number_format($detalle->subtotal, 0, ',', '.') }} Gs.</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">TOTAL:</th>
                    <th class="text-right">{{ number_format($recibo->total, 0, ',', '.') }} Gs.</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Información de Pago -->
<div class="col-sm-12">
    <h4>Formas de Pago</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Método de Pago</th>
                    <th class="text-right">Importe</th>
                    <th>Nro. Voucher</th>
                    <th>Observación</th>
                    <th>Fecha de Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cobros as $cobro)
                    <tr>
                        <td>{{ $cobro->metodo_pago }}</td>
                        <td class="text-right">{{ number_format($cobro->importe, 0, ',', '.') }} Gs.</td>
                        <td>{{ $cobro->nro_voucher ?? '-' }}</td>
                        <td>{{ $cobro->observacion ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($cobro->fecha_cobro)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">TOTAL PAGADO:</th>
                    <th class="text-right">{{ number_format(collect($cobros)->sum('importe'), 0, ',', '.') }} Gs.</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
