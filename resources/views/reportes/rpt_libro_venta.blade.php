@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Libro Venta</h1>
                </div>
                <div class="col-sm-6">
                    <button class="btn btn-success float-right ml-2" id="btn-exportar-excel" type="button" data-toggle="tooltip" title="Exportar a Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-danger float-right" id="btn-exportar-pdf" type="button" data-toggle="tooltip" title="Exportar a PDF">
                         <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <!-- Filters Card -->
        <div class="card shadow-sm border-0 mb-4 collapsed-card" id="card-filtros" style="border-radius: 0.5rem;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" 
                 style="border-bottom: 1px solid #f0f0f0; border-radius: 0.5rem 0.5rem 0 0; cursor: pointer;" 
                 data-card-widget="collapse">
                <h5 class="card-title text-dark m-0" style="font-size: 0.95rem; font-weight: 600;">
                    <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
                </h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-muted" id="btn-collapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="card-body bg-light" style="display: none; border-radius: 0 0 0.5rem 0.5rem;">
                <form id="form-filtros">
                    <div class="row">
                        <!-- RANGO DE FECHAS -->
                        <div class="form-group col-md-3">
                            {!! Form::label('desde', 'Fecha Desde:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                            {!! Form::input('date', 'desde', request()->get('desde', null), [
                                'class' => 'form-control form-control-sm shadow-none border-0',
                                'style' => 'background-color: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                                'id' => 'desde',
                            ]) !!}
                        </div>

                        <div class="form-group col-md-3">
                            {!! Form::label('hasta', 'Fecha Hasta:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                            {!! Form::input('date', 'hasta', request()->get('hasta', null), [
                                'class' => 'form-control form-control-sm shadow-none border-0',
                                'style' => 'background-color: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                                'id' => 'hasta',
                            ]) !!}
                        </div>

                        <!-- COMBO CLIENTE -->
                        <div class="form-group col-md-3">
                            {!! Form::label('id_cliente', 'Cliente:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                            {!! Form::select('id_cliente', $clientes, request()->get('id_cliente', null), [
                                'class' => 'form-control form-control-sm select2 shadow-none',
                                'style' => 'width: 100%;',
                                'placeholder' => 'Todos los clientes',
                                'id' => 'id_cliente',
                            ]) !!}
                        </div>

                         <!-- COMBO SUCURSAL -->
                         <div class="form-group col-md-3">
                            {!! Form::label('id_sucursal', 'Sucursal:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                            {!! Form::select('id_sucursal', $sucursales, request()->get('id_sucursal', null), [
                                'class' => 'form-control form-control-sm select2 shadow-none',
                                'style' => 'width: 100%;',
                                'placeholder' => 'Todas',
                                'id' => 'id_sucursal',
                            ]) !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button class="btn btn-light btn-sm border mr-2" type="button" id="btn-limpiar-filtros" title="Limpiar filtros">
                                <i class="fas fa-broom"></i> Limpiar
                            </button>
                            <button class="btn btn-primary btn-sm" type="button" id="btn-aplicar-filtros">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Component -->
        @include('components.search-input')

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" id="libro-venta-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nro Factura</th>
                                <th>RUC</th>
                                <th>Cliente</th>
                                <th class="text-right">Exenta</th>
                                <th class="text-right">Gravada 5%</th>
                                <th class="text-right">Gravada 10%</th>
                                <th class="text-right">IVA 5%</th>
                                <th class="text-right">IVA 10%</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                                    <td>{{ $venta->factura_nro }}</td>
                                    <td>{{ $venta->ruc }}</td>
                                    <td>{{ $venta->cliente }}</td>
                                    <td class="text-right">{{ number_format($venta->total_exento, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($venta->total_gravado_5, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($venta->total_gravado_10, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($venta->total_iva_5, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($venta->total_iva_10, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($venta->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No se encontraron registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="4" class="text-right">TOTALES:</td>
                                <td class="text-right">{{ number_format($totalesGenerales->sum_exento, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalesGenerales->sum_gravado_5, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalesGenerales->sum_gravado_10, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalesGenerales->sum_iva_5, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalesGenerales->sum_iva_10, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totalesGenerales->total_general, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    Mostrando {{ count($ventas) }} de {{ $total }} registros
                </div>
                <div class="float-right">
                    @include('adminlte-templates::common.paginate', ['records' => $ventas])
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true
            });

            // Toggle Filters Card Style
            $('#card-filtros .card-header').on('click', function() {
                let $card = $('#card-filtros');
                let $icon = $('#btn-collapse i');
                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });

             // Abrir card de filtros si hay filtros activos
            @if(request()->get('desde') || request()->get('hasta') || request()->get('id_cliente') || request()->get('id_sucursal'))
                $('#card-filtros').removeClass('collapsed-card');
                $('#card-filtros .card-body').show();
                $('#btn-collapse i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // FUNCION UNIFICADA PARA APLICAR TODOS LOS FILTROS
            function aplicarFiltros() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Recuperar valores de filtros
                var desde = $('#desde').val();
                var hasta = $('#hasta').val();
                var idCliente = $('#id_cliente').val();
                var idSucursal = $('#id_sucursal').val();
                
                // Recuperar valores del buscador (del componente search-input)
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                // Setear params solo si tienen valor
                if (desde) params.set('desde', desde);
                if (hasta) params.set('hasta', hasta);
                if (idCliente) params.set('id_cliente', idCliente);
                if (idSucursal) params.set('id_sucursal', idSucursal);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                // Resetear pagina siempre que se filtre
                params.delete('page');

                window.location.href = url + (params.toString() ? '?' + params.toString() : '');
            }

            // Eventos para filtros del card
            $('#btn-aplicar-filtros').click(function(e) {
                e.preventDefault();
                aplicarFiltros();
            });

            // Trigger change en selects para aplicar filtro automaticamente (opcional, como en ventas)
            $('#id_cliente, #id_sucursal').on('change', function() {
                aplicarFiltros();
            });
            // Trigger change en fechas (opcional)
            $('#desde, #hasta').on('change', function() {
                aplicarFiltros();
            });

            // Eventos para el buscador (override del componente search-input para que use nuestra funcion unificada)
            // Desvinculamos eventos previos para evitar conflictos si el componente los define
            $('.form-busqueda-texto').off('submit');
            $('.buscar').off('input');

            // Re-implementar debounce del buscador
            let searchTimeout = null;
            $('.buscar').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    aplicarFiltros();
                }, 600);
            });

            $('.form-busqueda-texto').on('submit', function(e) {
                e.preventDefault();
                aplicarFiltros();
            });

            // Limpiar filtros
            $('#btn-limpiar-filtros').click(function(e) {
                e.preventDefault();
                // Limpiar inputs
                $('#desde').val('');
                $('#hasta').val('');
                $('#id_cliente').val('').trigger('change');
                $('#id_sucursal').val('').trigger('change');
                $('.buscar').val('');
                $('.search-mode').val('all'); // valor default

                // Recargar sin parametros
                window.location.href = '{{ url()->current() }}';
            });

            // Export buttons
            function exportar(tipo) {
                // Usamos la misma logica de params actual
                var params = new URLSearchParams(window.location.search);
                params.set('exportar', tipo);
                window.open('{{ url()->current() }}?' + params.toString(), '_blank');
            }

            $("#btn-exportar-pdf").click(function(e) {
                e.preventDefault();
                exportar('pdf');
            });

            $("#btn-exportar-excel").click(function(e) {
                e.preventDefault();
                exportar('excel');
            });
        })
    </script>
@endpush
