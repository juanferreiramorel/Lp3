<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="ayuda-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Titulo</th>
                    <th>Modulo</th>
                    <th>Ruta</th>
                    <th>Permiso</th>
                    <th>Estado</th>
                    <th>PDF</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($helpTopics as $helpTopic)
                    <tr>
                        <td>{{ $helpTopic->id_help }}</td>
                        <td>{{ $helpTopic->title }}</td>
                        <td>{{ $helpTopic->module ?? '-' }}</td>
                        <td>{{ $helpTopic->route_name ?? '-' }}</td>
                        <td>{{ $helpTopic->permission_slug ?? '-' }}</td>
                        <td>
                            @if ($helpTopic->is_active)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ayuda.pdf', $helpTopic->id_help) }}" target="_blank" class="btn btn-link btn-sm">
                                <i class="fas fa-file-pdf"></i> Abrir
                            </a>
                        </td>
                        <td style="width: 160px">
                            {!! Form::open(['route' => ['ayuda.destroy', $helpTopic->id_help], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('ayuda.show', [$helpTopic->id_help]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('ayuda.edit', [$helpTopic->id_help]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensage' => $helpTopic->title,
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
            @include('adminlte-templates::common.paginate', ['records' => $helpTopics])
        </div>
    </div>
</div>
