<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover" id="ordencompra-table">
            <thead>
                <tr>
                    <th>N° OC</th>
                    <th>Proveedor</th>
                    <th>Fecha Orden</th>
                    <th>Fecha Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Sucursal</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenesCompra as $ordenCompra)
                    <tr>
                        <td>{{ $ordenCompra->id_orden_compra }}</td>
                        <td>{{ $ordenCompra->proveedor }}</td>
                        <td>{{ \Carbon\Carbon::parse($ordenCompra->fecha_orden)->format('d/m/Y') }}</td>
                        <td>
                            @if($ordenCompra->fecha_entrega_esperada)
                                {{ \Carbon\Carbon::parse($ordenCompra->fecha_entrega_esperada)->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($ordenCompra->total_orden, 0, ',', '.') }}</td>
                        <td>
                            @if($ordenCompra->estado == 'PENDIENTE')
                                <span class="badge badge-warning">PENDIENTE</span>
                            @elseif($ordenCompra->estado == 'APROBADA')
                                <span class="badge badge-info">APROBADA</span>
                            @elseif($ordenCompra->estado == 'PARCIAL')
                                <span class="badge badge-primary">PARCIAL</span>
                            @elseif($ordenCompra->estado == 'COMPLETADA')
                                <span class="badge badge-success">COMPLETADA</span>
                            @elseif($ordenCompra->estado == 'CANCELADA')
                                <span class="badge badge-danger">CANCELADA</span>
                            @endif
                        </td>
                        <td>{{ $ordenCompra->sucursal ?? '-' }}</td>
                        <td>{{ $ordenCompra->usuario ?? '-' }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['ordencompra.destroy', $ordenCompra->id_orden_compra], 'method' => 'delete', 'style' => 'display:inline']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('ordencompra.show', [$ordenCompra->id_orden_compra]) }}"
                                   class='btn btn-default btn-xs'>
                                    <i class="far fa-eye"></i>
                                </a>
                                @if(!in_array($ordenCompra->estado, ['COMPLETADA', 'CANCELADA']))
                                    <a href="{{ route('ordencompra.edit', [$ordenCompra->id_orden_compra]) }}"
                                       class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                    {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-delete',
                                        'data-mensaje' => 'esta Orden de Compra'
                                    ]) !!}
                                @endif
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No se encontraron registros</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-left">
            Mostrando {{ count($ordenesCompra) }} de {{ $total }} registros
        </div>
        <ul class="pagination pagination-sm m-0 float-right">
            @if($page > 1)
                <li class="page-item"><a class="page-link" href="?page={{ $page - 1 }}">«</a></li>
            @endif

            @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                </li>
            @endfor

            @if($page < $totalPages)
                <li class="page-item"><a class="page-link" href="?page={{ $page + 1 }}">»</a></li>
            @endif
        </ul>
    </div>
</div>
