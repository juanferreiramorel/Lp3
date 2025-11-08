<!-- Modal para seleccionar órdenes de compra -->
<div class="modal fade" id="modalOrdenes" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice"></i> Seleccionar Orden de Compra
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="buscarOrden"
                           placeholder="Buscar por proveedor o número...">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaOrdenes">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Cargar órdenes cuando se abre el modal
        $('#modalOrdenes').on('show.bs.modal', function() {
            cargarOrdenes();
        });

        // Buscar órdenes en tiempo real
        $('#buscarOrden').on('keyup', function() {
            let buscar = $(this).val().toLowerCase();
            $('#listaOrdenes tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(buscar) > -1);
            });
        });

        // Función para cargar órdenes PENDIENTES y APROBADAS
        function cargarOrdenes() {
            $.ajax({
                url: '{{ route("ordencompra.getPendientes") }}',
                method: 'GET',
                success: function(response) {
                    let html = '';
                    if (response.ordenesCompra && response.ordenesCompra.length > 0) {
                        response.ordenesCompra.forEach(function(orden) {
                            // Filtrar solo PENDIENTE y APROBADA
                            if (orden.estado === 'PENDIENTE' || orden.estado === 'APROBADA') {
                                let badgeClass = orden.estado === 'PENDIENTE' ? 'badge-warning' : 'badge-info';
                                html += `
                                    <tr>
                                        <td>${orden.id_orden_compra}</td>
                                        <td>${orden.proveedor}</td>
                                        <td>${orden.fecha_orden}</td>
                                        <td class="text-right">${orden.total_orden}</td>
                                        <td>
                                            <span class="badge ${badgeClass}">${orden.estado}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ url('compras/crear-desde-orden') }}/${orden.id_orden_compra}"
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Procesar
                                            </a>
                                        </td>
                                    </tr>
                                `;
                            }
                        });
                    }

                    if (html === '') {
                        html = `
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No hay órdenes pendientes o aprobadas disponibles
                                </td>
                            </tr>
                        `;
                    }
                    $('#listaOrdenes').html(html);
                },
                error: function(xhr) {
                    $('#listaOrdenes').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                Error al cargar las órdenes. Intente nuevamente.
                            </td>
                        </tr>
                    `);
                }
            });
        }
    });
</script>
@endpush
