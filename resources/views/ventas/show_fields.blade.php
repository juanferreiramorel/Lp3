<!-- Id Apertura Field -->
<div class="col-sm-4">
    {!! Form::label('id_apertura', 'Nro Apertura Caja:') !!}
    <p>{{ $ventas->id_apertura }}</p>
</div>

<!-- Id Cliente Field -->
<div class="col-sm-4">
    {!! Form::label('id_cliente', 'Cliente:') !!}
    <p>{{ $ventas->cliente }}</p>
</div>

<!-- Total Field -->
<div class="col-sm-4">
    {!! Form::label('total', 'Total:') !!}
    <p><strong>{{ number_format($ventas->total, 0, ',', '.') }} Gs.</strong></p>
</div>

<!-- Fecha Venta Field -->
<div class="col-sm-4">
    {!! Form::label('fecha_venta', 'Fecha Venta:') !!}
    <p>{{ \Carbon\Carbon::parse($ventas->fecha_venta)->format('d/m/Y') }}</p>
</div>

<!-- Factura Nro Field -->
<div class="col-sm-4">
    {!! Form::label('factura_nro', 'Factura Nro:') !!}
    <p>{{ $ventas->factura_nro }}</p>
</div>

<!-- User Id Field -->
<div class="col-sm-4">
    {!! Form::label('user_id', 'Vendedor:') !!}
    <p>{{ $ventas->usuario }}</p>
</div>

<!-- Detalle Venta -->
<div class="col-sm-12">
    <h4>Detalle Venta</h4>
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>Código Producto</th>
                <th>Producto</th>
                <th>Impuesto</th>
                <th>Cantidad</th>
                <th>Precio (IVA incl.)</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalle_venta as $detalle)
                <tr>
                    <td>{{ $detalle->id_producto }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td>
                        @if(isset($detalle->tax_nombre))
                            {{ $detalle->tax_nombre }}
                            @if(isset($detalle->tax_porcentaje))
                                ({{ number_format($detalle->tax_porcentaje, 0) }}%)
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>{{ number_format($detalle->precio, 0, ',', '.') }} Gs.</td>
                    <td>{{ number_format($detalle->precio * $detalle->cantidad, 0, ',', '.') }} Gs.</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Desglose de Impuestos -->
<div class="col-sm-12 mt-3">
    <div class="card">
        <div class="card-header bg-info">
            <h5 class="mb-0">Desglose de Impuestos (IVA)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Total Exento (0%):</strong></td>
                            <td class="text-right">{{ number_format($ventas->total_exento ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>Total Gravado 5%:</strong></td>
                            <td class="text-right">{{ number_format($ventas->total_gravado_5 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>Total Gravado 10%:</strong></td>
                            <td class="text-right">{{ number_format($ventas->total_gravado_10 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>IVA 5%:</strong></td>
                            <td class="text-right">{{ number_format($ventas->total_iva_5 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>IVA 10%:</strong></td>
                            <td class="text-right">{{ number_format($ventas->total_iva_10 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total IVA:</strong></td>
                            <td class="text-right"><strong>{{ number_format($ventas->total_iva ?? 0, 0, ',', '.') }} Gs.</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h4 class="text-right">
                        <strong>TOTAL GENERAL: {{ number_format($ventas->total, 0, ',', '.') }} Gs.</strong>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>