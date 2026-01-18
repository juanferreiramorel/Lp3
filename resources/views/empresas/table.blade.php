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
                    <th>Estado</th>
                    <th colspan="3">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($empresas as $empresa)
                    <tr class="{{ !$empresa->estado ? 'text-muted' : '' }}" style="{{ !$empresa->estado ? 'background-color: #f8f9fa;' : '' }}">
                        <td>{{ $empresa->id_empresa }}</td>
                        <td>{{ $empresa->razon_social }}</td>
                        <td>{{ $empresa->nombre_comercial }}</td>
                        <td>{{ $empresa->ruc }}</td>
                        <td>{{ $empresa->telefono }}</td>
                        <td>{{ $empresa->email }}</td>
                        <td>
                            @if($empresa->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
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
                                @if($empresa->estado)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Empresa',
                                        'data-mensage' => '¿Desea inactivar la empresa: ' . $empresa->razon_social . '?',
                                        'title' => 'Inactivar'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Empresa',
                                        'data-mensage' => '¿Desea activar la empresa: ' . $empresa->razon_social . '?',
                                        'title' => 'Activar'
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

    @if(count($empresas) == 0)
    <div class="card-body">
        <p class="text-center">No hay empresas registradas</p>
    </div>
    @endif
</div>
