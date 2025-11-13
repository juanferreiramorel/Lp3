<!-- Modal -->
<div class="modal fade" id="productSearchModal" tabindex="-1" role="dialog" aria-labelledby="productSearchModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productSearchModalLabel">Buscar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario de búsqueda -->
                <div class="form-group">
                    <input type="text" id="productSearchQuery" class="form-control" placeholder="Buscar por código o nombre..." autofocus>
                </div>
                <!-- Aquí se mostrará la tabla con los resultados -->
                <div id="modalResults" class="table-responsive">
                    <!-- resultado de la busqueda -->
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
    }

    /* Responsive modal */
    @media (max-width: 576px) {
        .modal-dialog {
            max-width: 100%;
            margin: 0.5rem;
        }

        #modalResults table {
            font-size: 14px;
        }

        #modalResults table th,
        #modalResults table td {
            padding: 0.5rem 0.25rem;
            font-size: 13px;
        }

        .modal-title {
            font-size: 1.1rem;
        }
    }
</style>

<!-- Script para manejar la búsqueda -->
@push('scripts')
<script>
    // Evento para buscar productos al teclear en el input
    document.getElementById('productSearchQuery').addEventListener('keyup', function() {
        let query = this.value;
        fetch('{{ url('buscar-productos-recibos') }}?query=' + query + '&cod_suc=' + $("#id_sucursal").val())
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Seleccionar producto (cantidad por defecto 1, subtotal igual a precio)
    function seleccionarProducto(codigo, producto, precio, subtotal = precio, cantidad = 1) {
        // Obtener todas las filas de la tabla
        let filas = document.getElementById('selectedProducts').getElementsByTagName('tr');

        // Verificar si el producto ya ha sido agregado
        for (let i = 0; i < filas.length; i++) {
            let codigoExistente = filas[i].querySelector('input[name="codigo[]"]').value;
            if (codigoExistente === codigo) {
                alert('El producto ya fue agregado.');
                return;
            }
        }

        // Crear una nueva fila para el producto seleccionado
        let row = document.createElement('tr');

        row.innerHTML = `
            <td class="d-none d-md-table-cell">
                <input type="text" name="codigo[]" class="form-control form-control-sm" value="${codigo}" readonly>
            </td>
            <td>
                <input type="text" name="producto[]" class="form-control form-control-sm" value="${producto}">
                <input type="hidden" name="codigo[]" value="${codigo}">
            </td>
            <td><input type="number" name="cantidad[]" class="text-center form-control form-control-sm cantidad-input" value="${cantidad}" min="1"></td>
            <td class="d-none d-sm-table-cell"><input type="text" name="precio[]" class="text-center form-control form-control-sm precio-input" value="${formatMoney(precio, 0)}"></td>
            <td><input type="text" name="subtotal[]" class="text-center form-control form-control-sm subtotal-input" value="${formatMoney(subtotal, 0)}" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="far fa-trash-alt"></i></button></td>
        `;

        // Agregar la fila a la tabla
        document.getElementById('selectedProducts').appendChild(row);

        // Calcular el total general
        calcularTotal();

        // Cerrar el modal
        $('#productSearchModal').modal('hide');
    }

    // Eliminar producto
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calcularTotal();
    });

    // Calcular subtotal al cambiar cantidad
    $(document).on('input', '.cantidad-input', function() {
        let row = $(this).closest('tr');
        let precio = parseInt(row.find('.precio-input').val().replace(/\./g, '')) || 0;
        let cantidad = parseInt($(this).val()) || 0;
        let subtotal = precio * cantidad;
        row.find('.subtotal-input').val(formatMoney(subtotal));
        calcularTotal();
    });

    // Formatear precio al perder foco y recalcular subtotal
    $(document).on('blur', '.precio-input', function() {
        let valor = parseInt($(this).val().replace(/\./g, '')) || 0;
        $(this).val(formatMoney(valor));

        let row = $(this).closest('tr');
        let cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
        let subtotal = valor * cantidad;
        row.find('.subtotal-input').val(formatMoney(subtotal));
        calcularTotal();
    });

    // Calcular total general
    function calcularTotal() {
        let total = 0;
        $('.subtotal-input').each(function() {
            total += parseInt($(this).val().replace(/\./g, '')) || 0;
        });
        $('#total').val(formatMoney(total));

        // Actualizar el total en formas de pago
        if (typeof actualizarTotalPago === 'function') {
            actualizarTotalPago(total);
        }
    }

    // Formatear números con separador de miles
    function formatMoney(n, c, d, t) {
        let s, i, j;
        c = isNaN(c = Math.abs(c)) ? 0 : c;
        d = d === undefined ? "," : d;
        t = t === undefined ? "." : t;
        s = n < 0 ? "-" : "";
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c)));
        j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) +
            (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    }
</script>
@endpush
