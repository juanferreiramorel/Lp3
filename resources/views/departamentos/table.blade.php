<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$departamentos"
                  id="departamentos-table"
                  empty="No hay departamentos para mostrar.">
                @foreach ($departamentos as $departamento)
                    <tr style="{{ !$departamento->estado ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $departamento->id_departamento }}</td>
                        <td>{{ $departamento->descripcion }}</td>
                        <td>
                            @if($departamento->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['departamentos.destroy', $departamento->id_departamento], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                @can('departamentos edit')
                                    <a href="{{ route('departamentos.edit', [$departamento->id_departamento]) }}"
                                        class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                @endcan

                                @can('departamentos destroy')
                                    @if($departamento->estado)
                                        {!! Form::button('<i class="fas fa-ban"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-xs alert-toggle',
                                            'data-title' => 'Inactivar Departamento',
                                            'data-mensage' => 'Desea inactivar el departamento: ' . $departamento->descripcion . '?',
                                            'title' => 'Inactivar departamento'
                                        ]) !!}
                                    @else
                                        {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-success btn-xs alert-toggle',
                                            'data-title' => 'Activar Departamento',
                                            'data-mensage' => 'Desea activar el departamento: ' . $departamento->descripcion . '?',
                                            'title' => 'Activar departamento'
                                        ]) !!}
                                    @endif
                                @endcan
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $departamentos])
        </div>
    </div>
</div>
