@extends('layouts.app')

@push('third_party_stylesheets')
<style>
    .btn-ai-gradient {
        background: linear-gradient(135deg, #5cd0ff 0%, #1dd3a8 100%) !important;
        color: #0f172a !important;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        padding: 0.375rem 1rem; /* Standard Bootstrap padding */
        border-radius: 0.25rem; /* Standard Bootstrap radius to match others */
        box-shadow: 0 0 15px rgba(92, 208, 255, 0.5);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        z-index: 1;
        font-size: 1rem; /* Standard font size */
        line-height: 1.5;
        vertical-align: middle;
        text-transform: none; /* Remove uppercase to match others if needed, or keep for emphasis. Let's keep it normal case to match "Nueva Orden..." */
    }

    /* The "Shine" Effect - Adjusted for smaller button */
    .btn-ai-gradient::after {
        content: '';
        position: absolute;
        top: 0;
        left: -150%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
        transform: skewX(-20deg);
        animation: magicShine 3s infinite;
        z-index: -1;
    }

    .btn-ai-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(92, 208, 255, 0.7);
        color: #0f172a !important;
        text-decoration: none !important;
    }

    .btn-ai-gradient i {
        font-size: 1.1em;
        animation: floatIcon 3s ease-in-out infinite;
    }

    .btn-text {
        font-weight: 700;
    }

    @keyframes magicShine {
        0% { left: -150%; }
        20% { left: 150%; }
        100% { left: 150%; }
    }

    @keyframes floatIcon {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        25% { transform: translateY(-2px) rotate(-5deg); }
        75% { transform: translateY(2px) rotate(5deg); }
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .btn-ai-gradient {
            width: 100%;
            margin-top: 10px;
            justify-content: center;
            float: none !important;
        }
    }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Órdenes de Compra</h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" id="btn-export-excel-ordencompra" class="btn btn-success float-right ml-2"
                        data-toggle="tooltip" title="Exportar a Excel">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    <a class="btn btn-primary ml-2 float-right"
                       href="{{ route('ordencompra.create') }}">
                       <i class="fas fa-plus"></i>
                        Nueva Orden de Compra
                    </a>
                    <a class="btn btn-ai-gradient ml-2 float-right"
                       href="{{ route('ordencompra.create') }}">
                       <i class="fas fa-robot"></i>
                        <span class="btn-text">Generar Con IA</span>
                    </a>
                </div>
            </div>
        </div>
    </section>


    <div class="content px-3">

        @includeIf('ordencompra.filtros', [
            'proveedores' => $proveedores,
            'sucursales' => $sucursales,
            'estados' => $estados
        ])

        <div class="card tabla-container">
            @include('ordencompra.table')
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Botón Exportar Excel
        $('#btn-export-excel-ordencompra').on('click', function(e) {
            e.preventDefault();
            let url = '{{ url('ordencompra') }}';
            let params = new URLSearchParams(window.location.search);
            params.set('exportar', 'excel');
            window.open(url + '?' + params.toString(), '_blank');
        });
    });
</script>
@endpush
