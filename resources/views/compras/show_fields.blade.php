<!-- Fila 1 -->
<div class="col-sm-3">
  <div class="form-group">
    <label><strong>ID Compra:</strong></label>
    <p>{{ $compra->id_compra }}</p>
  </div>
</div>

<div class="col-sm-3">
  <div class="form-group">
    <label><strong>Fecha:</strong></label>
    <p>{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</p>
  </div>
</div>

<div class="col-sm-3">
  <div class="form-group">
    <label><strong>Usuario:</strong></label>
    <p>{{ $compra->usuario ?? ($compra->user_name ?? '') }}</p>
  </div>
</div>

<div class="col-sm-3">
  <div class="form-group">
    <label><strong>Total:</strong></label>
    <p><strong>{{ number_format($compra->total ?? 0, 0, ',', '.') }} Gs.</strong></p>
  </div>
</div>

<!-- Fila 2 -->
<div class="col-sm-4">
  <div class="form-group">
    <label><strong>Proveedor:</strong></label>
    <p>{{ $compra->proveedor ?? $compra->id_proveedor }}</p>
  </div>
</div>

<div class="col-sm-4">
  <div class="form-group">
    <label><strong>Condicion De Compra:</strong></label>
    <p>{{ $compra->condicion_compra ?? '' }}</p>
  </div>
</div>

<div class="col-sm-4">
  <div class="form-group">
    <label><strong>Sucursal:</strong></label>
    <p>{{ $compra->sucursal ?? '' }}</p>
  </div>
</div>

<!-- Detalle Compra -->
<div class="col-sm-12">
    <h4>Detalle Compra</h4>
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
            @foreach($detalles as $detalle)
                <tr>
                    <td>{{ $detalle->id_producto }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td>
                        @if(isset($detalle->tax_porcentaje))
                            @if($detalle->tax_porcentaje == 0)
                                Exento (0%)
                            @elseif($detalle->tax_porcentaje == 5)
                                Gravado 5% (5%)
                            @elseif($detalle->tax_porcentaje == 10)
                                Gravado 10% (10%)
                            @else
                                {{ number_format($detalle->tax_porcentaje, 0) }}%
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>{{ number_format($detalle->precio_unitario, 0, ',', '.') }} Gs.</td>
                    <td>{{ number_format($detalle->precio_unitario * $detalle->cantidad, 0, ',', '.') }} Gs.</td>
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
                            <td class="text-right">{{ number_format($compra->total_exento ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>Total Gravado 5%:</strong></td>
                            <td class="text-right">{{ number_format($compra->total_gravado_5 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>Total Gravado 10%:</strong></td>
                            <td class="text-right">{{ number_format($compra->total_gravado_10 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>IVA 5%:</strong></td>
                            <td class="text-right">{{ number_format($compra->total_iva_5 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr>
                            <td><strong>IVA 10%:</strong></td>
                            <td class="text-right">{{ number_format($compra->total_iva_10 ?? 0, 0, ',', '.') }} Gs.</td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total IVA:</strong></td>
                            <td class="text-right"><strong>{{ number_format($compra->total_iva ?? 0, 0, ',', '.') }} Gs.</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h4 class="text-right">
                        <strong>TOTAL GENERAL: {{ number_format($compra->total, 0, ',', '.') }} Gs.</strong>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>