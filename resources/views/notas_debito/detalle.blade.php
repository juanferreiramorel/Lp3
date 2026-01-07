<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Detalles</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Autocomplete Search -->
            <div class="col-12 mb-3">
                <label for="producto_search_input">Buscar Producto (Código o Descripción):</label>
                <div class="position-relative">
                    <input type="text" id="producto_search_input" class="form-control" placeholder="Escriba para buscar..." autocomplete="off">
                    <div id="search_results" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;">
                        <!-- Results will be populated here -->
                    </div>
                </div>
            </div>
            
            <div class="table-responsive col-12">
                <table class="table item-table detalle-venta">
                    <thead>
                        <tr>
                            <th style="width:10%;">#</th>
                            <th style="width:40%;min-width:200px;">Producto</th>
                            <th class="text-center" style="width:12%;">Cantidad</th>
                            <th class="text-center" style="width:18%;">Precio Unit</th>
                            <th class="text-center" style="width:18%;">Subtotal</th>
                            <th style="width:2%;"></th>
                        </tr>
                    </thead>
                    <tbody id="selectedProducts">
                        <!-- Dynamic rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
