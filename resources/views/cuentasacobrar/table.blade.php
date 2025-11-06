    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="productos-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Cliente</th>
                        <th>N° de Factura</th>
                        <th>Fecha de Venta</th>
                        <th>Monto Total</th>
                        <th>Estado</th>
                        <th>Fecha de Vencimiento</th>
                        <th>N° Cuotas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cuentasacobrar as $fila)
                        <tr>
                            <td>{{ $fila->nro_cuenta }}</td>
                            <td>{{ $fila->cliente }}</td>
                            <td>{{ $fila->factura_nro }}</td>
                            <td>{{ \Carbon\Carbon::parse($fila->fecha_venta)->format('d/m/Y') }}</td>
                            <td>{{ number_format($fila->importe?? 0, 0, ',', '.') }}</td>
                            <td>
                            @if ($fila->estado == 'PENDIENTE')
                                <span class="badge badge-warning">{{ $fila->estado  }}</span>
                            @else
                                <span class="badge ">{{ $fila->estado  }}</span>
                            @endif
                            
                            </td>

                            <td>{{ \Carbon\Carbon::parse($fila->vencimiento)->format('d/m/Y') }}</td>
                            <td>{{ $fila->nro_cuotas }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $cuentasacobrar])
            </div>
        </div>
    </div>
