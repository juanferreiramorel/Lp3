<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="ciudades-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ciudad</th>
                    <th>Departamento</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ciudades as $ciudad)
                    <tr>
                        <td>{{ $ciudad->id_ciudad }}</td>
                        <td>{{ $ciudad->descripcion }}</td>
                        <td>{{ $ciudad->departamento }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['ciudades.destroy', $ciudad->id_ciudad], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('ciudades.edit', [$ciudad->id_ciudad]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $ciudad->descripcion,
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
            @include('adminlte-templates::common.paginate', ['records' => $ciudades])
        </div>
    </div>
</div>
