@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-12">
                    <h1>Crear Nota de Débito</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        @include('adminlte-templates::common.errors')

        <div class="card">
            {!! Form::open(['route' => 'notas_debito.store']) !!}

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
                        {!! Form::select('id_sucursal', $sucursales, null, ['class' => 'form-control', 'required']) !!}
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
                                    <tr>
                                        <td>
                                            {!! Form::select('pagos[0][id_metodo_pago]', $metodo_pagos, null, [
                                                'class' => 'form-control form-control-sm',
                                                'required',
                                                'placeholder' => 'Seleccione...'
                                            ]) !!}
                                        </td>
                                        <td>
                                            <input type="text" name="pagos[0][monto]" class="form-control form-control-sm importe-pago-input text-right"
                                                value="0" required>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <input type="text" name="pagos[0][nro_voucher]" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-pago">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
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
                {!! Form::hidden('total', 0, ['id' => 'total']) !!}

                <div class="row mt-3">
                     <div class="col-md-8"></div>
                     <div class="col-md-4">
                        <table class="table table-sm table-borderless">
                             <tr>
                                 <td><strong>Total General:</strong></td>
                                 <td class="text-right"><strong id="total_display">0 Gs.</strong></td>
                             </tr>
                        </table>
                     </div>
                </div>

            </div>

            <div class="card-footer">
                {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('notas_debito.index') }}" class="btn btn-default">Cancelar</a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
    
    @include('ventas.modal_producto')
    
    <!-- define hidden field for detail data logic if reused from venta scripts -->
    <input type="hidden" id="detalle_data" name="detalle_data">

@endsection

@push('scripts')
<script>
    // --- GLOBAL FUNCTIONS ---

    // Define globally so they can be accessed anywhere
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
        $('.subtotal-input').each(function() {
            total += parseFloat($(this).val().replace(/\./g, '')) || 0;
        });
        $('#total').val(total);
        $('#lbl_total_general').text(window.formatMoney(total));
        $('#total_display').text(window.formatMoney(total) + ' Gs.');
        
        window.actualizarTotalesPago();
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
                fetch(`{{ route('notas_debito.buscar_ventas') }}?term=${encodeURIComponent(query)}`)
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
                                       data-idsucursal="${venta.id_sucursal}">
                                        <strong>Factura: ${venta.factura}</strong> - ${venta.cliente} <br>
                                        <span class="text-muted">Fecha: ${venta.fecha} | Total: ${venta.total} Gs.</span>
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

            $('#id_venta').val(id);
            $('#venta_search_input').val(factura + ' - ' + cliente);
            $('#cliente_display').val(cliente);
            $('#id_cliente').val(idCliente);
            
            // Auto-select sucursal and lock it (visually)
            $('#id_sucursal').val(idSucursal).css('pointer-events','none').css('background','#e9ecef');
            
            $ventaResults.hide().empty();
        });

        // Hide search on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#venta_search_input, #venta_search_results').length) {
                $ventaResults.hide();
            }
        });


        // --- PRODUCT SEARCH LOGIC ---
        // (Keep existing product search logic here, but verify route works now)
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

            // Add row
            const newRow = `
            <tr>
                <td class="text-center">
                    <input class="text-center form-control" type="text" name="codigo[]" readonly style="text-align: center" value="${id}">
                </td>
                <td>
                    <input type="text" class="form-control" name="producto[]" readonly value="${desc} - ${codbarra}">
                    <input type="hidden" name="id_producto[]" value="${id}">
                    <input type="hidden" name="tax_porcentaje[]" value="${iva}">
                </td>
                <td class="text-center">
                    <input class="text-center form-control cantidad-input" type="number" min="1" name="cantidad[]" style="text-align: center" value="1">
                </td>
                <td class="text-center">
                    <input class="text-center form-control precio-input" type="text" name="precio[]" style="text-align: center" value="${window.formatMoney(price)}">
                </td>
                <td class="text-center">
                    <input class="text-center form-control subtotal-input" type="text" name="subtotal[]" readonly value="${window.formatMoney(price)}" style="text-align: center">
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
             row.find('.subtotal-input').val(window.formatMoney(cant * precio));
             window.calcularTotal();
        });

        // --- PAYMENT METHODS LOGIC ---

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
