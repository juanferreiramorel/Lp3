<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('id_orden_compra', 'N° Orden de Compra:') !!}
            <p>{{ $ordenCompra->id_orden_compra }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('fecha_orden', 'Fecha Orden:') !!}
            <p>{{ \Carbon\Carbon::parse($ordenCompra->fecha_orden)->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('proveedor', 'Proveedor:') !!}
            <p>{{ $ordenCompra->proveedor }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('sucursal', 'Sucursal:') !!}
            <p>{{ $ordenCompra->sucursal ?? '-' }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('usuario', 'Usuario:') !!}
            <p>{{ $ordenCompra->usuario ?? '-' }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('estado', 'Estado:') !!}
            <p>
                @if($ordenCompra->estado == 'PENDIENTE')
                    <span class="badge badge-warning">PENDIENTE</span>
                @elseif($ordenCompra->estado == 'APROBADA')
                    <span class="badge badge-info">APROBADA</span>
                @elseif($ordenCompra->estado == 'PARCIAL')
                    <span class="badge badge-primary">PARCIAL</span>
                @elseif($ordenCompra->estado == 'PROCESADO')
                    <span class="badge badge-success">PROCESADO</span>
                @elseif($ordenCompra->estado == 'COMPLETADA')
                    <span class="badge badge-success">COMPLETADA</span>
                @elseif($ordenCompra->estado == 'CANCELADA')
                    <span class="badge badge-danger">CANCELADA</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('total', 'Total:') !!}
            <p><strong>{{ number_format($ordenCompra->total, 0, ',', '.') }}</strong></p>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group">
            {!! Form::label('observaciones', 'Observaciones:') !!}
            <p>{{ $ordenCompra->observaciones ?? '-' }}</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h4>Detalle de Productos</h4>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Precio Unitario</th>
                    <th class="text-center">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detalle as $item)
                    <tr>
                        <td>{{ $item->id_producto }}</td>
                        <td>{{ $item->producto }}</td>
                        <td class="text-center">{{ $item->cantidad }}</td>
                        <td class="text-right">{{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Desglose de Impuestos -->
<div class="row">
    <div class="col-md-8"></div>
    <div class="col-md-4">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">{{ number_format($ordenCompra->subtotal ?? 0, 0, ',', '.') }} Gs.</td>
            </tr>
            @if(($ordenCompra->total_iva_5 ?? 0) > 0)
            <tr>
                <td>IVA 5%:</td>
                <td class="text-right">{{ number_format($ordenCompra->total_iva_5, 0, ',', '.') }} Gs.</td>
            </tr>
            @endif
            @if(($ordenCompra->total_iva_10 ?? 0) > 0)
            <tr>
                <td>IVA 10%:</td>
                <td class="text-right">{{ number_format($ordenCompra->total_iva_10, 0, ',', '.') }} Gs.</td>
            </tr>
            @endif
            <tr class="border-top">
                <td><strong style="font-size: 1.1em;">Total:</strong></td>
                <td class="text-right"><strong style="font-size: 1.1em;">{{ number_format($ordenCompra->total, 0, ',', '.') }} Gs.</strong></td>
            </tr>
        </table>
    </div>
</div>
