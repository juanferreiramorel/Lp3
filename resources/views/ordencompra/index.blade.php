@extends('layouts.app')

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
                    <a class="btn btn-primary float-right"
                       href="{{ route('ordencompra.create') }}">
                       <i class="fas fa-plus"></i>
                        Nueva Orden de Compra
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
