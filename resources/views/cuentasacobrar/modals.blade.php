<!-- Modal para mostrar el detalle de cuentas a cobrar -->
<div class="modal fade" id="modalDetalleCuentas" tabindex="-1" role="dialog" aria-labelledby="modalDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="modalDetalleLabel">Detalle de Cuentas a Cobrar - Factura <span id="modal-factura-nro"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loading-detalle" class="text-center" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando detalle...</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabla-detalle-cuentas">
                        <thead>
                            <tr>
                                <th>N° Cuenta</th>
                                <th>N° Cuota</th>
                                <th>Importe</th>
                                <th>Saldo</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Pagos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-detalle-cuentas">
                            <!-- Se llenará dinámicamente con AJAX -->
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

<!-- Modal para Cobrar Cuenta -->
<div class="modal fade" id="modalCobrarCuenta" tabindex="-1" role="dialog" aria-labelledby="modalCobrarLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="modalCobrarLabel">Cobrar Cuota</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-cobrar-cuenta">
                <div class="modal-body">
                    <input type="hidden" id="cobro-id-cta" name="id_cta">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>N° Cuenta:</label>
                                <input type="text" class="form-control" id="cobro-nro-cuenta" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>N° Cuota:</label>
                                <input type="text" class="form-control" id="cobro-nro-cuota" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Importe Total:</label>
                                <input type="text" class="form-control" id="cobro-importe-total" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Saldo Pendiente: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-danger font-weight-bold" id="cobro-saldo-pendiente-display" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vencimiento:</label>
                                <input type="text" class="form-control" id="cobro-vencimiento" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>Fecha de Cobro: <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="cobro-fecha" name="fecha_cobro" required>
                    </div>

                    <div class="form-group">
                        <label>Método de Pago: <span class="text-danger">*</span></label>
                        <select class="form-control" id="cobro-metodo-pago" name="id_metodo_pago" required>
                            <option value="">Seleccione un método de pago</option>
                            @if(isset($metodos_pago))
                                @foreach($metodos_pago as $id => $descripcion)
                                    <option value="{{ $id }}">{{ $descripcion }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Monto a Pagar: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-right" id="cobro-monto-pagar" name="monto"
                               placeholder="0" onkeyup="formatCobro(this)" required>
                        <small class="form-text text-muted">Puede ingresar un monto parcial o total</small>
                    </div>

                    <div class="form-group">
                        <label>N° Voucher:</label>
                        <input type="text" class="form-control" id="cobro-nro-voucher" name="nro_voucher"
                               placeholder="Número de comprobante (opcional)">
                    </div>

                    <div class="alert alert-info" id="cobro-saldo-info" style="display: none;">
                        <strong>Saldo Pendiente:</strong> <span id="cobro-saldo-pendiente">0</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn-confirmar-cobro">
                        <i class="fas fa-check"></i> Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Historial de Pagos -->
<div class="modal fade" id="modalHistorialPagos" tabindex="-1" role="dialog" aria-labelledby="modalHistorialLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="modalHistorialLabel">
                    Historial de Pagos - Cuenta N° <span id="historial-nro-cuenta"></span> - Cuota <span id="historial-nro-cuota"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loading-historial" class="text-center" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Cargando historial...</p>
                </div>
                <div id="sin-pagos-mensaje" class="alert alert-info" style="display: none;">
                    <i class="fas fa-info-circle"></i> No hay pagos registrados para esta cuota.
                </div>
                <div class="table-responsive" id="tabla-historial-container">
                    <table class="table table-bordered table-striped" id="tabla-historial-pagos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Método Pago</th>
                                <th>N° Voucher</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th width="150">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-historial-pagos">
                            <!-- Se llenará dinámicamente con AJAX -->
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
