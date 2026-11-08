<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$roles"
                  id="roles-table"
                  empty="No hay roles para mostrar.">
                @foreach ($roles as $rol)
                    <tr>
                        <td>{{ $rol->id }}</td>
                        <td>{{ $rol->name }}</td>
                        <td>{{ $rol->guard_name }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['roles.destroy', $rol->id], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('roles.edit', [$rol->id]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $rol->name,
                                ]) !!}
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $roles])
        </div>
    </div>
</div>
