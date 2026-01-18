<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="ciudades-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ciudad</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th colspan="3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ciudades as $ciudad)
                    <tr style="{{ !$ciudad->estado ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $ciudad->id_ciudad }}</td>
                        <td>{{ $ciudad->descripcion }}</td>
                        <td>{{ $ciudad->departamento }}</td>
                        <td>
                            @if($ciudad->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['ciudades.destroy', $ciudad->id_ciudad], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('ciudades.edit', [$ciudad->id_ciudad]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($ciudad->estado)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Ciudad',
                                        'data-mensage' => 'Desea inactivar la ciudad: ' . $ciudad->descripcion . '?',
                                        'title' => 'Inactivar ciudad'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Ciudad',
                                        'data-mensage' => 'Desea activar la ciudad: ' . $ciudad->descripcion . '?',
                                        'title' => 'Activar ciudad'
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
            @include('adminlte-templates::common.paginate', ['records' => $ciudades])
        </div>
    </div>
</div>
