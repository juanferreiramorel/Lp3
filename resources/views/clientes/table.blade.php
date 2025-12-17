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
                    <th>Estado</th>
                    <th colspan="3">Accion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientes as $cliente)
                    <tr style="{{ !$cliente->activo ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $cliente->id_cliente }}</td>
                        <td>{{ $cliente->clie_nombre . ' ' . $cliente->clie_apellido }}</td>
                        <td>{{ $cliente->clie_ci }}</td>
                        <td>{{ $cliente->clie_telefono }}</td>
                        <td>{{ $cliente->ciudad }}</td>
                        <td>{{ \Carbon\Carbon::parse($cliente->clie_fecha_nac)->format('d/m/Y') ?? null }}</td>
                        <td>{{ $cliente->departamento }}</td>
                        <td>
                            @if($cliente->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['clientes.destroy', $cliente->id_cliente], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('clientes.edit', [$cliente->id_cliente]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($cliente->activo)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Cliente',
                                        'data-mensage' => 'Desea inactivar el cliente: ' . $cliente->clie_nombre . ' ' . $cliente->clie_apellido . '?',
                                        'title' => 'Inactivar cliente'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Cliente',
                                        'data-mensage' => 'Desea activar el cliente: ' . $cliente->clie_nombre . ' ' . $cliente->clie_apellido . '?',
                                        'title' => 'Activar cliente'
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
            @include('adminlte-templates::common.paginate', ['records' => $clientes])
        </div>
    </div>
</div>
