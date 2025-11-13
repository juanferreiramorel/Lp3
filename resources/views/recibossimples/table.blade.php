<div class="p-0 card-body">
    <!-- Vista de tabla para desktop -->
    <div class="table-responsive d-none d-md-block">
        <table class="table" id="recibos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>CI/RUC</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Sucursal</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recibos as $recibo)
                    <tr>
                        <td>{{ $recibo->id_recibo }}</td>
                        <td>{{ \Carbon\Carbon::parse($recibo->fecha_recibo)->format('d/m/Y') }}</td>
                        <td>{{ $recibo->clie_ci }}</td>
                        <td>{{ $recibo->cliente }}</td>
                        <td>{{ number_format($recibo->total, 0, ',', '.') }}</td>
                        <td>{{ $recibo->sucursal }}</td>
                        <td>{{ $recibo->usuario }}</td>
                        <td>
                            <span class="badge bg-{{ $recibo->estado == 'ACTIVO' ? 'success' : 'danger' }}">
                                {{ $recibo->estado }}
                            </span>
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['recibossimples.destroy', $recibo->id_recibo], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                @can('recibossimples index')
                                    <a href="{{ route('recibossimples.show', [$recibo->id_recibo]) }}" class='btn btn-default btn-xs'>
                                        <i class="far fa-eye"></i>
                                    </a>
                                @endcan

                                @if($recibo->estado != 'ANULADO')
                                    @can('recibossimples index')
                                        <a href="{{ route('recibossimples.imprimir', [$recibo->id_recibo]) }}"
                                            class='btn btn-success btn-xs' target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endcan

                                    @can('recibossimples edit')
                                        <a href="{{ route('recibossimples.edit', [$recibo->id_recibo]) }}"
                                            class='btn btn-default btn-xs'>
                                            <i class="far fa-edit"></i>
                                        </a>
                                    @endcan

                                    @can('recibossimples destroy')
                                        {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-xs alert-delete',
                                            'data-mensaje' => 'el recibo nro: '. $recibo->id_recibo
                                        ]) !!}
                                    @endcan
                                @endif
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Vista de cards para mobile -->
    <div class="d-block d-md-none">
        @foreach ($recibos as $recibo)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <strong>Recibo #{{ $recibo->id_recibo }}</strong>
                        </div>
                        <div class="col-6 text-right">
                            <span class="badge bg-{{ $recibo->estado == 'ACTIVO' ? 'success' : 'danger' }}">
                                {{ $recibo->estado }}
                            </span>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row mb-1">
                        <div class="col-5"><small class="text-muted">Fecha:</small></div>
                        <div class="col-7"><small>{{ \Carbon\Carbon::parse($recibo->fecha_recibo)->format('d/m/Y') }}</small></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-5"><small class="text-muted">Cliente:</small></div>
                        <div class="col-7"><small>{{ $recibo->cliente }}</small></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-5"><small class="text-muted">CI/RUC:</small></div>
                        <div class="col-7"><small>{{ $recibo->clie_ci }}</small></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-5"><small class="text-muted">Total:</small></div>
                        <div class="col-7"><strong>{{ number_format($recibo->total, 0, ',', '.') }} Gs.</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-5"><small class="text-muted">Sucursal:</small></div>
                        <div class="col-7"><small>{{ $recibo->sucursal }}</small></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><small class="text-muted">Usuario:</small></div>
                        <div class="col-7"><small>{{ $recibo->usuario }}</small></div>
                    </div>

                    {!! Form::open(['route' => ['recibossimples.destroy', $recibo->id_recibo], 'method' => 'delete']) !!}
                    <div class="btn-group d-flex" role="group">
                        @can('recibossimples index')
                            <a href="{{ route('recibossimples.show', [$recibo->id_recibo]) }}" class='btn btn-sm btn-default flex-fill'>
                                <i class="far fa-eye"></i> Ver
                            </a>
                        @endcan

                        @if($recibo->estado != 'ANULADO')
                            @can('recibossimples index')
                                <a href="{{ route('recibossimples.imprimir', [$recibo->id_recibo]) }}"
                                    class='btn btn-sm btn-success flex-fill' target="_blank">
                                    <i class="fas fa-print"></i> Imprimir
                                </a>
                            @endcan

                            @can('recibossimples edit')
                                <a href="{{ route('recibossimples.edit', [$recibo->id_recibo]) }}"
                                    class='btn btn-sm btn-primary flex-fill'>
                                    <i class="far fa-edit"></i> Editar
                                </a>
                            @endcan

                            @can('recibossimples destroy')
                                {!! Form::button('<i class="far fa-trash-alt"></i> Anular', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-sm btn-danger flex-fill alert-delete',
                                    'data-mensaje' => 'el recibo nro: '. $recibo->id_recibo
                                ]) !!}
                            @endcan
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        @endforeach
    </div>

    <div class="clearfix card-footer">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $recibos])
        </div>
    </div>
</div>
