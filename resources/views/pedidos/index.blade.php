@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Pedidos</h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" id="btn-export-excel-pedidos" class="btn btn-success float-right ml-2"
                        data-toggle="tooltip" title="Exportar a Excel">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    <a class="btn btn-primary float-right"
                       href="{{ route('pedidos.create') }}">
                        <i class="fas fa-plus"></i>
                        Nueva Pedido
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @includeIf('pedidos.filtros', [
            'clientes' => $clientes,
            'sucursales' => $sucursales,
            'estados' => $estados
        ])

        <div class="card tabla-container">
            @include('pedidos.table')
        </div>
    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Botón Exportar Excel
        $('#btn-export-excel-pedidos').on('click', function(e) {
            e.preventDefault();
            let url = '{{ url('pedidos') }}';
            let params = new URLSearchParams(window.location.search);
            params.set('exportar', 'excel');
            window.open(url + '?' + params.toString(), '_blank');
        });
    });
</script>
@endpush