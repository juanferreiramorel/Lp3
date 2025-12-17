<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="marcas-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th>Estado</th>
                    <th colspan="3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($marcas as $marca)
                    <tr style="{{ !$marca->activo ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $marca->id_marca }}</td>
                        <td>{{ $marca->descripcion }}</td>
                        <td>
                            @if($marca->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['marcas.destroy', $marca->id_marca], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('marcas.edit', [$marca->id_marca]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($marca->activo)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Marca',
                                        'data-mensage' => 'Desea inactivar la marca: ' . $marca->descripcion . '?',
                                        'title' => 'Inactivar marca'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Marca',
                                        'data-mensage' => 'Desea activar la marca: ' . $marca->descripcion . '?',
                                        'title' => 'Activar marca'
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
            @include('adminlte-templates::common.paginate', ['records' => $marcas])
        </div>
    </div>
</div>
