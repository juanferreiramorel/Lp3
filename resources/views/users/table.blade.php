<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre y Apellido</th>
                    <th>Username</th>
                    <th>Nro Doc.</th>
                    <th>Teléfono</th>
                    <th>Fecha Ingreso</th>
                    <th>Estado</th>
                    <th>Rol</th>
                    <th>Sucursal</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->ci }}</td>
                        <td>{{ $user->telefono }}</td>
                        <td>{{ !empty($user->fecha_ingreso) ? Carbon\Carbon::parse($user->fecha_ingreso)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ $user->estado == true ? 'Activo' : 'Inactivo' }}</td>
                        <td>{{ $user->rol }}</td>
                        <td>{{ $user->sucursal }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['users.destroy', $user->id], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                @can('users edit')
                                    <a href="{{ route('users.edit', [$user->id]) }}" class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                @endcan

                                @can('users destroy')
                                    @if ($user->estado == true)
                                        {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-xs alert-delete',
                                            'data-mensaje' => 'el usuario (inactivar)',
                                        ]) !!}
                                    @else
                                        {!! Form::button('<i class="fas fa-check"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-success btn-xs alert-delete',
                                            'data-mensaje' => 'el usuario (activar)',
                                        ]) !!}
                                    @endif
                                @endcan
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $users])
        </div>
    </div>
</div>
