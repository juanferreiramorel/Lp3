$(document).ready(function() {
    // Populate form with AI data
    window.populateFormWithAiData = function(data) {
        // Set date
        if (data.cabecera.fecha_oc) {
            $('#fecha_pedido').val(data.cabecera.fecha_oc);
        }

        // Set cliente
        if (data.cliente) {
            $('#id_cliente').val(data.cliente.id_cliente);
        } else if (data.cabecera.ruc) {
            // Cliente not found - pre-fill modal
            $('#cliente_ci').val(data.cabecera.ruc);
            if (data.cabecera.empresa) {
                let nombreCompleto = data.cabecera.empresa.split(' ');
                $('#cliente_nombre').val(nombreCompleto[0] || '');
                $('#cliente_apellido').val(nombreCompleto.slice(1).join(' ') || '');
            }

            Swal.fire({
                title: 'Cliente No Encontrado',
                text: 'No se encontró un cliente con el RUC ' + data.cabecera.ruc + '. Por favor, créelo o selecciónelo manualmente.',
                icon: 'warning',
                confirmButtonText: 'Crear Cliente',
                showCancelButton: true,
                cancelButtonText: 'Seleccionar Manualmente'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#modalNuevoCliente').modal('show');
                }
            });
        }

        // Populate products
        $('#selectedProducts').empty();
        data.productos_encontrados.forEach(function(item) {
            agregarProductoATabla(
                item.producto.id_producto,
                item.producto.descripcion,
                item.precio_ai || item.producto.precio,
                item.producto.tax_porcentaje || 0,
                item.cantidad
            );
        });

        // Show unmapped products
        if (data.productos_no_encontrados.length > 0) {
            mostrarProductosNoEncontrados(data.productos_no_encontrados);
        }

        // Show warning if totals don't match
        if (data.controles && !data.controles.coincide_total_vs_informado) {
            $('#alertMessage').text(
                'El total calculado no coincide con el total informado en el PDF. ' +
                'Por favor, verifique los datos antes de confirmar.'
            );
            $('#alertTotalMismatch').show();
        }

        calcularTotal();
    };

    // Show unmapped products
    function mostrarProductosNoEncontrados(productos) {
        let html = '<ul class="list-unstyled">';
        productos.forEach(function(item, index) {
            html += `
                <li class="mb-2">
                    <strong>${item.descripcion || 'Producto sin descripción'}</strong>
                    (Código: ${item.codigo_producto || 'N/A'},
                    Cant: ${item.cantidad || 0},
                    Precio: ${formatMoney(item.precio_unitario || 0)} Gs.)
                    <button type="button" class="btn btn-sm btn-primary ml-2"
                            onclick="buscarProductoManual(${index}, '${(item.descripcion || '').replace(/'/g, "\\'")}', ${item.cantidad}, ${item.precio_unitario})">
                        <i class="fas fa-search"></i> Buscar Producto
                    </button>
                </li>
            `;
        });
        html += '</ul>';
        $('#listaProductosNoEncontrados').html(html);
        $('#productosNoEncontrados').show();
    }

    // Add product to table
    function agregarProductoATabla(codigo, descripcion, precio, taxPorcentaje, cantidad) {
        // Check if already exists
        let existe = false;
        $('#selectedProducts tr').each(function() {
            if ($(this).find('input[name="codigo[]"]').val() == codigo) {
                existe = true;
                return false;
            }
        });

        if (existe) {
            Swal.fire('Info', 'El producto ya está en la lista', 'info');
            return;
        }

        let precioFormateado = formatMoney(parseFloat(precio));
        let subtotal = parseFloat(precio) * parseInt(cantidad);

        let row = `
            <tr>
                <td>
                    <input type="text" name="codigo[]" class="form-control text-center" value="${codigo}" readonly>
                </td>
                <td>
                    <input type="text" name="producto[]" class="form-control" value="${descripcion}" readonly>
                    <input type="hidden" name="tax_porcentaje[]" value="${taxPorcentaje}">
                </td>
                <td>
                    <input type="number" name="cantidad[]" class="form-control text-center cantidad-input"
                           value="${cantidad}" min="1">
                </td>
                <td>
                    <input type="text" name="precio[]" class="form-control text-center precio-input"
                           value="${precioFormateado}">
                </td>
                <td>
                    <input type="text" name="subtotal[]" class="form-control text-center subtotal-input"
                           value="${formatMoney(subtotal)}" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#selectedProducts').append(row);
        calcularTotal();
    }

    // Format money
    function formatMoney(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Clean number
    function cleanNumber(str) {
        return parseFloat(str.toString().replace(/\./g, '')) || 0;
    }

    // Calculate totals (same as existing)
    function calcularTotal() {
        let total = 0;
        $('.subtotal-input').each(function() {
            total += cleanNumber($(this).val());
        });
        calcularTotalesImpuestos();
    }

    function calcularTotalesImpuestos() {
        let subtotal_sin_impuestos = 0;
        let total_iva_5 = 0;
        let total_iva_10 = 0;
        let tiene_iva_5 = false;
        let tiene_iva_10 = false;

        $('#selectedProducts tr').each(function() {
            let subtotal_con_iva = cleanNumber($(this).find('.subtotal-input').val());
            let tax_porcentaje = parseFloat($(this).find('input[name="tax_porcentaje[]"]').val()) || 0;

            let precio_sin_impuesto = 0;
            let monto_impuesto = 0;

            if (tax_porcentaje > 0) {
                precio_sin_impuesto = subtotal_con_iva / (1 + (tax_porcentaje / 100));
                monto_impuesto = subtotal_con_iva - precio_sin_impuesto;
            } else {
                precio_sin_impuesto = subtotal_con_iva;
            }

            subtotal_sin_impuestos += precio_sin_impuesto;

            if (tax_porcentaje == 5) {
                total_iva_5 += monto_impuesto;
                tiene_iva_5 = true;
            } else if (tax_porcentaje == 10) {
                total_iva_10 += monto_impuesto;
                tiene_iva_10 = true;
            }
        });

        let total_con_impuestos = subtotal_sin_impuestos + total_iva_5 + total_iva_10;

        // Actualizar displays
        $('#subtotal_sin_impuestos_display').text(formatMoney(Math.round(subtotal_sin_impuestos)) + ' Gs.');
        $('#total_iva_5_display').text(formatMoney(Math.round(total_iva_5)) + ' Gs.');
        $('#total_iva_10_display').text(formatMoney(Math.round(total_iva_10)) + ' Gs.');
        $('#total_con_impuestos_display').text(formatMoney(Math.round(total_con_impuestos)) + ' Gs.');

        // Actualizar campos hidden para enviar al servidor
        $('#subtotal_sin_impuestos').val(Math.round(subtotal_sin_impuestos));
        $('#total_iva_5').val(Math.round(total_iva_5));
        $('#total_iva_10').val(Math.round(total_iva_10));
        $('#total_con_impuestos').val(Math.round(total_con_impuestos));

        if (tiene_iva_5) {
            $('#row_iva_5').show();
        } else {
            $('#row_iva_5').hide();
        }

        if (tiene_iva_10) {
            $('#row_iva_10').show();
        } else {
            $('#row_iva_10').hide();
        }
    }

    // Quantity/price changes
    $(document).on('change keyup', '.cantidad-input, .precio-input', function() {
        let row = $(this).closest('tr');
        let cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
        let precio = cleanNumber(row.find('.precio-input').val());
        let subtotal = cantidad * precio;
        row.find('.subtotal-input').val(formatMoney(subtotal));
        row.find('.precio-input').val(formatMoney(precio));
        calcularTotal();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calcularTotal();
    });

    // New cliente button
    $('#btnNewCliente').on('click', function() {
        $('#modalNuevoCliente').modal('show');
    });

    // Save new cliente
    $('#btnGuardarCliente').on('click', function() {
        let formData = $('#formNuevoCliente').serialize();

        // Clear previous errors
        $('.text-danger').text('');

        // Deshabilitar botón mientras procesa
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        let btn = $(this);

        $.ajax({
            url: '/pedidos/cliente-ajax',
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Add to select
                    let clienteText = response.cliente.cliente;
                    if (response.cliente.ruc) {
                        clienteText += ' - ' + response.cliente.ruc;
                    }
                    let option = new Option(
                        clienteText,
                        response.cliente.id_cliente,
                        true,
                        true
                    );
                    $('#id_cliente').append(option).trigger('change');

                    $('#modalNuevoCliente').modal('hide');
                    $('#formNuevoCliente')[0].reset();

                    Swal.fire('Éxito', 'Cliente creado correctamente', 'success');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        $('.error-' + field).text(errors[field][0]);
                    }
                } else {
                    Swal.fire('Error', 'Error al crear el cliente: ' + (xhr.responseJSON?.message || 'Error desconocido'), 'error');
                }
            },
            complete: function() {
                // Rehabilitar botón
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cliente');
            }
        });
    });

    // Expose function globally for product search
    window.agregarProductoATabla = agregarProductoATabla;
    window.buscarProductoManual = function(index, descripcion, cantidad, precio) {
        // Store context for later use
        window.currentUnmappedProduct = { index, descripcion, cantidad, precio };
        $('#productSearchModal').modal('show');

        // Search with description
        $('#productSearchQuery').val(descripcion).trigger('keyup');
    };
});
