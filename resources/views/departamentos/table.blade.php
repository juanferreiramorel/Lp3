<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="departamentos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th colspan="3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($departamentos as $departamento)
                    <tr>
                        <td>{{ $departamento->id_departamento }}</td>
                        <td>{{ $departamento->descripcion }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['departamentos.destroy', $departamento->id_departamento], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                @can('departamentos edit')
                                    <a href="{{ route('departamentos.edit', [$departamento->id_departamento]) }}"
                                        class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                @endcan

                                @can('departamentos destroy')
                                    {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs',
                                        'onclick' => "return confirm('Desea dar de baja el departamento?')",
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

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $departamentos])
        </div>
    </div>
</div>
