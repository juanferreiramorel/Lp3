<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="compras-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Condicion</th>
                    <th>Factura</th>
                    <th>Proveedor</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th class="text-right">Total</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                    <tr>
                        <td>{{ $compra->id_compra }}</td>
                        <td>{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</td>
                        <td>{{ $compra->condicion_compra }}</td>
                        <td>{{ $compra->factura }}</td>
                        <td>
                            {{ $compra->proveedor ?? $compra->id_proveedor }}
                        </td>
                        <td>{{ $compra->usuario ?? ($compra->user_name ?? '') }}</td>
                        <td>
                            @if ($compra->estado == 'ANULADO')
                                <span class="badge badge-danger">{{ $compra->estado }}</span>
                            @else
                                <span class="badge badge-success">{{ $compra->estado }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($compra->total ?? 0, 0, ',', '.') }}</td>
                        <td width="120">
                            <div class="btn-group">
                                <!-- boton pagos -->
                                @if($compra->estado != 'ANULADO' && $compra->estado != 'PAGADO' && $compra->condicion_compra == 'CONTADO')
                                    <a href="{{ route('pagos.index', ['id_compra' => $compra->id_compra]) }}" class="btn btn-warning btn-xs" title="Pagar">
                                        <i class="far fa-money-bill-alt"></i>
                                    </a>
                                @endif
                                <a href="{{ route('compras.show', $compra->id_compra) }}" class="btn btn-default btn-xs"
                                    title="Ver"><i class="far fa-eye"></i></a>
                                @if ($compra->estado != 'ANULADO')
                                    <a href="{{ route('compras.edit', $compra->id_compra) }}"
                                        class="btn btn-info btn-xs" title="Editar"><i class="far fa-edit"></i></a>
                                    <button type="button" class="btn btn-danger btn-xs alert-delete"
                                        data-mensaje="{{ $compra->id_compra }}" title="Anular">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay compras registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $compras])
        </div>
    </div>
</div>
