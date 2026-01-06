<div class="table-responsive">
    <table class="table table-hover table-striped" id="apertura-cierre-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha Apertura</th>
                <th>Usuario</th>
                <th>Caja</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th class="text-right">Monto Apertura</th>
                <th class="text-right">Monto Cierre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aperturas as $apertura)
                <tr>
                    <td>{{ $apertura->id_apertura }}</td>
                    <td>{{ \Carbon\Carbon::parse($apertura->fecha_apertura)->format('d/m/Y') }}</td>
                    <td>{{ $apertura->usuario }}</td>
                    <td>{{ $apertura->caja }}</td>
                    <td>{{ $apertura->sucursal }}</td>
                    <td>
                        @if ($apertura->estado == 'ABIERTA')
                            <span class="badge badge-success">ABIERTA</span>
                        @else
                            <span class="badge badge-secondary">CERRADA</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($apertura->monto_apertura, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($apertura->monto_cierre, 0, ',', '.') }}</td>
                    <td>
                        <div class='btn-group'>
                            <button type="button" class="btn btn-default btn-xs btn-imprimir-arqueo"
                                data-id="{{ $apertura->id_apertura }}" title="Imprimir Arqueo">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="card-footer clearfix">
        <div class="float-right">
            @if($aperturas instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $aperturas->links() }}
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Manejar clic en botón imprimir
        const botonesImprimir = document.querySelectorAll('.btn-imprimir-arqueo');
        botonesImprimir.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const url = "{{ url('apertura_cierre/imprimir_cierre') }}/" + id;
                window.open(url, '_blank');
            });
        });
    });
</script>
