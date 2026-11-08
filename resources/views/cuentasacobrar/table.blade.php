    <div class="card-body p-0">
        <x-data-table :columns="$columns" :sort="$sort" :paginator="$cuentasacobrar"
                      id="cuentas-table"
                      empty="No hay cuentas a cobrar que coincidan con la búsqueda.">
                    @foreach ($cuentasacobrar as $fila)
                        <tr class="factura-row" style="cursor: pointer;">
                            <td>{{ $fila->id_venta }}</td>
                            <td>{{ $fila->factura_nro }}</td>
                            <td>{{ $fila->cliente }}</td>
                            <td>{{ \Carbon\Carbon::parse($fila->fecha_venta)->format('d/m/Y') }}</td>
                            <td>{{ number_format($fila->monto_total ?? 0, 0, ',', '.') }}</td>
                            <td>{{ number_format($fila->saldo_pendiente ?? 0, 0, ',', '.') }}</td>
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
                                @if ($fila->cuotas_vencidas_count > 0)
                                    <span class="badge badge-danger">{{ $fila->cuotas_vencidas_count }}</span>
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
                                <button class="btn btn-sm btn-info btn-ver-detalle" data-id-venta="{{ $fila->id_venta }}" data-factura="{{ $fila->factura_nro }}">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                    @endforeach
        </x-data-table>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $cuentasacobrar])
            </div>
        </div>
    </div>
