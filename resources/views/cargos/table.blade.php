<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="cargos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th colspan="3">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cargos as $cargo)
                    <tr>
                        <td>{{ $cargo->id_cargo }}</td>
                        <td>{{ $cargo->descripcion }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['cargos.destroy', $cargo->id_cargo], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('cargos.edit', [$cargo->id_cargo]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>

                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $cargo->descripcion,
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
            @include('adminlte-templates::common.paginate', ['records' => $cargos])
        </div>
    </div>
</div>
