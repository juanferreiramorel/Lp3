<div class="p-0 card-body">
    <div class="table-responsive">
        <table class="table" id="notas-debito-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ci/Ruc</th>
                    <th>Cliente</th>
                    <th>Fecha Emisión</th>
                    <th>Nro Nota</th>
                    <th>Total</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th colspan="3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notas as $nota)
                    <tr>
                        <td>{{ $nota->id_nota_debito }}</td>
                        <td>{{ $nota->clie_ci }}</td>
                        <td>{{ $nota->cliente }}</td>
                        <td>{{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</td>
                        <td>{{ $nota->nro_nota }}</td>
                        <td>{{ number_format($nota->total, 0, ',', '.') }}</td>
                        <td>{{ $nota->usuario }}</td>
                        <td>
                            <span class="badge bg-{{ $nota->estado == 'COMPLETADO' ? 'info' : ($nota->estado == 'ANULADO' ? 'danger' : 'success') }}">
                                {{ $nota->estado }}
                            </span>
                        </td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['notas_debito.destroy', $nota->id_nota_debito], 'method' => 'delete']) !!}
                            <div class='btn-group'>
                                <a href="{{ route('notas_debito.show', [$nota->id_nota_debito]) }}" class='btn btn-info btn-xs'>
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('notas_debito.pdf', [$nota->id_nota_debito]) }}" class='btn btn-success btn-xs' target="_blank" title="Imprimir">
                                    <i class="fas fa-print"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', ['type' => 'submit', 'class' => 'btn btn-danger btn-xs', 'onclick' => "return confirm('¿Está seguro de eliminar este registro?')"]) !!}
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="clearfix card-footer">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $notas])
        </div>
    </div>
</div>
