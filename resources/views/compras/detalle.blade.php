<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Detalles</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="row">
            <!-- Botón para abrir el modal -->
            <div class="col-12 col-sm-12">
                <button type="button" class="btn btn-primary" id="buscar" style="float: right">
                    <i class="fas fa-search" aria-hidden="true"></i> Buscar Producto
                </button>
            </div>
            
            <div class="table-responsive">
                <br>
                <table class="table item-table">
                    <thead>
                        <tr>
                            <th style="width:12%;">#</th>
                            <th style="width:35%;min-width:240px;">Producto</th>
                            <th class="text-center" style="width:15%;">Cantidad</th>
                            <th class="text-center" style="width:15%;">Precio Unit</th>
                            <th class="text-center" style="width:15%;">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="selectedProducts">
                        <!-- Los productos seleccionados se agregarán aquí -->

                        <!-- si la funcion es editar o viene desde orden, mostramos los productos -->
                        @if (isset($detalles))
                            @foreach ($detalles as $value)
                            <tr>
                                <td class="text-center">
                                    <input class="text-center form-control" type="text" name="codigo[]" readonly style="text-align: center"
                                        value="{!! $value->id_producto !!}">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="producto[]" readonly
                                        placeholder="Buscar productos" value="{!! $value->descripcion !!}">
                                    {!! Form::hidden('id_producto[]', $value->id_producto) !!}
                                    {!! Form::hidden('tax_porcentaje[]', $value->tax_porcentaje ?? 0) !!}
                                    @if(isset($value->id_detalle_compra))
                                        {!! Form::hidden('id_det_compra[]', $value->id_detalle_compra) !!}
                                    @endif
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control cantidad-input" type="number" min="1" name="cantidad[]"
                                        style="text-align: center" value="{!! $value->cantidad !!}">
                                </td>

                                <td class="text-center" style="width: 20%">
                                    <input class="text-center form-control precio-input" type="text" name="precio_unitario[]"
                                        style="text-align: center" value="{!! number_format($value->precio_unitario, 0, ',', '.') !!}">
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control subtotal-input" type="text" name="subtotal[]" readonly
                                        value="{!! number_format($value->precio_unitario * $value->cantidad, 0, ',', '.') !!}" style="text-align: center">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
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
