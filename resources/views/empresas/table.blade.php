<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="empresas-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Razón Social</th>
                    <th>Nombre Comercial</th>
                    <th>RUC</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th colspan="3">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($empresas as $empresa)
                    <tr>
                        <td>{{ $empresa->id_empresa }}</td>
                        <td>{{ $empresa->razon_social }}</td>
                        <td>{{ $empresa->nombre_comercial }}</td>
                        <td>{{ $empresa->ruc }}</td>
                        <td>{{ $empresa->telefono }}</td>
                        <td>{{ $empresa->email }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['empresas.destroy', $empresa->id_empresa], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                @can('empresas edit')
                                <a href="{{ route('empresas.show', [$empresa->id_empresa]) }}"
                                    class='btn btn-default btn-xs'
                                    title="Ver detalle">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('empresas.edit', [$empresa->id_empresa]) }}"
                                    class='btn btn-default btn-xs'
                                    title="Editar">
                                    <i class="far fa-edit"></i>
                                </a>
                                @endcan
                                @can('empresas destroy')
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $empresa->razon_social,
                                    'title' => 'Eliminar'
                                ]) !!}
                                @endcan
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(count($empresas) == 0)
    <div class="card-body">
        <p class="text-center">No hay empresas registradas</p>
    </div>
    @endif
</div>
