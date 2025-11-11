    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="cuentas-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>N° de Factura</th>
                        <th>Proveedor</th>
                        <th>Fecha de Compra</th>
                        <th>Monto Total</th>
                        <th>Total Cuotas</th>
                        <th>Cuotas Pendientes</th>
                        <th>Cuotas Pagadas</th>
                        <th>Rango Vencimientos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cuentasapagar as $fila)
                        <tr class="factura-row" style="cursor: pointer;">
                            <td>{{ $fila->id_compra }}</td>
                            <td>{{ $fila->factura }}</td>
                            <td>{{ $fila->proveedor }}</td>
                            <td>{{ \Carbon\Carbon::parse($fila->fecha_compra)->format('d/m/Y') }}</td>
                            <td>{{ number_format($fila->monto_total ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $fila->total_cuotas }}</td>
                            <td>
                                @if ($fila->cuotas_pendientes > 0)
                                    <span class="badge badge-warning">{{ $fila->cuotas_pendientes }}</span>
                                @else
                                    <span class="badge badge-secondary">0</span>
                                @endif
                            </td>
                            <td>
                                @if ($fila->cuotas_pagadas > 0)
                                    <span class="badge badge-success">{{ $fila->cuotas_pagadas }}</span>
                                @else
                                    <span class="badge badge-secondary">0</span>
                                @endif
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($fila->primer_vencimiento)->format('d/m/Y') }}
                                -
                                {{ \Carbon\Carbon::parse($fila->ultimo_vencimiento)->format('d/m/Y') }}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info btn-ver-detalle" data-id-compra="{{ $fila->id_compra }}" data-factura="{{ $fila->factura }}">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $cuentasapagar])
            </div>
        </div>
    </div>
