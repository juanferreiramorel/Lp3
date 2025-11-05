<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="clientes-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre y Apellido</th>
                    <th>Nro. C.I</th>
                    <th>Telefono</th>
                    <th>Ciudad</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Departamento</th>
                    <th colspan="3">Accion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientes as $cliente)
                    <tr>
                        <td>{{ $cliente->id_cliente }}</td>
                        <td>{{ $cliente->clie_nombre . ' ' . $cliente->clie_apellido }}</td>
                        <td>{{ $cliente->clie_ci }}</td>
                        <td>{{ $cliente->clie_telefono }}</td>
                        <td>{{ $cliente->ciudad }}</td>
                        <td>{{ \Carbon\Carbon::parse($cliente->clie_fecha_nac)->format('d/m/Y') ?? null }}</td>
                        <td>{{ $cliente->departamento }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['clientes.destroy', $cliente->id_cliente], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('clientes.edit', [$cliente->id_cliente]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $cliente->clie_nombre . ' ' . $cliente->clie_apellido,
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
            @include('adminlte-templates::common.paginate', ['records' => $clientes])
        </div>
    </div>
</div>
