<!-- Modal -->
<div class="modal fade" id="productSearchModal" tabindex="-1" role="dialog" aria-labelledby="productSearchModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productSearchModalLabel">Buscar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario de búsqueda -->
                <input type="text" id="productSearchQuery"  class="form-control" placeholder="Buscar...">
                <br>
                <!-- Aquí se mostrará la tabla con los resultados -->
                <div id="modalResults">
                    <!-- resultado de la busqueda del body_producto.blade.php -->
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    /* Cambiar el cursor a una mano cuando el mouse esté sobre una fila */
    #modalResults table tr:hover {
        cursor: pointer;
        background-color: #f5f5f5;
        /* Cambia el color de fondo al pasar el mouse */
    }
</style>

<!-- Script para manejar la búsqueda -->
<!-- SECCION JAVASCRIPT -->
@push('scripts')
<script>
        // Evento para buscar productos al teclear en el input
        const productSearchInput = document.getElementById('productSearchQuery');
        if (productSearchInput) {
            productSearchInput.addEventListener('keyup', function() {
                // Validar que haya una sucursal seleccionada
                const idSucursal = $("#id_sucursal").val();
                if (!idSucursal || idSucursal === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sucursal Requerida',
                        text: 'Debe seleccionar una sucursal antes de buscar productos.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }

                const query = encodeURIComponent(this.value || '');
                fetch("{{ url('buscar-productos-pedidos') }}?query=" + query + "&cod_suc=" + idSucursal)
                    .then(response => response.text())
                    .then(html => {
                        const modalResults = document.getElementById('modalResults');
                        if (modalResults) {
                            modalResults.innerHTML = html;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }

        // Funciones simplificadas que usan las globales de fields.blade.php
        function formatMoneyPedido(value) {
            // Usar la función global formatMoney de fields.blade.php
            if (typeof formatMoney === 'function') {
                return formatMoney(value, 0);
            }
            // Fallback si no está disponible
            const sanitizedValue = value === undefined || value === null ? 0 : value;
            const parsed = parseInt(sanitizedValue.toString().replace(/[^\d-]/g, ''), 10) || 0;
            return parsed.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Función para seleccionar producto (similar a ventas)
        function seleccionarProducto(codigo, producto, precio, tax_porcentaje = 0) {
            // Si existe la función agregarProductoATabla (vista de IA), usarla
            if (typeof window.agregarProductoATabla === 'function') {
                // Verificar si hay un producto no encontrado pendiente
                if (window.currentUnmappedProduct) {
                    window.agregarProductoATabla(
                        codigo,
                        producto,
                        window.currentUnmappedProduct.precio || precio,
                        tax_porcentaje || 0,
                        window.currentUnmappedProduct.cantidad || 1
                    );
                    window.currentUnmappedProduct = null;
                } else {
                    // Agregar producto normal desde el modal
                    window.agregarProductoATabla(
                        codigo,
                        producto,
                        precio,
                        tax_porcentaje || 0,
                        1
                    );
                }
                $('#productSearchModal').modal('hide');
                return;
            }

            // Código original para vista normal de pedidos
            const tabla = document.getElementById('selectedProducts');
            if (!tabla) {
                return;
            }

            // Verificar si el producto ya fue agregado
            const filas = tabla.getElementsByTagName('tr');
            for (let i = 0; i < filas.length; i++) {
                const codigoExistente = filas[i].querySelector('input[name="codigo[]"]');
                if (codigoExistente && codigoExistente.value === codigo) {
                    alert('El producto ya fue agregado.');
                    return;
                }
            }

            // Asegurar que tax_porcentaje sea un número válido (0-100)
            // Si viene como precio (mayor a 100), corregirlo a 0
            let taxPorcentajeFinal = parseFloat(tax_porcentaje);
            // Validar que sea un número válido y esté en el rango correcto (0-100)
            if (isNaN(taxPorcentajeFinal)) {
                taxPorcentajeFinal = 0;
            } else if (taxPorcentajeFinal > 100) {
                // Si el valor es mayor a 100, probablemente es un precio, no un porcentaje
                console.warn('tax_porcentaje parece ser un precio (' + taxPorcentajeFinal + '), estableciendo a 0');
                taxPorcentajeFinal = 0;
            } else if (taxPorcentajeFinal < 0) {
                taxPorcentajeFinal = 0;
            }

            // Calcular subtotal inicial (precio * cantidad = precio * 1)
            // El precio viene como número entero desde PHP (ej: 88280)
            const cantidad = 1;
            const subtotal = precio * cantidad;

            // Crear la fila
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center"><input type="text" name="codigo[]" class="form-control text-center" value="${codigo}" readonly></td>
                <td>
                    <input type="text" name="producto[]" class="form-control" value="${producto}" readonly>
                    <input type="hidden" name="tax_porcentaje[]" value="${taxPorcentajeFinal}">
                </td>
                <td class="text-center"><input type="number" name="cantidad[]" class="text-center form-control cantidad-input" value="${cantidad}" min="1"></td>
                <td class="text-center"><input type="text" name="precio[]" class="text-center form-control precio-input" value="${formatMoneyPedido(precio)}"></td>
                <td class="text-center"><input type="text" name="subtotal[]" class="text-center form-control subtotal-input" value="${formatMoneyPedido(subtotal)}" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="far fa-trash-alt"></i></button></td>
            `;

            // Agregar la fila a la tabla
            tabla.appendChild(row);

            // Calcular el total general
            if (typeof window.pedidosCalcularTotal === 'function') {
                window.pedidosCalcularTotal();
            }

            // Cerrar el modal
            $('#productSearchModal').modal('hide');
        }
        
        // Exponer función globalmente
        window.seleccionarProducto = seleccionarProducto;

        // Función para eliminar producto (ya no se usa, se maneja con event listener en fields.blade.php)
        function borrar(button) {
            // Esta función ya no se usa, se maneja con el event listener en fields.blade.php
            // Se mantiene por compatibilidad pero no hace nada
        }

</script>
@endpush
