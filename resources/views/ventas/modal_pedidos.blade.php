<!-- Modal para seleccionar pedidos -->
<div class="modal fade" id="modalPedidos" tabindex="-1" role="dialog" aria-labelledby="modalPedidosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="modalPedidosLabel">
                    <i class="fas fa-file-invoice"></i> Seleccionar Pedido para Venta
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="buscarPedido" placeholder="Buscar por cliente o número de pedido...">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaPedidos">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="listaPedidos">
                            <!-- Los pedidos se cargarán aquí mediante AJAX -->
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
        // Cargar pedidos cuando se abre el modal
        $('#modalPedidos').on('show.bs.modal', function() {
            cargarPedidos();
        });

        // Buscar pedidos en tiempo real
        $('#buscarPedido').on('keyup', function() {
            let buscar = $(this).val().toLowerCase();
            $('#listaPedidos tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(buscar) > -1);
            });
        });

        // Función para cargar pedidos PENDIENTES
        function cargarPedidos() {
            $.ajax({
                url: '{{ route("pedidos.getPendientes") }}',
                method: 'GET',
                success: function(response) {
                    let html = '';
                    if (response.pedidos && response.pedidos.length > 0) {
                        response.pedidos.forEach(function(pedido) {
                            html += `
                                <tr>
                                    <td>${pedido.id_pedido}</td>
                                    <td>${pedido.cliente}</td>
                                    <td>${pedido.fecha_pedido}</td>
                                    <td class="text-right">${pedido.total_pedido}</td>
                                    <td>
                                        <span class="badge badge-warning">${pedido.estado}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('ventas/crear-desde-pedido') }}/${pedido.id_pedido}"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Procesar
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html = `
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No hay pedidos pendientes disponibles
                                </td>
                            </tr>
                        `;
                    }
                    $('#listaPedidos').html(html);
                },
                error: function(xhr) {
                    $('#listaPedidos').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                Error al cargar los pedidos. Intente nuevamente.
                            </td>
                        </tr>
                    `);
                }
            });
        }
    });
</script>
@endpush
