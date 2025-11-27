<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Detalles de Productos</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        <button type="button" class="btn btn-primary mb-2" id="buscar" style="float: right">
            <i class="fas fa-search"></i> Buscar Producto
        </button>

        <div class="table-responsive">
            <table class="table table-bordered item-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Precio Unit.</th>
                        <th class="text-center">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="selectedProducts">
                    @if (isset($detalleOrdenCompra))
                        @foreach ($detalleOrdenCompra as $value)
                        <tr>
                            <td>
                                <input class="text-center form-control" type="text" name="codigo[]"
                                       readonly value="{{ $value->id_producto }}">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="producto[]"
                                       readonly value="{{ $value->producto }}">
                                {!! Form::hidden('id_producto[]', $value->id_producto) !!}
                                {!! Form::hidden('id_det_orden[]', $value->id_detalle_orden) !!}
                                {!! Form::hidden('tax_porcentaje[]', $value->tax_porcentaje ?? 0) !!}
                            </td>
                            <td>
                                <input class="text-center form-control cantidad-input" type="number"
                                       min="1" name="cantidad[]" value="{{ $value->cantidad }}"
                                       data-precio="{{ $value->precio_unitario }}"
                                       @if(in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA', 'PROCESADO'])) readonly @endif>
                            </td>
                            <td>
                                <input class="text-center form-control precio-input" type="text"
                                       name="precio[]"
                                       value="{{ number_format($value->precio_unitario, 0, ',', '.') }}">
                            </td>
                            <td>
                                <input class="text-center form-control subtotal-input" type="text"
                                       name="subtotal[]" readonly
                                       value="{{ number_format($value->precio_unitario * $value->cantidad, 0, ',', '.') }}">
                            </td>
                            <td>
                                @if(!in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA', 'PROCESADO']))
                                    <button type="button" class="btn btn-danger btn-sm remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Desglose de Impuestos (Estilo Odoo) -->
<div class="card card-secondary mt-3">
    <div class="card-body p-3">
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-right" id="subtotal_sin_impuestos_display">0 Gs.</td>
                    </tr>
                    <tr id="row_iva_5" style="display: none;">
                        <td>IVA 5%:</td>
                        <td class="text-right" id="total_iva_5_display">0 Gs.</td>
                    </tr>
                    <tr id="row_iva_10" style="display: none;">
                        <td>IVA 10%:</td>
                        <td class="text-right" id="total_iva_10_display">0 Gs.</td>
                    </tr>
                    <tr class="border-top">
                        <td><strong style="font-size: 1.1em;">Total:</strong></td>
                        <td class="text-right"><strong style="font-size: 1.1em;" id="total_con_impuestos_display">0 Gs.</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
