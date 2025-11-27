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
        // Evento para buscar productos al teclear en el input y consultar la funcion buscar productos
        document.getElementById('productSearchQuery').addEventListener('keyup', function() {
            let query = this.value;
            fetch('{{ url('buscar-productoscompras') }}?query=' + query + '&cod_suc=' + $("#id_sucursal").val())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalResults').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        // Función para formatear números con separador de miles (formato paraguayo: punto para miles)
        function formatMoney(n) {
            return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function seleccionarProducto(codigo, producto, precio, subtotal = precio, cantidad = 1, tax_porcentaje = 0) {
            // Obtener todas las filas de la tabla que fue seleccionado
            let filas = document.getElementById('selectedProducts').getElementsByTagName('tr');

            // Verificar si el producto ya ha sido agregado en el detalle de la tabla html para validar
            for (let i = 0; i < filas.length; i++) {
                let codigoExistente = filas[i].querySelector('input[name="codigo[]"]').value;//capturar el valor del codigo del proudcto
                if (codigoExistente === codigo) {//verifica si el codigo del proudcto ya fue agregado
                    alert('El producto ya fue agregado.');
                    return; // Salir de la función si el producto ya existe
                }
            }

            // Crear una nueva fila para el producto seleccionado con inputs
            let row = document.createElement('tr');

            // Agregar los inputs a la fila con los valores obtenidos de la función seleccionarProducto
            // Se cambia el tipo de dato de number a tipo text para que no dé problemas en el separador de miles
            row.innerHTML = `
                <td class="text-center"><input type="text" name="codigo[]" class="form-control text-center" value="${codigo}" readonly></td>
                <td>
                    <input type="text" name="producto[]" class="form-control" value="${producto}" readonly>
                    <input type="hidden" name="tax_porcentaje[]" value="${tax_porcentaje}">
                </td>
                <td class="text-center"><input type="number" name="cantidad[]" class="text-center form-control cantidad-input" value="${cantidad}" min="1"></td>
                <td class="text-center"><input type="text" name="precio_unitario[]" class="text-center form-control precio-input" value="${formatMoney(precio)}"></td>
                <td class="text-center"><input type="text" name="subtotal[]" class="text-center form-control subtotal-input" value="${formatMoney(subtotal)}" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="far fa-trash-alt"></i></button></td>
            `;

            // Agregar la fila a la tabla de productos seleccionados
            document.getElementById('selectedProducts').appendChild(row);

            // Calcular el total general según el detalle cargado
            calcularTotal();

            // Cerrar el modal después de seleccionar el producto
            $('#productSearchModal').modal('hide');
        }


       
</script>
@endpush
