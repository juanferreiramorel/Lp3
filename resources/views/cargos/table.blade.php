<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$cargos"
                  id="cargos-table"
                  empty="No hay cargos para mostrar.">
                @foreach ($cargos as $cargo)
                    <tr class="{{ isset($cargo->estado) && !$cargo->estado ? 'text-muted' : '' }}" 
                        style="{{ isset($cargo->estado) && !$cargo->estado ? 'background-color: #f8f9fa;' : '' }}">
                        <td>{{ $cargo->id_cargo }}</td>
                        <td>{{ $cargo->descripcion }}</td>
                        <td>
                            @if(isset($cargo->estado))
                                <span class="badge badge-{{ $cargo->estado ? 'success' : 'secondary' }}">
                                    {{ $cargo->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            @else
                                <span class="badge badge-success">Activo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['cargos.destroy', $cargo->id_cargo], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('cargos.edit', [$cargo->id_cargo]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>

                                @if(isset($cargo->estado) && !$cargo->estado)
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Cargo',
                                        'data-mensage' => '¿Desea activar el cargo: ' . $cargo->descripcion . '?',
                                        'title' => 'Activar cargo'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Cargo',
                                        'data-mensage' => '¿Desea inactivar el cargo: ' . $cargo->descripcion . '?',
                                        'title' => 'Inactivar cargo'
                                    ]) !!}
                                @endif
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $cargos])
        </div>
    </div>
</div>
