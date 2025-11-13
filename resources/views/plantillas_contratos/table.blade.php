<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="plantillas-contratos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Plantilla</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Usuario</th>
                    <th>Última Modificación</th>
                    <th colspan="4">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plantillas as $plantilla)
                    <tr>
                        <td>{{ $plantilla->id_plantilla_contrato }}</td>
                        <td>{{ $plantilla->nombre_plantilla }}</td>
                        <td>{{ $plantilla->descripcion ? Str::limit($plantilla->descripcion, 50) : '-' }}</td>
                        <td>
                            @if ($plantilla->activo)
                                <span class="badge badge-success">Activa</span>
                            @else
                                <span class="badge badge-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td>{{ $plantilla->usuario_nombre ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($plantilla->fecha_modificacion)->format('d/m/Y H:i') }}</td>
                        <td style="width: 180px">
                            {!! Form::open(['route' => ['plantillas_contratos.destroy', $plantilla->id_plantilla_contrato], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('plantillas_contratos.show', [$plantilla->id_plantilla_contrato]) }}"
                                    class='btn btn-info btn-xs' title="Ver">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('plantillas_contratos.edit', [$plantilla->id_plantilla_contrato]) }}"
                                    class='btn btn-default btn-xs' title="Editar">
                                    <i class="far fa-edit"></i>
                                </a>
                                <a href="{{ route('plantillas_contratos.generar_formulario', [$plantilla->id_plantilla_contrato]) }}"
                                    class='btn btn-success btn-xs' title="Generar Contrato">
                                    <i class="fas fa-file-contract"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $plantilla->nombre_plantilla,
                                    'title' => 'Eliminar'
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
            @include('adminlte-templates::common.paginate', ['records' => $plantillas])
        </div>
    </div>
</div>
