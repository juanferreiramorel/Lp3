$(document).ready(function() {
    let aiResponseData = null;

    // Update file input label
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    // Upload and process PDF
    $('#uploadPdfForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        $('#btnProcessPdf').prop('disabled', true);
        $('#loadingIndicator').show();

        $.ajax({
            url: '/ordencompra/procesar-ai-pdf',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    aiResponseData = response.data;
                    populateFormWithAiData(response.data);
                    $('#step1Card').hide();
                    $('#step2Card').show();
                    $('html, body').animate({ scrollTop: 0 }, 300);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Error al procesar el PDF';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error', message, 'error');
            },
            complete: function() {
                $('#btnProcessPdf').prop('disabled', false);
                $('#loadingIndicator').hide();
            }
        });
    });

    // Populate form with AI data
    function populateFormWithAiData(data) {
        // Set date
        if (data.cabecera.fecha_oc) {
            $('#fecha_orden').val(data.cabecera.fecha_oc);
        }

        // Set provider
        if (data.proveedor) {
            $('#id_proveedor').val(data.proveedor.id_proveedor);
        } else if (data.cabecera.ruc) {
            // Provider not found - pre-fill modal
            $('#proveedor_ruc').val(data.cabecera.ruc);
            $('#proveedor_descripcion').val(data.cabecera.empresa || '');

            Swal.fire({
                title: 'Proveedor No Encontrado',
                text: 'No se encontró un proveedor con el RUC ' + data.cabecera.ruc + '. Por favor, créelo o selecciónelo manualmente.',
                icon: 'warning',
                confirmButtonText: 'Crear Proveedor',
                showCancelButton: true,
                cancelButtonText: 'Seleccionar Manualmente'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#modalNuevoProveedor').modal('show');
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

        // Show AI warnings
        if (data.controles && data.controles.advertencias && data.controles.advertencias.length > 0) {
            let warnings = data.controles.advertencias.join(', ');
            let currentObs = $('#observaciones').val() || '';
            $('#observaciones').val(currentObs + (currentObs ? '\n' : '') + 'Advertencias de IA: ' + warnings);
        }

        calcularTotal();
    }

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

        $('#subtotal_sin_impuestos_display').text(formatMoney(Math.round(subtotal_sin_impuestos)) + ' Gs.');
        $('#total_iva_5_display').text(formatMoney(Math.round(total_iva_5)) + ' Gs.');
        $('#total_iva_10_display').text(formatMoney(Math.round(total_iva_10)) + ' Gs.');
        $('#total_con_impuestos_display').text(formatMoney(Math.round(total_con_impuestos)) + ' Gs.');

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

    // New provider button
    $('#btnNewProvider').on('click', function() {
        $('#modalNuevoProveedor').modal('show');
    });

    // Save new provider
    $('#btnGuardarProveedor').on('click', function() {
        let formData = $('#formNuevoProveedor').serialize();

        // Clear previous errors
        $('.text-danger').text('');

        $.ajax({
            url: '/ordencompra/proveedor-ajax',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Add to select
                    let provText = response.proveedor.descripcion;
                    if (response.proveedor.ruc) {
                        provText += ' - ' + response.proveedor.ruc;
                    }
                    let option = new Option(
                        provText,
                        response.proveedor.id_proveedor,
                        true,
                        true
                    );
                    $('#id_proveedor').append(option).trigger('change');

                    $('#modalNuevoProveedor').modal('hide');
                    $('#formNuevoProveedor')[0].reset();

                    Swal.fire('Éxito', 'Proveedor creado correctamente', 'success');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        $('.error-' + field).text(errors[field][0]);
                    }
                }
            }
        });
    });

    // Back to upload
    $('#btnBackToUpload').on('click', function() {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se perderán los datos extraídos del PDF',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, volver',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
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

    // Override seleccionarProducto for unmapped products
    let originalSeleccionarProducto = window.seleccionarProducto;
    if (typeof originalSeleccionarProducto === 'function') {
        window.seleccionarProducto = function(codigo, producto, precio, tax_porcentaje) {
            if (window.currentUnmappedProduct) {
                agregarProductoATabla(
                    codigo,
                    producto,
                    window.currentUnmappedProduct.precio || precio,
                    tax_porcentaje,
                    window.currentUnmappedProduct.cantidad
                );
                window.currentUnmappedProduct = null;
            } else {
                originalSeleccionarProducto(codigo, producto, precio, tax_porcentaje);
            }
            $('#productSearchModal').modal('hide');
        };
    }
});
