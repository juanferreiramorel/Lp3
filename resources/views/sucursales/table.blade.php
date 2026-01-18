<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="sucursales-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th>Direccion</th>
                    <th>Telefono</th>
                    <th>Ciudad</th>
                    <th>Empresa</th>
                    <th>Estado</th>
                    <th colspan="3">Accion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sucursales as $sucursal)
                    <tr style="{{ !$sucursal->estado ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $sucursal->id_sucursal }}</td>
                        <td>{{ $sucursal->descripcion }}</td>
                        <td>{{ $sucursal->direccion }}</td>
                        <td>{{ $sucursal->telefono }}</td>
                        <td>{{ $sucursal->ciudades }}</td>
                        <td>{{ $sucursal->empresa }}</td>
                        <td>
                            @if($sucursal->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['sucursales.destroy', $sucursal->id_sucursal], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('sucursales.edit', [$sucursal->id_sucursal]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($sucursal->estado)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle', // Usamos alert-toggle en lugar de alert-delete
                                        'data-title' => 'Inactivar Sucursal',
                                        'data-mensage' => 'Desea inactivar la sucursal: ' . $sucursal->descripcion . '?',
                                        'title' => 'Inactivar sucursal'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Sucursal',
                                        'data-mensage' => 'Desea activar la sucursal: ' . $sucursal->descripcion . '?',
                                        'title' => 'Activar sucursal'
                                    ]) !!}
                                @endif
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
            @include('adminlte-templates::common.paginate', ['records' => $sucursales])
        </div>
    </div>
</div>
