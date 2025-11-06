
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="productos-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sucursal</th>
                        <th>Usuario</th>
                        <th>Operacion</th>
                        <th>Tabla</th>
                        <th>Fecha</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditoria as $fila)
                        <tr>
                            <td>{{ $fila->id }}</td>
                            <td>{{ $fila->sucursal }}</td>
                            <td>{{ $fila->usuario }}</td>
                            <td>
                                @php
                                    $operacion = trim($fila->operacion);
                                @endphp
                                @if($operacion == 'AGREGA')
                                    <span class="badge bg-success">{{ $operacion }}</span>
                                @elseif($operacion == 'MODIFICA')
                                    <span class="badge bg-warning">{{ $operacion }}</span>
                                @elseif($operacion == 'ELIMINA')
                                    <span class="badge bg-danger">{{ $operacion }}</span>
                                @else
                                    <span class="badge bg-info">{{ $operacion }}</span>
                                @endif
                            </td>
                            <td>{{ $fila->tabla }}</td>
                            <td>{{ \Carbon\Carbon::parse($fila->fecha)->format('d/m/Y H:i:s') }}</td>
                            <td style="width: 120px">
                                <div class='btn-group'>
                                    <a class='btn btn-info btn-xs'
                                        title="Ver diferencias"
                                        onclick="verDiferencias(this)"
                                        data-titulo="Auditoría #{{ $fila->id }} - {{ $fila->tabla }}"
                                        data-operacion='{{ $fila->operacion }}'
                                        data-anterior-b64='{{ base64_encode($fila->anterior ?? "{}") }}'
                                        data-nuevo-b64='{{ base64_encode($fila->nuevo ?? "{}") }}'>
                                        <i class="far fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $auditoria])
            </div>
        </div>
    </div>

    @include('auditoria.modal')