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
                    <th colspan="3">Accion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sucursales as $sucursal)
                    <tr>
                        <td>{{ $sucursal->id_sucursal }}</td>
                        <td>{{ $sucursal->descripcion }}</td>
                        <td>{{ $sucursal->direccion }}</td>
                        <td>{{ $sucursal->telefono }}</td>
                        <td>{{ $sucursal->ciudades }}</td>
                        <td>{{ $sucursal->empresa }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['sucursales.destroy', $sucursal->id_sucursal], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('sucursales.edit', [$sucursal->id_sucursal]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $sucursal->descripcion,
                                ]) !!}
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
