<!-- Modal para crear nuevo proveedor -->
<div class="modal fade" id="modalNuevoProveedor" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-truck"></i> Nuevo Proveedor
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNuevoProveedor">
                    @csrf
                    <div class="form-group">
                        <label for="proveedor_descripcion">Descripción / Razón Social *</label>
                        <input type="text" class="form-control" id="proveedor_descripcion" name="descripcion" required>
                        <span class="text-danger error-descripcion"></span>
                    </div>

                    <div class="form-group">
                        <label for="proveedor_ruc">RUC</label>
                        <input type="text" class="form-control" id="proveedor_ruc" name="ruc">
                        <span class="text-danger error-ruc"></span>
                    </div>

                    <div class="form-group">
                        <label for="proveedor_telefono">Teléfono *</label>
                        <input type="text" class="form-control" id="proveedor_telefono" name="telefono" required>
                        <span class="text-danger error-telefono"></span>
                    </div>

                    <div class="form-group">
                        <label for="proveedor_direccion">Dirección</label>
                        <textarea class="form-control" id="proveedor_direccion" name="direccion" rows="2"></textarea>
                        <span class="text-danger error-direccion"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarProveedor">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>
