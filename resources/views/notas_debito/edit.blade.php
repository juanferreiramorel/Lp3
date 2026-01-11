@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-12">
                    <h1>Editar Nota de Débito</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        @include('adminlte-templates::common.errors')

        <div class="card">
            {!! Form::model($nota, ['route' => ['notas_debito.update', $nota->id_nota_debito], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    <!-- Venta Reference (READONLY IN EDIT) -->
                    <div class="col-12 mb-3">
                        <label>Venta / Factura (Referencia):</label>
                        <input type="text" class="form-control" readonly 
                               value="{{ $nota->venta->factura_nro }} - {{ $nota->venta->cliente->clie_nombre ?? '' }} {{ $nota->venta->cliente->clie_apellido ?? '' }}">
                        <input type="hidden" name="id_venta" id="id_venta" value="{{ $nota->id_venta }}">
                    </div>

                    <!-- Clientes (Readonly) -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('cliente_display', 'Cliente:') !!}
                        <input type="text" id="cliente_display" class="form-control" readonly 
                               value="{{ $nota->cliente->clie_nombre }} {{ $nota->cliente->clie_apellido }}">
                        <input type="hidden" name="id_cliente" id="id_cliente" value="{{ $nota->id_cliente }}">
                    </div>

                    <!-- Fecha -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('fecha_emision', 'Fecha Emisión:') !!}
                        {!! Form::date('fecha_emision', \Carbon\Carbon::parse($nota->fecha_emision)->format('Y-m-d'), [
                            'class' => 'form-control',
                            'required',
                        ]) !!}
                    </div>

                    <!-- Sucursal -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('id_sucursal', 'Sucursal:') !!}
                        {!! Form::select('id_sucursal', $sucursales, null, ['class' => 'form-control', 'required', 'readonly' => 'readonly', 'style' => 'pointer-events: none; background-color: #e9ecef;']) !!}
                    </div>

                    <!-- Timbrado -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('id_timbrado', 'Timbrado:') !!}
                        <input type="hidden" name="id_timbrado" value="{{ $nota->id_timbrado }}">
                        @if(count($timbrados_vigentes) > 0)
                            <select id="id_timbrado" class="form-control" disabled style="background-color: #e9ecef;">
                                <option value="">Seleccione un timbrado</option>
                                @foreach($timbrados_vigentes as $timbrado)
                                    <option value="{{ $timbrado->id_timbrado }}" {{ $nota->id_timbrado == $timbrado->id_timbrado ? 'selected' : '' }}>
                                        {{ $timbrado->numero_timbrado }} (Vence: {{ $timbrado->fecha_vencimiento }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control is-invalid" value="No hay timbrados vigentes" disabled>
                        @endif
                    </div>

                    <!-- Usuario -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('user_id', 'Responsable:') !!}
                        {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
                    </div>

                    <!-- Motivo -->
                    <div class="form-group col-sm-12">
                        {!! Form::label('motivo', 'Motivo:') !!}
                        {!! Form::text('motivo', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el motivo de la nota de débito']) !!}
                    </div>

                </div>

                <hr>
                <h4>Detalle</h4>
                
                @include('notas_debito.detalle')

                <!-- Cobro Section -->
                <hr>
                
                <!-- Formas de Pago Section -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Formas de Pago</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-success btn-block d-sm-inline" id="agregar-pago">
                                <i class="fas fa-plus"></i><span class="d-none d-sm-inline"> Agregar Forma de Pago</span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th style="min-width: 150px;">Método de Pago</th>
                                        <th style="min-width: 100px;">Importe</th>
                                        <th class="d-none d-md-table-cell" style="min-width: 100px;">Nro. Voucher</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="formas-pago-tbody">
                                    {{-- Pagos will be populated by JS --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 row">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <strong>Total General: </strong>
                                <span id="lbl_total_general" class="text-primary font-weight-bold">0</span> Gs.
                            </div>
                            <div class="col-md-6 col-12 text-md-right">
                                <strong>Total Pagado: </strong>
                                <span id="total-pagado" class="text-success font-weight-bold">0</span> Gs.
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="alert alert-info" id="diferencia-alert" style="display: none;">
                                <strong>Diferencia: </strong>
                                <span id="diferencia-monto" class="font-weight-bold"></span> Gs.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totales Hidden -->
                {!! Form::hidden('total', $nota->total, ['id' => 'total']) !!}

                <div class="col-md-12 mt-3">
                     @include('components.tax_totals')
                </div>

            </div>

            <div class="card-footer">
                {!! Form::submit('Guardar Cambios', ['class' => 'btn btn-primary', 'id' => 'btn-submit-nota']) !!}
                <a href="{{ route('notas_debito.index') }}" class="btn btn-default">Cancelar</a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
    
    @include('ventas.modal_producto')
    
    <!-- define hidden field for detail data logic -->
    <input type="hidden" id="detalle_data" name="detalle_data">

@endsection

@push('scripts')
<script>
    // --- GLOBAL FUNCTIONS ---

    window.formatMoney = function(n, c, d, t) {
        var c = isNaN(c = Math.abs(c)) ? 0 : c,
            d = d == undefined ? "," : d,
            t = t == undefined ? "." : t,
            s = n < 0 ? "-" : "",
            i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
            j = (j = i.length) > 3 ? j % 3 : 0;

        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    };

    window.cleanNumber = function(val) {
        if (!val) return 0;
        return parseFloat(val.toString().replace(/\./g, '').replace(',', '.'));
    };

        window.calcularTotal = function() {
            let total = 0;
            let totalExenta = 0;
            
            let base5 = 0;
            let iva5 = 0;
            
            let base10 = 0;
            let iva10 = 0;

            $('#selectedProducts tr').each(function() {
                let row = $(this);
                let cant = parseFloat(row.find('.cantidad-input').val()) || 0;
                let precio = parseFloat(row.find('.precio-input').val().replace(/\./g, '')) || 0;
                let taxPercent = parseFloat(row.find('input[name="tax_porcentaje[]"]').val()) || 0;

                let subtotal = cant * precio;
                
                // Calculate Tax Part
                let precioSinIva = 0;
                let montoIva = 0;

                if (taxPercent > 0) {
                     precioSinIva = subtotal / (1 + (taxPercent / 100));
                     montoIva = subtotal - precioSinIva;
                } else {
                     precioSinIva = subtotal;
                }
                
                // Accumulate
                total += subtotal;

                if (taxPercent == 0) {
                    totalExenta += subtotal;
                } else if (taxPercent == 5) {
                    base5 += precioSinIva;
                    iva5 += montoIva; 
                } else if (taxPercent == 10) {
                    base10 += precioSinIva;
                    iva10 += montoIva; 
                }
            });

            const totalIva = iva5 + iva10;

            $('#total').val(total);
            $('#lbl_total_general').text(window.formatMoney(total));
            $('#total_display').text(window.formatMoney(total) + ' Gs.');
            
            // Update Tax Component Labels
            $('#lbl_total_exenta').text(window.formatMoney(totalExenta));
            
            $('#lbl_base_5').text(window.formatMoney(base5));
            $('#lbl_total_5').text(window.formatMoney(iva5)); 
            
            $('#lbl_base_10').text(window.formatMoney(base10));
            $('#lbl_total_10').text(window.formatMoney(iva10)); 
            
            $('#lbl_total_iva').text(window.formatMoney(totalIva));
            $('#lbl_total_general_footer').text(window.formatMoney(total));
            
            // Validate
            window.validarTotalFijo(total);
        };

    window.actualizarTotalesPago = function() {
        let totalPagado = 0;
        $('.importe-pago-input').each(function() {
            const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
            totalPagado += valor;
        });

        let totalProductos = parseInt($('#total').val()) || 0;

        $('#total-pagado').text(window.formatMoney(totalPagado));

        // Calcular diferencia
        let diferencia = totalProductos - totalPagado;
        const $alert = $('#diferencia-alert');

        if (totalProductos > 0) {
            let htmlContent = '';
            let alertClass = '';

            if (diferencia > 0) {
                alertClass = 'alert-warning';
                htmlContent = '<strong>Falta pagar: </strong> ' + window.formatMoney(diferencia) + ' Gs.';
            } else if (diferencia < 0) {
                alertClass = 'alert-success'; 
                htmlContent = '<strong>Excedente: </strong> ' + window.formatMoney(Math.abs(diferencia)) + ' Gs.';
            } else {
                alertClass = 'alert-success';
                htmlContent = '<strong>Completo!</strong>';
            }

            $alert.removeClass('alert-info alert-success alert-warning alert-danger')
                  .addClass(alertClass)
                  .html(htmlContent)
                  .show();
        } else {
            $alert.hide();
        }
    };

    $(document).ready(function() {
        const ORIGINAL_TOTAL = parseFloat("{{ $nota->total }}") || 0;

        // --- GLOBAL FUNCTIONS OVERRIDE FOR VALIDATION ---
        // Store original function reference if needed, or just redefine
        
        window.validarTotalFijo = function(currentTotal) {
            const $submitBtn = $('#btn-submit-nota');
            const $alert = $('#diferencia-alert');
            const diff = currentTotal - ORIGINAL_TOTAL;

            if (Math.abs(diff) > 0) {
                // Mismatch
                $submitBtn.prop('disabled', true);
                $submitBtn.text(`Total Incorrecto (Dif: ${window.formatMoney(diff)})`);
                $submitBtn.removeClass('btn-primary').addClass('btn-danger');

                // Show alert
                 let msg = `<strong>Atención:</strong> El monto total debe mantenerse en <strong>${window.formatMoney(ORIGINAL_TOTAL)} Gs.</strong> para preservar la validez fiscal. <br> Diferencia actual: ${window.formatMoney(diff)} Gs.`;
                 $alert.removeClass('alert-success alert-info').addClass('alert-danger').html(msg).show();
            } else {
                // Match
                $submitBtn.prop('disabled', false);
                $submitBtn.text('Guardar Cambios');
                $submitBtn.removeClass('btn-danger').addClass('btn-primary');
                
                // Check payments logic (Standard validation)
                window.actualizarTotalesPago(); 
            }
        };

        // Redefine calcularTotal to include validation
        window.calcularTotal = function() {
            let total = 0;
            $('.subtotal-input').each(function() {
                total += parseFloat($(this).val().replace(/\./g, '')) || 0;
            });
            $('#total').val(total);
            $('#lbl_total_general').text(window.formatMoney(total));
            $('#total_display').text(window.formatMoney(total) + ' Gs.');
            
            // Validate against original total
            window.validarTotalFijo(total);
        };
        
        // Redefine actualizarTotalesPago for fixed total context
        window.actualizarTotalesPago = function() {
             let totalPagado = 0;
            $('.importe-pago-input').each(function() {
                const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
                totalPagado += valor;
            });

            $('#total-pagado').text(window.formatMoney(totalPagado));

            // We compare against ORIGINAL TOTAL effectively, since current must match original
            let totalProductos = ORIGINAL_TOTAL; 
            let diferencia = totalProductos - totalPagado;
            const $alert = $('#diferencia-alert');

            // Only show payment alert if Total Validation passed (otherwise the error alert takes precedence)
            const currentTotalCalc = parseFloat($('#total').val()) || 0;
            if (Math.abs(currentTotalCalc - ORIGINAL_TOTAL) > 0) {
                return; // Let validacionTotalFijo handle the alert
            }

            if (totalProductos > 0) {
                let htmlContent = '';
                let alertClass = '';

                if (diferencia > 0) {
                    alertClass = 'alert-warning';
                    htmlContent = '<strong>Falta cubrir: </strong> ' + window.formatMoney(diferencia) + ' Gs. en formas de pago.';
                } else if (diferencia < 0) {
                    alertClass = 'alert-warning'; // Warning because payments should match exact total too ideally, or just show surplus
                    htmlContent = '<strong>Excedente en pagos: </strong> ' + window.formatMoney(Math.abs(diferencia)) + ' Gs.';
                } else {
                    alertClass = 'alert-success';
                    htmlContent = '<strong>Totales Correctos.</strong> El monto coincide con el original y los pagos cubren el total.';
                }

                $alert.removeClass('alert-info alert-success alert-warning alert-danger')
                      .addClass(alertClass)
                      .html(htmlContent)
                      .show();
            }
        };

        // Redefine calcularTotal to include validation and new Tax Component logic
        window.calcularTotal = function() {
            let total = 0;
            let totalExenta = 0;
            
            let base5 = 0;
            let iva5 = 0;
            
            let base10 = 0;
            let iva10 = 0;

            $('#selectedProducts tr').each(function() {
                let row = $(this);
                let cant = parseFloat(row.find('.cantidad-input').val()) || 0;
                let precio = parseFloat(row.find('.precio-input').val().replace(/\./g, '')) || 0;
                let taxPercent = parseFloat(row.find('input[name="tax_porcentaje[]"]').val()) || 0;

                let subtotal = cant * precio;
                
                // Calculate Tax Part
                let precioSinIva = 0;
                let montoIva = 0;

                if (taxPercent > 0) {
                     precioSinIva = subtotal / (1 + (taxPercent / 100));
                     montoIva = subtotal - precioSinIva;
                } else {
                     precioSinIva = subtotal;
                }
                
                // Accumulate
                total += subtotal;

                if (taxPercent == 0) {
                    totalExenta += subtotal;
                } else if (taxPercent == 5) {
                    base5 += precioSinIva;
                    iva5 += montoIva; 
                } else if (taxPercent == 10) {
                    base10 += precioSinIva;
                    iva10 += montoIva; 
                }
            });

            const totalIva = iva5 + iva10;

            $('#total').val(total);
            $('#lbl_total_general').text(window.formatMoney(total));
            $('#total_display').text(window.formatMoney(total) + ' Gs.');
            
            // Update Tax Component Labels
            $('#lbl_total_exenta').text(window.formatMoney(totalExenta));
            
            $('#lbl_base_5').text(window.formatMoney(base5));
            $('#lbl_total_5').text(window.formatMoney(iva5)); 
            
            $('#lbl_base_10').text(window.formatMoney(base10));
            $('#lbl_total_10').text(window.formatMoney(iva10)); 
            
            $('#lbl_total_iva').text(window.formatMoney(totalIva));
            $('#lbl_total_general_footer').text(window.formatMoney(total));
            
            // Validate
            window.validarTotalFijo(total);
        };

        // --- PRE-POPULATE DATA FOR EDIT ---
        
        // 1. Populate Products
        const existingDetalles = @json($nota->detalles);
        if (existingDetalles.length > 0) {
            existingDetalles.forEach(detalle => {
                const product = detalle.producto;
                // Determine barcode (codigo_barras or id)
                const codBarra = product.codigo_barras || product.id_producto;
                
                const precio = parseFloat(detalle.precio);
                const subtotal = detalle.cantidad * precio;
                
                // Tax Calculation for row
                const taxPercent = parseFloat(detalle.tax_porcentaje ?? 10);
                let montoIvaRow = 0;
                if (taxPercent > 0) {
                    montoIvaRow = subtotal - (subtotal / (1 + (taxPercent / 100)));
                }

                // Add row via logic similar to product search selection
                const newRow = `
                <tr>
                    <td class="text-center">
                        <input class="text-center form-control form-control-sm" type="text" name="codigo[]" readonly style="text-align: center" value="${product.id_producto}">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="producto[]" readonly value="${product.descripcion} - ${codBarra}">
                        <input type="hidden" name="id_producto[]" value="${product.id_producto}">
                        <input type="hidden" name="tax_porcentaje[]" value="${taxPercent}">
                    </td>
                    <td class="text-center">
                        <input class="text-center form-control form-control-sm cantidad-input" type="number" min="1" name="cantidad[]" style="text-align: center" value="${detalle.cantidad}">
                    </td>
                    <td class="text-center">
                        <input class="text-center form-control form-control-sm precio-input" type="text" name="precio[]" style="text-align: center" value="${window.formatMoney(precio)}">
                    </td>
                    <td class="text-center">
                         <input class="text-center form-control form-control-sm tax-percent-input" type="text" readonly value="${taxPercent}%" style="text-align: center; background-color: #eee;">
                    </td>
                    <td class="text-center">
                         <input class="text-center form-control form-control-sm tax-amount-input" type="text" readonly value="${window.formatMoney(montoIvaRow)}" style="text-align: center; background-color: #eee;">
                    </td>
                    <td class="text-center">
                        <input class="text-center form-control form-control-sm subtotal-input" type="text" name="subtotal[]" readonly value="${window.formatMoney(subtotal)}" style="text-align: center">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
                $('#selectedProducts').append(newRow);
            });
        }

        // 2. Populate Payments
        const existingPagos = @json($nota->cobros);
        if (existingPagos.length > 0) {
            existingPagos.forEach((pago, index) => {
                const newRow = `
                <tr>
                    <td>
                        <select name="pagos[${index}][id_metodo_pago]" class="form-control form-control-sm" required>
                            <option value="">Seleccione...</option>
                            @foreach($metodo_pagos as $id => $descripcion)
                                <option value="{{ $id }}" ${pago.id_metodo_pago == {{ $id }} ? 'selected' : ''}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="pagos[${index}][monto]" class="form-control form-control-sm importe-pago-input text-right"
                            value="${window.formatMoney(pago.cobro_importe)}" required>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <input type="text" name="pagos[${index}][nro_voucher]" class="form-control form-control-sm" value="${pago.nro_voucher || ''}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-pago">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                `;
                $('#formas-pago-tbody').append(newRow);
            });
        } else {
             // If no payments found (weird but possible), add empty row
             $('#agregar-pago').click();
        }

        // Calculate totals initially
        window.calcularTotal();


        // --- PRODUCT SEARCH LOGIC ---
        let searchTimeout;
        const $searchResults = $('#search_results');
        const $searchInput = $('#producto_search_input');

        function performProductSearch(query, autoSelect = false) {
             const sucursal = $('#id_sucursal').val();
             if (query.length < 2) {
                $searchResults.hide().empty();
                return;
             }

             fetch(`{{ route('notas_debito.buscar_productos') }}?term=${encodeURIComponent(query)}&cod_suc=${sucursal}`)
                .then(response => response.json())
                .then(data => {
                    $searchResults.empty();
                    if (data.results.length > 0) {
                        data.results.forEach(product => {
                            $searchResults.append(`
                                <a href="#" class="list-group-item list-group-item-action product-item" 
                                   data-id="${product.id}" 
                                   data-codbarra="${product.cod_barra}"
                                   data-desc="${product.descripcion}" 
                                   data-price="${product.precio}"
                                   data-iva="${product.iva}"
                                   data-stock="${product.stock_cantidad}"
                                   data-controlar="${product.controlar_stock}">
                                    <strong>${product.cod_barra}</strong> - ${product.descripcion} <br>
                                    <span class="text-muted">Precio: ${window.formatMoney(product.precio)} Gs.${product.stock_msg}</span>
                                </a>
                            `);
                        });
                        $searchResults.show();

                        if (autoSelect) {
                            $searchResults.find('.product-item').first().click();
                        }
                    } else {
                        if (!autoSelect) {
                            $searchResults.append('<div class="list-group-item">No se encontraron productos</div>');
                            $searchResults.show();
                        }
                    }
                })
                .catch(err => console.error('Error fetching products:', err));
        }

        $searchInput.on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const query = $(this).val();
                performProductSearch(query, true);
            }
        });

        $searchInput.on('keyup', function(e) {
            if (e.which === 13) return; // Skip Enter key in keyup
            
            const query = $(this).val();
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                performProductSearch(query, false);
            }, 300);
        });

        // Handle Product Selection
        $(document).on('click', '.product-item', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const desc = $(this).data('desc');
            const codbarra = $(this).data('codbarra');
            const price = parseFloat($(this).data('price')) || 0;
            const iva = $(this).data('iva') || 10;
            const stock = parseFloat($(this).data('stock')) || 0;
            const controlar = $(this).data('controlar');

            if (controlar && stock <= 0) {
                alert('El producto no cuenta con stock disponible en esta sucursal.');
                return;
            }

            // Tax Calculation for new row
            let subtotal = price * 1;
            let montoIvaRow = 0;
            if (iva > 0) {
                 // Formula: Tax Amount = Total - (Total / (1 + rate))
                 montoIvaRow = subtotal - (subtotal / (1 + (iva / 100)));
            }

            // Add row
            const newRow = `
            <tr>
                <td class="text-center">
                    <input class="text-center form-control form-control-sm" type="text" name="codigo[]" readonly style="text-align: center" value="${id}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="producto[]" readonly value="${desc} - ${codbarra}">
                    <input type="hidden" name="id_producto[]" value="${id}">
                    <input type="hidden" name="tax_porcentaje[]" value="${iva}">
                </td>
                <td class="text-center">
                    <input class="text-center form-control form-control-sm cantidad-input" type="number" min="1" name="cantidad[]" style="text-align: center" value="1">
                </td>
                <td class="text-center">
                    <input class="text-center form-control form-control-sm precio-input" type="text" name="precio[]" style="text-align: center" value="${window.formatMoney(price)}">
                </td>
                <td class="text-center">
                     <input class="text-center form-control form-control-sm tax-percent-input" type="text" readonly value="${iva}%" style="text-align: center; background-color: #eee;">
                </td>
                <td class="text-center">
                     <input class="text-center form-control form-control-sm tax-amount-input" type="text" readonly value="${window.formatMoney(montoIvaRow)}" style="text-align: center; background-color: #eee;">
                </td>
                <td class="text-center">
                    <input class="text-center form-control form-control-sm subtotal-input" type="text" name="subtotal[]" readonly value="${window.formatMoney(price)}" style="text-align: center">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
            
            $('#selectedProducts').append(newRow);
            window.calcularTotal();

            // Reset Search
            $searchInput.val('').focus();
            $searchResults.hide().empty();
        });

        // Close search list on click outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#producto_search_input, #search_results').length) {
                $searchResults.hide();
            }
        });

        // --- GENERAL TABLE LOGIC ---

        $(document).on('click', '.remove-item', function() {
            $(this).closest('tr').remove();
            window.calcularTotal();
        });

        $(document).on('change keyup', '.cantidad-input, .precio-input', function() {
             let row = $(this).closest('tr');
             let cant = parseFloat(row.find('.cantidad-input').val()) || 0;
             let precio = parseFloat(row.find('.precio-input').val().replace(/\./g, '')) || 0;
             let subtotal = cant * precio;
             
             // Recalculate Row Tax Amount
             let taxPercent = parseFloat(row.find('input[name="tax_porcentaje[]"]').val()) || 0;
             let montoIvaRow = 0;
             if (taxPercent > 0) {
                  montoIvaRow = subtotal - (subtotal / (1 + (taxPercent / 100)));
             }
             
             row.find('.subtotal-input').val(window.formatMoney(subtotal));
             row.find('.tax-amount-input').val(window.formatMoney(montoIvaRow)); // Update tax amount display

             window.calcularTotal();
        });

        // --- PAYMENT METHODS LOGIC ---

        $('#agregar-pago').on('click', function() {
            let index = $('#formas-pago-tbody tr').length + 100; // avoid conflicts with existing indices
            const newRow = `
                <tr>
                    <td>
                        <select name="pagos[${index}][id_metodo_pago]" class="form-control form-control-sm" required>
                            <option value="">Seleccione...</option>
                            @foreach($metodo_pagos as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="pagos[${index}][monto]" class="form-control form-control-sm importe-pago-input text-right"
                            value="0" required>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <input type="text" name="pagos[${index}][nro_voucher]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-pago">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#formas-pago-tbody').append(newRow);
        });

        $(document).on('click', '.remove-pago', function() {
            if ($('#formas-pago-tbody tr').length > 1) {
                $(this).closest('tr').remove();
                window.actualizarTotalesPago();
            } else {
                alert('Debe haber al menos una forma de pago');
            }
        });

        $(document).on('blur', '.importe-pago-input', function() {
            const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
            $(this).val(window.formatMoney(valor));
            window.actualizarTotalesPago();
        });
    });
</script>
@endpush
