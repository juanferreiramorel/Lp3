<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="cajas-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripcion</th>
                    <th>Sucursal</th>
                    <th>Estado</th>
                    <th colspan="3">Accion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cajas as $caja)
                    <tr style="{{ !$caja->activo ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $caja->id_caja }}</td>
                        <td>{{ $caja->descripcion }}</td>
                        <td>{{ $caja->sucursal }}</td>
                        <td>
                            @if($caja->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['cajas.destroy', $caja->id_caja], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('cajas.edit', [$caja->id_caja]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($caja->activo)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Caja',
                                        'data-mensage' => 'Desea inactivar la caja: ' . $caja->descripcion . '?',
                                        'title' => 'Inactivar caja'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Caja',
                                        'data-mensage' => 'Desea activar la caja: ' . $caja->descripcion . '?',
                                        'title' => 'Activar caja'
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
            @include('adminlte-templates::common.paginate', ['records' => $cajas])
        </div>
    </div>
</div>
