<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$empresas"
                  id="empresas-table" table-class="table"
                  empty="No hay empresas que coincidan con la búsqueda.">
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
    </x-data-table>

</div>
