<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="metodo_pagos-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th colspan="3">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($metodos as $metodo)
                    <tr style="{{ !$metodo->estado ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                        <td>{{ $metodo->id_metodo_pago }}</td>
                        <td>{{ $metodo->descripcion }}</td>
                        <td>{{ $metodo->tipo ? $metodo->tipo->descripcion : 'N/A' }}</td>
                        <td>
                            @if($metodo->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['metodo_pagos.destroy', $metodo->id_metodo_pago], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('metodo_pagos.edit', [$metodo->id_metodo_pago]) }}" class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                @if($metodo->estado)
                                    {!! Form::button('<i class="fas fa-ban"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-toggle',
                                        'data-title' => 'Inactivar Método',
                                        'data-mensage' => 'Desea inactivar el método: ' . $metodo->descripcion . '?',
                                        'title' => 'Inactivar método'
                                    ]) !!}
                                @else
                                    {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-success btn-xs alert-toggle',
                                        'data-title' => 'Activar Método',
                                        'data-mensage' => 'Desea activar el método: ' . $metodo->descripcion . '?',
                                        'title' => 'Activar método'
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
            {{-- @include('adminlte-templates::common.paginate', ['records' => $metodos]) --}}
        </div>
    </div>
</div>
