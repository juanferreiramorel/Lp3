@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1><i class="fas fa-robot text-info"></i> Crear Pedido con IA</h1>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <!-- Upload PDF Form -->
    <div class="card">
        <div class="card-header bg-info">
            <h3 class="card-title">Cargar PDF de Orden de Compra del Cliente</h3>
        </div>
        <div class="card-body">
            {!! Form::open(['route' => 'pedidos.processAiPdf', 'id' => 'uploadPdfForm', 'enctype' => 'multipart/form-data']) !!}

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
            <a href="{{ route('pedidos.index') }}" class="btn btn-default">Cancelar</a>

            {!! Form::close() !!}

            <!-- Loading indicator -->
            <div id="loadingIndicator" style="display: none;" class="text-center mt-3">
                <div class="spinner-border text-info" role="status">
                    <span class="sr-only">Procesando...</span>
                </div>
                <p class="mt-2">Procesando PDF con IA, esto puede tomar unos momentos...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update file input label
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    // Show loading indicator on form submit
    $('#uploadPdfForm').on('submit', function() {
        $('#btnProcessPdf').prop('disabled', true);
        $('#loadingIndicator').show();
    });
});
</script>
@endpush
