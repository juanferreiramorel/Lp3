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
                <table class="table item-table detalle-venta">
                    <thead>
                        <tr>
                            <th style="width:10%;">#</th>
                            <th style="width:30%;min-width:200px;">Producto</th>
                            <th class="text-center" style="width:10%;">IVA</th>
                            <th class="text-center" style="width:12%;">Cantidad</th>
                            <th class="text-center" style="width:15%;">Precio Unit</th>
                            <th class="text-center" style="width:15%;">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="selectedProducts">
                        <!-- Los productos seleccionados se agregarán aquí -->

                        <!-- si la funcion es editar preguntamos si existe la variable $detalle_venta -->
                        @if (isset($detalle_venta))
                            @foreach ($detalle_venta as $value)
                            <tr>
                                <td class="text-center">
                                    <input class="text-center form-control" type="text" name="codigo[]" readonly style="text-align: center"
                                        value="{!! $value->id_producto !!}">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="producto[]" readonly
                                        placeholder="Buscar productos" value="{!! $value->descripcion !!}">
                                    {!! Form::hidden('id_producto[]', $value->id_producto) !!}
                                    @if(property_exists($value, 'id_detalle_venta'))
                                        {!! Form::hidden('id_det_venta[]', $value->id_detalle_venta) !!}
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge badge-info" style="font-size: 13px;">
                                        @if(isset($value->tax_porcentaje))
                                            {{ number_format($value->tax_porcentaje, 0) }}%
                                        @else
                                            0%
                                        @endif
                                    </span>
                                    <input type="hidden" name="tax_porcentaje[]" value="{{ $value->tax_porcentaje ?? 0 }}">
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control cantidad-input" type="number" min="1" name="cantidad[]"
                                        style="text-align: center" value="{!! $value->cantidad !!}">
                                </td>

                                <td class="text-center" style="width: 20%">
                                    <input class="text-center form-control precio-input" type="text" name="precio[]"
                                        style="text-align: center" value="{!! number_format($value->precio, 0, ',', '.') !!}">
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control subtotal-input" type="text" name="subtotal[]" readonly
                                        value="{!! number_format($value->precio * $value->cantidad, 0, ',', '.') !!}" style="text-align: center">
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
