<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$permisos"
                  id="permissions-table"
                  empty="No hay permisos para mostrar.">
                @foreach ($permisos as $permiso)
                    <tr>
                        <td>{{ $permiso->id }}</td>
                        <td>{{ $permiso->name }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['permissions.destroy', $permiso->id], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('permissions.edit', [$permiso->id]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $permiso->name,
                                ]) !!}
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $permisos])
        </div>
    </div>
</div>
