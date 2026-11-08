<div class="p-0 card-body">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$notas"
                  id="notas-credito-table" table-class="table"
                  empty="No hay notas de crédito que coincidan con la búsqueda.">
                @foreach ($notas as $nota)
                    <tr>
                        <td>{{ $nota->id_nota_credito }}</td>
                        <td>{{ $nota->clie_ci ?? 'S/D' }}</td>
                        <td>{{ $nota->cliente ?? '' }}</td>
                        <td>{{ $nota->sucursal ?? '' }}</td>
                        <td>{{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</td>
                        <td>{{ $nota->nro_nota }}</td>
                        <td>{{ $nota->factura ?? 'N/A' }}</td>
                        <td>{{ $nota->motivo }}</td>
                        <td>{{ number_format($nota->total, 0, ',', '.') }}</td>
                        <td>{{ $nota->usuario ?? '' }}</td>
                        <td>
                            <span class="badge bg-{{ $nota->estado == 'COMPLETADO' ? 'info' : ($nota->estado == 'ANULADO' ? 'danger' : 'success') }}">
                                {{ $nota->estado }}
                            </span>
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['notas_credito.destroy', $nota->id_nota_credito], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('notas_credito.show', [$nota->id_nota_credito]) }}" class='btn btn-info btn-xs'>
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('notas_credito.pdf', [$nota->id_nota_credito]) }}" class='btn btn-success btn-xs' target="_blank" title="Imprimir">
                                    <i class="fas fa-print"></i>
                                </a>
                                {{-- Edit button removed for now as Credit Notes editing usually restricted or implies annulment --}}
                                {{-- 
                                @if ($nota->estado != 'ANULADO' && !empty($caja_abierta) && $nota->user_id == auth()->id() && $nota->fecha_emision >= $caja_abierta->fecha_apertura)
                                    <a href="{{ route('notas_credito.edit', [$nota->id_nota_credito]) }}" class='btn btn-default btn-xs' title="Editar">
                                        <i class="far fa-edit"></i>
                                    </a>
                                @endif
                                --}}
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => 'la nota de crédito nro:'. $nota->id_nota_credito,
                                    'data-accion' => 'anular'
                                ]) !!}
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="clearfix card-footer">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $notas])
        </div>
    </div>
</div>
