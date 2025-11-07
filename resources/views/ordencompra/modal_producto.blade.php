<!-- Modal de búsqueda de productos -->
<div class="modal fade" id="productSearchModal" tabindex="-1" role="dialog" aria-labelledby="productSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productSearchModalLabel">Buscar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" id="productSearchQuery" class="form-control" placeholder="Buscar por código o descripción...">
                </div>
                <div id="modalResults">
                    <!-- Los resultados se cargarán aquí vía AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #modalResults table tr:hover {
        cursor: pointer;
        background-color: #f5f5f5;
    }
</style>

@push('scripts')
<script>
    // Búsqueda de productos en tiempo real
    document.getElementById('productSearchQuery').addEventListener('keyup', function() {
        let query = this.value;
        fetch('{{ url('buscar-productos-oc') }}?query=' + encodeURIComponent(query))
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Función para seleccionar producto (llamada desde body_producto.blade.php)
    function seleccionarProducto(codigo, producto, precio) {
        // Verificar si ya existe en la tabla
        let filas = document.getElementById('selectedProducts').getElementsByTagName('tr');

        for (let i = 0; i < filas.length; i++) {
            let codigoExistente = filas[i].querySelector('input[name="codigo[]"]').value;
            if (codigoExistente === codigo) {
                alert('El producto ya fue agregado.');
                return;
            }
        }

        // Formatear precio
        let precioFormateado = formatMoney(parseFloat(precio));

        // Crear nueva fila
        let row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="text" name="codigo[]" class="form-control text-center" value="${codigo}" readonly>
            </td>
            <td>
                <input type="text" name="producto[]" class="form-control" value="${producto}" readonly>
            </td>
            <td>
                <input type="number" name="cantidad[]" class="form-control text-center cantidad-input" value="1" min="1">
            </td>
            <td>
                <input type="text" name="precio[]" class="form-control text-center precio-input" value="${precioFormateado}">
            </td>
            <td>
                <input type="text" name="subtotal[]" class="form-control text-center subtotal-input" value="${precioFormateado}" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        document.getElementById('selectedProducts').appendChild(row);
        calcularTotal();
        $('#productSearchModal').modal('hide');

        // Limpiar búsqueda
        document.getElementById('productSearchQuery').value = '';
    }
</script>
@endpush
