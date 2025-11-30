@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1><i class="fas fa-robot text-info"></i> Revisar Pedido Procesado con IA</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <!-- Review and Edit Form -->
    <div class="card">
        <div class="card-header bg-success">
            <h3 class="card-title">Revisar y Confirmar Datos Extraídos</h3>
        </div>
        <div class="card-body">
            {!! Form::open(['route' => 'pedidos.storeAi', 'id' => 'form-pedido-ai']) !!}

            <!-- Alert for total mismatch -->
            <div id="alertTotalMismatch" class="alert alert-warning" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Advertencia:</strong> <span id="alertMessage"></span>
            </div>

            <div class="row">
                <!-- Cliente Info -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('id_cliente', 'Cliente:') !!}
                        <div class="input-group">
                            {!! Form::select('id_cliente', $clientes, null, [
                                'class' => 'form-control',
                                'id' => 'id_cliente',
                                'required',
                                'placeholder' => 'Seleccione un cliente',
                            ]) !!}
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btnNewCliente">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('fecha_pedido', 'Fecha Pedido:') !!}
                        {!! Form::date('fecha_pedido', \Carbon\Carbon::now()->format('Y-m-d'), [
                            'class' => 'form-control',
                            'id' => 'fecha_pedido',
                            'required',
                        ]) !!}
                    </div>
                </div>

                <!-- User -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('usuario', 'Usuario/Responsable:') !!}
                        {!! Form::text('user_name', $usuario, ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>

                <!-- Sucursal -->
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('id_sucursal', 'Sucursal:') !!}
                        {!! Form::select('id_sucursal', $sucursales, null, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => 'Seleccione una sucursal',
                        ]) !!}
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Productos Detectados</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="btnAgregarProducto">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Products not found -->
                    <div id="productosNoEncontrados" style="display: none;">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-circle"></i> Productos No Encontrados</h5>
                            <p>Los siguientes productos no se encontraron en el sistema. Por favor, busque y asocie manualmente:</p>
                            <div id="listaProductosNoEncontrados"></div>
                        </div>
                    </div>

                    <!-- Products table -->
                    <div class="table-responsive">
                        <table class="table table-bordered item-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Precio Unit.</th>
                                    <th class="text-center">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="selectedProducts">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="card card-secondary mt-3">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right" id="subtotal_sin_impuestos_display">0 Gs.</td>
                                </tr>
                                <tr id="row_iva_5" style="display: none;">
                                    <td>IVA 5%:</td>
                                    <td class="text-right" id="total_iva_5_display">0 Gs.</td>
                                </tr>
                                <tr id="row_iva_10" style="display: none;">
                                    <td>IVA 10%:</td>
                                    <td class="text-right" id="total_iva_10_display">0 Gs.</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong style="font-size: 1.1em;">Total:</strong></td>
                                    <td class="text-right"><strong style="font-size: 1.1em;" id="total_con_impuestos_display">0 Gs.</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields for tax totals -->
            {!! Form::hidden('subtotal_sin_impuestos', 0, ['id' => 'subtotal_sin_impuestos']) !!}
            {!! Form::hidden('total_iva_5', 0, ['id' => 'total_iva_5']) !!}
            {!! Form::hidden('total_iva_10', 0, ['id' => 'total_iva_10']) !!}
            {!! Form::hidden('total_con_impuestos', 0, ['id' => 'total_con_impuestos']) !!}

            <div class="card-footer">
                <div class="card-footer">
                    {!! Form::submit('Confirmar y Crear Pedido', ['class' => 'btn btn-success']) !!}
                    <a href="{{ route('pedidos.createWithAi') }}" class="btn btn-default">Volver a Cargar PDF</a>
                </div>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>

<!-- Modal for Product Search (for unmapped products) -->
@includeIf('pedidos.modal_producto')

<!-- Modal for New Cliente -->
@includeIf('pedidos.modal_cliente_ai')

@endsection

@push('scripts')
<script src="{{ asset('js/pedidos_ai.js') }}"></script>
<script>
$(document).ready(function() {
    // Pre-populate form with data from session
    let data = @json($data);

    // Call populateFormWithAiData function that's in pedidos_ai.js
    if (typeof populateFormWithAiData === 'function') {
        populateFormWithAiData(data);
    }

    // Botón para agregar producto manualmente
    $('#btnAgregarProducto').on('click', function() {
        // Validar que haya una sucursal seleccionada
        let idSucursal = $('#id_sucursal').val();
        if (!idSucursal || idSucursal === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Sucursal Requerida',
                text: 'Debe seleccionar una sucursal antes de buscar productos.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        $('#productSearchModal').modal('show');
        // Limpiar búsqueda anterior
        $('#productSearchQuery').val('');
        $('#modalResults').html('');
        
        // Cargar productos iniciales
        fetch("{{ url('buscar-productos-pedidos') }}?cod_suc=" + idSucursal)
            .then(response => response.text())
            .then(html => {
                const modalResults = document.getElementById('modalResults');
                if (modalResults) {
                    modalResults.innerHTML = html;
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // Actualizar búsqueda cuando cambie la sucursal
    $('#id_sucursal').on('change', function() {
        if ($('#productSearchModal').hasClass('show')) {
            let idSucursal = $(this).val();
            if (idSucursal) {
                fetch("{{ url('buscar-productos-pedidos') }}?cod_suc=" + idSucursal)
                    .then(response => response.text())
                    .then(html => {
                        const modalResults = document.getElementById('modalResults');
                        if (modalResults) {
                            modalResults.innerHTML = html;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
    });
});
</script>
@endpush
