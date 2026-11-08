<div class="p-0 card-body">
    <!-- Vista de tabla para desktop -->
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$recibos"
                  wrapper-class="d-none d-md-block"
                      id="recibos-table"
                      empty="No hay recibos que coincidan con la búsqueda."
                      empty-hint="Probá quitar algún filtro o ampliar el rango de fechas.">
                @foreach ($recibos as $recibo)
                    <tr>
                        <td>{{ $recibo->id_recibo }}</td>
                        <td>{{ \Carbon\Carbon::parse($recibo->fecha_recibo)->format('d/m/Y') }}</td>
                        <td>{{ $recibo->clie_ci }}</td>
                        <td>{{ $recibo->cliente }}</td>
                        <td class="text-right">{{ number_format($recibo->total, 0, ',', '.') }}</td>
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
                                {{-- Icon-only actions now share one neutral treatment, so each
                                     one has to name itself for the pointer and the screen reader. --}}
                                @can('recibossimples index')
                                    <a href="{{ route('recibossimples.show', [$recibo->id_recibo]) }}"
                                        class='btn btn-default btn-xs'
                                        title="Ver el recibo {{ $recibo->id_recibo }}"
                                        aria-label="Ver el recibo {{ $recibo->id_recibo }}">
                                        <i class="far fa-eye" aria-hidden="true"></i>
                                    </a>
                                @endcan

                                @if($recibo->estado != 'ANULADO')
                                    @can('recibossimples index')
                                        <a href="{{ route('recibossimples.imprimir', [$recibo->id_recibo]) }}"
                                            class='btn btn-success btn-xs' target="_blank" rel="noopener"
                                            title="Imprimir el recibo {{ $recibo->id_recibo }}"
                                            aria-label="Imprimir el recibo {{ $recibo->id_recibo }}">
                                            <i class="fas fa-print" aria-hidden="true"></i>
                                        </a>
                                    @endcan

                                    @can('recibossimples edit')
                                        <a href="{{ route('recibossimples.edit', [$recibo->id_recibo]) }}"
                                            class='btn btn-default btn-xs'
                                            title="Editar el recibo {{ $recibo->id_recibo }}"
                                            aria-label="Editar el recibo {{ $recibo->id_recibo }}">
                                            <i class="far fa-edit" aria-hidden="true"></i>
                                        </a>
                                    @endcan

                                    @can('recibossimples destroy')
                                        {!! Form::button('<i class="far fa-trash-alt" aria-hidden="true"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-xs alert-delete',
                                            'title' => 'Anular el recibo '. $recibo->id_recibo,
                                            'aria-label' => 'Anular el recibo '. $recibo->id_recibo,
                                            'data-mensaje' => 'el recibo nro: '. $recibo->id_recibo
                                        ]) !!}
                                    @endcan
                                @endif
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

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
