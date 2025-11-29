@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1><i class="fas fa-robot text-info"></i> Crear Orden de Compra con IA</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <!-- Step 1: Upload PDF -->
    <div class="card" id="step1Card">
        <div class="card-header bg-info">
            <h3 class="card-title">Paso 1: Cargar PDF de Orden de Compra</h3>
        </div>
        <div class="card-body">
            <form id="uploadPdfForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="pdf_file">Seleccionar PDF:</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                        <label class="custom-file-label" for="pdf_file">Elegir archivo...</label>
                    </div>
                    <small class="form-text text-muted">Tamaño máximo: 10MB</small>
                </div>
                <button type="submit" class="btn btn-primary" id="btnProcessPdf">
                    <i class="fas fa-magic"></i> Procesar con IA
                </button>
                <a href="{{ route('ordencompra.index') }}" class="btn btn-default">Cancelar</a>
            </form>

            <!-- Loading indicator -->
            <div id="loadingIndicator" style="display: none;" class="text-center mt-3">
                <div class="spinner-border text-info" role="status">
                    <span class="sr-only">Procesando...</span>
                </div>
                <p class="mt-2">Procesando PDF con IA, esto puede tomar unos momentos...</p>
            </div>
        </div>
    </div>

    <!-- Step 2: Review and Edit Form (Hidden initially) -->
    <div class="card" id="step2Card" style="display: none;">
        <div class="card-header bg-success">
            <h3 class="card-title">Paso 2: Revisar y Confirmar Datos Extraídos</h3>
        </div>
        <div class="card-body">
            {!! Form::open(['route' => 'ordencompra.storeAi', 'id' => 'form-ordencompra-ai']) !!}

            <!-- Alert for total mismatch -->
            <div id="alertTotalMismatch" class="alert alert-warning" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Advertencia:</strong> <span id="alertMessage"></span>
            </div>

            <div class="row">
                <!-- Provider Info -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('id_proveedor', 'Proveedor:') !!}
                        <div class="input-group">
                            {!! Form::select('id_proveedor', $proveedores, null, [
                                'class' => 'form-control',
                                'id' => 'id_proveedor',
                                'required',
                                'placeholder' => 'Seleccione un proveedor',
                            ]) !!}
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btnNewProvider">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('fecha_orden', 'Fecha Orden:') !!}
                        {!! Form::date('fecha_orden', \Carbon\Carbon::now()->format('Y-m-d'), [
                            'class' => 'form-control',
                            'id' => 'fecha_orden',
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
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('id_sucursal', 'Sucursal:') !!}
                        {!! Form::select('id_sucursal', $sucursales, null, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => 'Seleccione una sucursal',
                        ]) !!}
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('observaciones', 'Observaciones:') !!}
                        {!! Form::textarea('observaciones', null, [
                            'class' => 'form-control',
                            'rows' => 2,
                        ]) !!}
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Productos Detectados</h3>
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

            <div class="card-footer">
                {!! Form::submit('Confirmar y Crear Orden de Compra', ['class' => 'btn btn-success']) !!}
                <button type="button" class="btn btn-default" id="btnBackToUpload">Volver a Cargar PDF</button>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
</div>

<!-- Modal for Product Search (for unmapped products) -->
@includeIf('ordencompra.modal_producto')

<!-- Modal for New Provider -->
@includeIf('ordencompra.modal_proveedor_ai')

@endsection

@push('scripts')
<script src="{{ asset('js/ordencompra_ai.js') }}"></script>
@endpush
