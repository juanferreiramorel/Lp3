<!-- Modal para crear nuevo cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Nuevo Cliente
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente">
                    @csrf
                    <div class="form-group">
                        <label for="cliente_nombre">Nombre *</label>
                        <input type="text" class="form-control" id="cliente_nombre" name="clie_nombre" required>
                        <span class="text-danger error-clie_nombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="cliente_apellido">Apellido *</label>
                        <input type="text" class="form-control" id="cliente_apellido" name="clie_apellido" required>
                        <span class="text-danger error-clie_apellido"></span>
                    </div>

                    <div class="form-group">
                        <label for="cliente_ci">CI / RUC</label>
                        <input type="text" class="form-control" id="cliente_ci" name="clie_ci">
                        <span class="text-danger error-clie_ci"></span>
                    </div>

                    <div class="form-group">
                        <label for="cliente_telefono">Teléfono *</label>
                        <input type="text" class="form-control" id="cliente_telefono" name="clie_telefono" required>
                        <span class="text-danger error-clie_telefono"></span>
                    </div>

                    <div class="form-group">
                        <label for="cliente_direccion">Dirección</label>
                        <textarea class="form-control" id="cliente_direccion" name="clie_direccion" rows="2"></textarea>
                        <span class="text-danger error-clie_direccion"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">
                    <i class="fas fa-save"></i> Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>
