<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover" id="timbrados-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Empresa</th>
                    <th>Nro. Timbrado</th>
                    <th>Vigencia</th>
                    <th>Rango</th>
                    <th>Disponibles</th>
                    <th>Estado</th>
                    <th colspan="3">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($timbrados as $timbrado)
                    <tr>
                        <td>{{ $timbrado->id_timbrado }}</td>
                        <td>{{ $timbrado->empresa->razon_social ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $timbrado->numero_timbrado }}</strong><br>
                            <small class="text-muted">
                                {{ $timbrado->establecimiento }}-{{ $timbrado->punto_expedicion }}
                            </small>
                        </td>
                        <td>
                            <small>
                                <strong>Inicio:</strong> {{ $timbrado->fecha_inicio->format('d/m/Y') }}<br>
                                <strong>Vence:</strong> {{ $timbrado->fecha_vencimiento->format('d/m/Y') }}
                                @if($timbrado->proximoAVencer())
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $timbrado->dias_hasta_vencimiento }} días
                                    </span>
                                @endif
                            </small>
                        </td>
                        <td>
                            <small>
                                {{ number_format($timbrado->rango_inicio) }} - {{ number_format($timbrado->rango_fin) }}<br>
                                <span class="text-muted">Total: {{ number_format($timbrado->total_facturas) }}</span>
                            </small>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar {{ $timbrado->porcentaje_uso >= 90 ? 'bg-danger' : ($timbrado->porcentaje_uso >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     role="progressbar"
                                     style="width: {{ $timbrado->porcentaje_uso }}%"
                                     aria-valuenow="{{ $timbrado->porcentaje_uso }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    {{ number_format($timbrado->porcentaje_uso, 1) }}%
                                </div>
                            </div>
                            <small class="text-muted">
                                Disponibles: <strong>{{ number_format($timbrado->facturas_disponibles) }}</strong>
                            </small>
                        </td>
                        <td>
                            @if($timbrado->estado == 'VIGENTE')
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Vigente
                                </span>
                            @elseif($timbrado->estado == 'VENCIDO')
                                <span class="badge badge-danger">
                                    <i class="fas fa-times-circle"></i> Vencido
                                </span>
                            @elseif($timbrado->estado == 'AGOTADO')
                                <span class="badge badge-warning">
                                    <i class="fas fa-exclamation-circle"></i> Agotado
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-ban"></i> Cancelado
                                </span>
                            @endif
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['timbrados.destroy', $timbrado->id_timbrado], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('timbrados.show', [$timbrado->id_timbrado]) }}"
                                    class='btn btn-default btn-xs'
                                    title="Ver detalle">
                                    <i class="far fa-eye"></i>
                                </a>
                                @can('timbrados edit')
                                <a href="{{ route('timbrados.edit', [$timbrado->id_timbrado]) }}"
                                    class='btn btn-default btn-xs'
                                    title="Editar">
                                    <i class="far fa-edit"></i>
                                </a>
                                @endcan
                                @can('timbrados destroy')
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensaje' => $timbrado->numero_timbrado,
                                    'title' => 'Eliminar'
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

    @if(count($timbrados) == 0)
    <div class="card-body">
        <p class="text-center">No hay timbrados registrados</p>
    </div>
    @endif
</div>
