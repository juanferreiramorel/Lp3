    <div class="card-body p-0">
        <x-data-table :columns="$columns" :sort="$sort" :paginator="$taxes"
                      id="taxes-table"
                      empty="No hay impuestos para mostrar.">
                    @foreach ($taxes as $tax)
                        <tr>
                            <td>{{ $tax->id_tax }}</td>
                            <td>{{ $tax->nombre }}</td>
                            <td>{{ $tax->descripcion ?? 'N/A' }}</td>
                            <td>{{ number_format($tax->porcentaje, 2) }}%</td>
                            <td>
                                @if($tax->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td style="width: 120px">
                                {!! Form::open(['route' => ['taxes.destroy', $tax->id_tax], 'method' => 'delete']) !!}
                                <div class='btn-group'>
                                    <a href="{{ route('taxes.show', [$tax->id_tax]) }}"
                                        class='btn btn-default btn-xs'>
                                        <i class="far fa-eye"></i>
                                    </a>
                                    <a href="{{ route('taxes.edit', [$tax->id_tax]) }}"
                                        class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                    {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-delete',
                                        'data-mensaje' => 'este impuesto',
                                    ]) !!}
                                </div>
                                {!! Form::close() !!}
                            </td>
                        </tr>
                    @endforeach
        </x-data-table>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $taxes])
            </div>
        </div>
    </div>
