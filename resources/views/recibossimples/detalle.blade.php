{{-- A solid colour bar for a section heading reads as an alert, not as structure.
     The card is now delimited by its hairline, like every other panel. --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title m-0">Detalle de Productos</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"
                    aria-label="Mostrar u ocultar el detalle">
                <i class="fas fa-minus" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Botón para buscar productos -->
            <div class="col-12 mb-3 d-flex justify-content-sm-end">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center" id="buscar">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i> Buscar Producto
                </button>
            </div>

            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-sm item-table detalle-recibo">
                        <thead>
                            <tr>
                                <th class="d-none d-md-table-cell" style="width:12%;">#</th>
                                <th style="min-width:150px;">Producto</th>
                                <th class="text-center" style="width:80px;">Cant.</th>
                                <th class="text-center d-none d-sm-table-cell" style="width:100px;">Precio</th>
                                <th class="text-center" style="width:100px;">Subtotal</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                    <tbody id="selectedProducts">
                        <!-- Los productos seleccionados se agregarán aquí -->

                        <!-- Si la función es editar preguntamos si existe la variable $detalle_recibo -->
                        @if (isset($detalle_recibo))
                            @foreach ($detalle_recibo as $value)
                            <tr>
                                <td class="text-center d-none d-md-table-cell">
                                    <input class="text-center form-control form-control-sm" type="text" readonly style="text-align: center"
                                        value="{{ $value->id_producto }}">
                                    {!! Form::hidden('codigo[]', $value->id_producto) !!}
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="producto[]"
                                        placeholder="Descripción del producto" value="{{ $value->concepto }}">
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control form-control-sm cantidad-input" type="number" min="1" name="cantidad[]"
                                        style="text-align: center" value="{{ $value->cantidad }}">
                                </td>

                                <td class="text-center d-none d-sm-table-cell">
                                    <input class="text-center form-control form-control-sm precio-input" type="text" name="precio[]"
                                        style="text-align: center" value="{{ number_format($value->precio_unitario, 0, ',', '.') }}">
                                </td>

                                <td class="text-center">
                                    <input class="text-center form-control form-control-sm subtotal-input" type="text" name="subtotal[]" readonly
                                        value="{{ number_format($value->subtotal, 0, ',', '.') }}" style="text-align: center">
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
</div>

@push('styles')
<style>
    /* Responsive mobile para tabla de productos */
    @media (max-width: 576px) {
        .detalle-recibo input.form-control {
            font-size: 14px;
            padding: 0.375rem 0.5rem;
        }

        .detalle-recibo thead th {
            font-size: 12px;
            padding: 0.5rem 0.25rem;
        }

        .detalle-recibo tbody td {
            padding: 0.5rem 0.25rem;
        }

        .detalle-recibo .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }
</style>
@endpush
