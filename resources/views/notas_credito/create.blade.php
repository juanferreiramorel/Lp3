@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-12">
                    <h1>Crear Nota de Crédito</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        @include('adminlte-templates::common.errors')

        <div class="card">
            {!! Form::open(['route' => 'notas_credito.store']) !!}

            <div class="card-body">
                <div class="row">
                    <!-- Venta Reference (MANDATORY) -->
                    <div class="col-12 mb-3">
                        <label>Buscar Venta / Factura (Referencia):</label>
                        <div class="position-relative">
                            <input type="text" id="venta_search_input" class="form-control" placeholder="Buscar por Nro Factura o Cliente..." autocomplete="off">
                            <div id="venta_search_results" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;"></div>
                        </div>
                        <input type="hidden" name="id_venta" id="id_venta" required>
                    </div>

                    <!-- Clientes (Readonly, filled by Venta) -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('cliente_display', 'Cliente:') !!}
                        <input type="text" id="cliente_display" class="form-control" readonly placeholder="Seleccione una venta primero...">
                        <input type="hidden" name="id_cliente" id="id_cliente">
                    </div>

                    <!-- Fecha -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('fecha_emision', 'Fecha Emisión:') !!}
                        {!! Form::date('fecha_emision', \Carbon\Carbon::now()->format('Y-m-d'), [
                            'class' => 'form-control',
                            'required',
                        ]) !!}
                    </div>

                    <!-- Sucursal -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('id_sucursal', 'Sucursal:') !!}
                        {!! Form::select('id_sucursal', $sucursales, null, ['class' => 'form-control', 'required', 'id' => 'id_sucursal']) !!}
                    </div>

                    <!-- Timbrado -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('id_timbrado', 'Timbrado:') !!}
                        @if(count($timbrados_vigentes) > 0)
                            <select name="id_timbrado" id="id_timbrado" class="form-control" required>
                                <option value="">Seleccione un timbrado</option>
                                @foreach($timbrados_vigentes as $timbrado)
                                    <option value="{{ $timbrado->id_timbrado }}">
                                        {{ $timbrado->numero_timbrado }} (Vence: {{ $timbrado->fecha_vencimiento }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control is-invalid" value="No hay timbrados vigentes para NC. No puede crear una nota." disabled>
                            <div class="invalid-feedback d-block">
                                Debe gestionar un Timbrado vigente para Notas de Crédito antes de continuar.
                            </div>
                        @endif
                    </div>

                    <!-- Usuario -->
                    <div class="form-group col-sm-6">
                        {!! Form::label('user_id', 'Responsable:') !!}
                        {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
                    </div>

                </div>

                <div class="row">
                     <!-- Concepto Selector -->
                    <div class="form-group col-sm-4">
                        {!! Form::label('concepto', 'Concepto / Tipo:') !!}
                        {!! Form::select('concepto', ['DEVOLUCION' => 'Devolución (Retorno al Stock)', 'DESCUENTO' => 'Descuento (Solo Monetario)'], 'DEVOLUCION', ['class' => 'form-control', 'id' => 'concepto']) !!}
                    </div>

                    <!-- Motivo Text -->
                    <div class="form-group col-sm-8">
                        {!! Form::label('motivo', 'Motivo / Observación:') !!}
                        {!! Form::text('motivo', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el motivo...']) !!}
                    </div>
                </div>

                <hr>
                <h4>Detalle (Items a devolver o descontar)</h4>
                
                <div class="detalle-wrapper">
                    @include('notas_credito.detalle')
                </div>
                <style>
                    /* Hide the search input inside the included detail view */
                    .detalle-wrapper .position-relative, 
                    .detalle-wrapper label[for="producto_search_input"] {
                        display: none !important;
                    }
                </style>

                <!-- Formas de Pago Section (Added by Request) -->
                <hr>
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            Devolución / Forma de Pago (Salida de Dinero)
                            <span class="badge badge-info ml-2" style="font-size: 0.9rem;" title="Dinero en efectivo disponible para devolver">
                                <i class="fas fa-money-bill-wave"></i> Efectivo Disponible: Gs. {{ number_format($efectivo_disponible ?? 0, 0, ',', '.') }}
                            </span>
                        </h3>
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
                                        <th style="min-width: 100px;">Importe (Devolución)</th>
                                        <th class="d-none d-md-table-cell" style="min-width: 100px;">Nro. Voucher / Ref.</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="formas-pago-tbody">
                                    <!-- Rows added dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 row">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <strong>Total Nota Crédito: </strong>
                                <span id="lbl_total_general_pago" class="text-primary font-weight-bold">0</span> Gs.
                            </div>
                            <div class="col-md-6 col-12 text-md-right">
                                <strong>Total Devolución Asignada: </strong>
                                <span id="total-pagado" class="text-success font-weight-bold">0</span> Gs.
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="alert alert-info" id="diferencia-alert" style="display: none;">
                                <strong>Diferencia (Saldo a Favor Cliente): </strong>
                                <span id="diferencia-monto" class="font-weight-bold"></span> Gs.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totales Hidden -->
                {!! Form::hidden('total', 0, ['id' => 'total']) !!}

                     <div class="col-md-12 mt-3">
                        @include('components.tax_totals')
                     </div>

            </div>

            <div class="card-footer">
                @if(count($timbrados_vigentes) > 0)
                    {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                @else
                    <button type="button" class="btn btn-danger" disabled>Sin Timbrado Vigente</button>
                    <span class="text-danger ml-2"><i class="fas fa-exclamation-triangle"></i> Contacte al administrador.</span>
                @endif
                <a href="{{ route('notas_credito.index') }}" class="btn btn-default">Cancelar</a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
    
    @include('ventas.modal_producto')
    
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
            // Price usually includes Tax in this system logic
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
            
            // Accumulate Global Total
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
        $('#lbl_total_general_pago').text(window.formatMoney(total));
        $('#total_display').text(window.formatMoney(total) + ' Gs.');
        
        // Update Tax Component Labels
        $('#lbl_total_exenta').text(window.formatMoney(totalExenta));
        
        $('#lbl_base_5').text(window.formatMoney(base5));
        $('#lbl_total_5').text(window.formatMoney(iva5)); // IVA 5% label
        
        $('#lbl_base_10').text(window.formatMoney(base10));
        $('#lbl_total_10').text(window.formatMoney(iva10)); // IVA 10% label
        
        $('#lbl_total_iva').text(window.formatMoney(totalIva));
        $('#lbl_total_general_footer').text(window.formatMoney(total));

        window.actualizarTotalesPago();
    };

    window.actualizarTotalesPago = function() {
        let totalPagado = 0;
        let totalEfectivo = 0;
        const efectivoDisponible = {{ $efectivo_disponible ?? 0 }};

        $('#formas-pago-tbody tr').each(function() {
            const row = $(this);
            const monto = parseInt(row.find('.importe-pago-input').val().replace(/\./g, '')) || 0;
            const metodo = row.find('select').val();

            totalPagado += monto;
            
            // Asumimos ID 1 es Efectivo. Si cambia, esto debe actualizarse.
            if (metodo == '1') {
                totalEfectivo += monto;
            }
        });

        if (totalEfectivo > efectivoDisponible) {
            Swal.fire({
                icon: 'error',
                title: 'Saldo Insuficiente',
                text: 'El monto a devolver en Efectivo (' + window.formatMoney(totalEfectivo) + ') supera el saldo disponible en caja (' + window.formatMoney(efectivoDisponible) + ').',
                toast: true,
                position: 'center'
            });
        }

        let totalNota = parseInt($('#total').val()) || 0;

        $('#total-pagado').text(window.formatMoney(totalPagado));

        // Calcular diferencia (Nota - Devolución Asignada)
        // Si la nota es de 100.000 y devuelvo 100.000 efectivo, diferencia 0.
        // Si devuelvo 0, diferencia 100.000 (Saldo a Favor Cliente en cuenta).
        let diferencia = totalNota - totalPagado;
        const $alert = $('#diferencia-alert');

        let htmlContent = '';
        let alertClass = '';

        if (totalNota > 0) {
            if (diferencia > 0) {
                // Hay un saldo positivo a favor del cliente q no se devolvió en Cash
                alertClass = 'alert-info';
                htmlContent = '<strong>Saldo a crédito (Cta Cte Cliente): </strong> ' + window.formatMoney(diferencia) + ' Gs.';
            } else if (diferencia < 0) {
                // Se está devolviendo MÁS de lo que vale la nota
                alertClass = 'alert-danger'; 
                htmlContent = '<strong>Error: Excede monto de la Nota: </strong> ' + window.formatMoney(Math.abs(diferencia)) + ' Gs.';
            } else {
                alertClass = 'alert-success';
                htmlContent = '<strong>Devolución Total Completa</strong>';
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
        // --- SALE SEARCH LOGIC ---
        let ventaSearchTimeout;
        const $ventaResults = $('#venta_search_results');
        const $ventaInput = $('#venta_search_input');

        $ventaInput.on('keyup', function() {
            const query = $(this).val();
            clearTimeout(ventaSearchTimeout);

            if (query.length < 2) {
                $ventaResults.hide().empty();
                return;
            }

            ventaSearchTimeout = setTimeout(() => {
                fetch(`{{ route('notas_credito.buscar_ventas') }}?term=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) throw new Error("Error en red");
                        return response.json();
                    })
                    .then(data => {
                        $ventaResults.empty();
                        if (data.results.length > 0) {
                            data.results.forEach(venta => {
                                $ventaResults.append(`
                                    <a href="#" class="list-group-item list-group-item-action venta-item" 
                                       data-id="${venta.id}" 
                                       data-factura="${venta.factura}" 
                                       data-cliente="${venta.cliente}"
                                       data-idcliente="${venta.id_cliente}"
                                       data-idsucursal="${venta.id_sucursal}"
                                       data-saldo="${venta.saldo_disponible}"
                                       data-saldoraw="${venta.saldo_raw}">
                                        <strong>Factura: ${venta.factura}</strong> - ${venta.cliente} <br>
                                        <span class="text-muted">Fecha: ${venta.fecha} | Total: ${venta.total} Gs. | <strong class="text-info">Saldo: ${venta.saldo_disponible} Gs.</strong></span>
                                    </a>
                                `);
                            });
                            $ventaResults.show();
                        } else {
                            $ventaResults.append('<div class="list-group-item">No se encontraron ventas</div>');
                            $ventaResults.show();
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });

        $(document).on('click', '.venta-item', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const factura = $(this).data('factura');
            const cliente = $(this).data('cliente');
            const idCliente = $(this).data('idcliente');
            const idSucursal = $(this).data('idsucursal');
            const saldo = $(this).data('saldo');
            const saldoRaw = parseFloat($(this).data('saldoraw')) || 0;

            if (saldoRaw <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Saldo',
                    text: 'Esta factura no tiene saldo disponible para realizar notas de crédito.'
                });
            }

            $('#id_venta').val(id);
            $('#venta_search_input').val(factura + ' - ' + cliente);
            $('#cliente_display').val(cliente + ' (Saldo Dispon: ' + saldo + ' Gs.)');
            $('#id_cliente').val(idCliente);
            
            // Auto-select sucursal and lock it (visually)
            $('#id_sucursal').val(idSucursal).css('pointer-events','none').css('background','#e9ecef');
            
            $ventaResults.hide().empty();
            
            
            // Auto-load details
            loadDetallesVenta(id);
        });

        // Hide search on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#venta_search_input, #venta_search_results').length) {
                $ventaResults.hide();
            }
        });


        // --- PRODUCT SEARCH LOGIC ---
        // Using notas_debito route as it is generic for product search
        let searchTimeout;
        const $searchResults = $('#search_results');
        const $searchInput = $('#producto_search_input');

        function performProductSearch(query, autoSelect = false) {
             const sucursal = $('#id_sucursal').val();
             if (query.length < 2) {
                $searchResults.hide().empty();
                return;
             }

             // REUSING notas_debito route
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
                                    <span class="text-muted">Precio: ${window.formatMoney(product.precio)} Gs. ${product.stock_msg}</span>
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

            // For Credit Notes (Returns), we don't necessarily care about current stock being > 0 to accept a return,
            // but we might want to check if we sold it?
            // Usually, standard check is fine. If I return something, I add to stock.
            // So 'controlar' (checking if stock exists to SELL) is irrelevant for returning.
            // I will COMMENT OUT the stock check for NC.
            /*
            if (controlar && stock <= 0) {
                alert('El producto no cuenta con stock disponible en esta sucursal (Warning).');
                // continue anyway for NC? Or block? Usually NC implies "Client brings item", so it physically exists.
            }
            */

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

        // --- LOAD DETAILS FUNCTION ---
        function loadDetallesVenta(idVenta) {
             $('#selectedProducts').empty();
             window.calcularTotal();

             fetch(`{{ url('notas_credito/detalles-venta') }}/${idVenta}`)
                .then(res => res.json())
                .then(data => {
                     if(data.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'La venta seleccionada no tiene items o no se pudieron cargar.'
                        });
                        return;
                    }
                    
                    data.forEach(item => {
                        let subtotal = item.precio * item.cantidad;
                        
                        let montoIvaRow = 0;
                        if (item.porcentaje_tax > 0) {
                             montoIvaRow = subtotal - (subtotal / (1 + (item.porcentaje_tax / 100)));
                        }

                        const newRow = `
                        <tr>
                            <td class="text-center">
                                <input class="text-center form-control form-control-sm" type="text" name="codigo[]" readonly style="text-align: center" value="${item.id_producto}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="producto[]" readonly value="${item.descripcion} - ${item.codigo_barras}">
                                <input type="hidden" name="id_producto[]" value="${item.id_producto}">
                                <input type="hidden" name="tax_porcentaje[]" value="${item.porcentaje_tax}">
                            </td>
                            <td class="text-center">
                                <input class="text-center form-control form-control-sm cantidad-input" type="number" 
                                       min="1" max="${item.cantidad}" 
                                       name="cantidad[]" style="text-align: center" value="${item.cantidad}">
                                <small class="text-muted">Max: ${item.cantidad}</small>
                            </td>
                            <td class="text-center">
                                <input class="text-center form-control form-control-sm precio-input" type="text" readonly
                                       name="precio[]" style="text-align: center; background-color: #eee;" 
                                       value="${window.formatMoney(item.precio)}">
                            </td>
                             <td class="text-center">
                                 <input class="text-center form-control form-control-sm tax-percent-input" type="text" readonly value="${item.porcentaje_tax}%" style="text-align: center; background-color: #eee;">
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
                    
                    window.calcularTotal();
                    applyModeLogic(); // Apply current mode permissions
                    Swal.fire({
                        icon: 'info',
                        title: 'Items cargados',
                        text: 'Se han cargado los items de la factura.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                })
                .catch(err => console.error(err));
        }

        // Change price readonly status based on Concepto
        $('#concepto').change(function() {
            const isDiscount = $(this).val() === 'DESCUENTO';
            if (isDiscount) {
                $('.precio-input').prop('readonly', false).css('background-color', '#fff');
                Swal.fire({
                    icon: 'info',
                    title: 'Modo Descuento',
                    text: 'Puede editar el PRECIO para ajustar el monto a descontar.',
                    confirmButtonText: 'Entendido'
                });
            } else {
                $('.precio-input').prop('readonly', true).css('background-color', '#eee');
                // Could ideally reset prices to original if we stored them, but for now we rely on user or reload.
                // Or maybe just warn.
            }
        });

        // Trigger on load details (re-apply logic)
         function applyModeLogic() {
            const isDiscount = $('#concepto').val() === 'DESCUENTO';
             if (isDiscount) {
                $('.precio-input').prop('readonly', false).css('background-color', '#fff');
            } else {
                $('.precio-input').prop('readonly', true).css('background-color', '#eee');
            }
         }

        // --- GENERAL TABLE LOGIC ---

        $(document).on('click', '.remove-item', function() {
            const row = $(this).closest('tr');
            Swal.fire({
                title: '¿Quitar item?',
                text: "Esta acción removerá el item de la lista.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    row.remove();
                    window.calcularTotal();
                }
            });
        });

        $(document).on('change keyup', '.cantidad-input, .precio-input', function() {
             let row = $(this).closest('tr');
             let max = parseFloat(row.find('.cantidad-input').attr('max')) || 999999;
             let cant = parseFloat(row.find('.cantidad-input').val()) || 0;
             let precio = parseFloat(row.find('.precio-input').val().replace(/\./g, '')) || 0;
             
             // Enforce max quantity
             if (cant > max) {
                 Swal.fire({
                    icon: 'error',
                    title: 'Error de Cantidad',
                    text: 'No puede devolver más cantidad de la facturada original (' + max + ').'
                 });
                 cant = max;
                 row.find('.cantidad-input').val(cant);
             }

             // Enforce max price (Cannot discount more than original price) - Optional but good safety
             // Storing original price in data-original-price could be useful
             
             let subtotal = cant * precio;

             // Recalculate Row Tax Amount
             let taxPercent = parseFloat(row.find('input[name="tax_porcentaje[]"]').val()) || 0;
             let montoIvaRow = 0;
             if (taxPercent > 0) {
                  montoIvaRow = subtotal - (subtotal / (1 + (taxPercent / 100)));
             }

             row.find('.subtotal-input').val(window.formatMoney(subtotal));
             row.find('.tax-amount-input').val(window.formatMoney(montoIvaRow));

             window.calcularTotal();
        });

        // Note: No Payment Method Logic needed here as explained above. (OLD COMMENT)
        
        // --- PAYMENT METHODS LOGIC (Added) ---
        $('#agregar-pago').on('click', function() {
            let index = $('#formas-pago-tbody tr').length;
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
             $(this).closest('tr').remove();
             window.actualizarTotalesPago();
        });

        $(document).on('change blur', '.importe-pago-input, select[name^="pagos"]', function() {
            // Format only on blur/change of amount
            if ($(this).hasClass('importe-pago-input')) {
                const valor = parseInt($(this).val().replace(/\./g, '')) || 0;
                $(this).val(window.formatMoney(valor));
            }
            window.actualizarTotalesPago();
        });
    });
</script>
@endpush
