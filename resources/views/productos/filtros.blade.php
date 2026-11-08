<!-- Componente de filtros para productos -->
<div class="card mb-4 collapsed-card" id="card-filtros-productos" style="border-radius: 0.5rem;">
    <div class="card-header py-3 d-flex justify-content-between align-items-center"
         style="cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-productos">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form id="form-busqueda-productos">
            <div class="row">
                <!-- COMBO ESTADO -->
                <div class="form-group col-md-2">
                    {!! Form::label('estado_filter', 'Estado:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('estado_filter', [
                        '' => '📋 Todos',
                        '1' => '🟢 Activos',
                        '0' => '🔴 Inactivos'
                    ], request()->get('estado_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'estado_filter',
                    ]) !!}
                </div>

                <!-- COMBO MARCA -->
                <div class="form-group col-md-3">
                    {!! Form::label('marca_filter', 'Marca:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('marca_filter', 
                        ['' => '🏷️ Todas las marcas'] + $marcas->toArray(), 
                        request()->get('marca_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'marca_filter',
                    ]) !!}
                </div>

                <!-- COMBO IMPUESTO -->
                <div class="form-group col-md-2">
                    {!! Form::label('tax_filter', 'Impuesto:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('tax_filter', 
                        ['' => '💰 Todos'] + $taxes->toArray(), 
                        request()->get('tax_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'tax_filter',
                    ]) !!}
                </div>

                <!-- COMBO CONTROLA STOCK -->
                <div class="form-group col-md-2">
                    {!! Form::label('stock_filter', 'Controla Stock:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('stock_filter', [
                        '' => '📦 Todos',
                        '1' => '✅ Sí controla',
                        '0' => '❌ No controla'
                    ], request()->get('stock_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'stock_filter',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros-productos"
                        
                        data-toggle="tooltip" data-placement="top"
                        title="Limpiar filtros">
                        <i class="fas fa-broom"></i> Limpiar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- BÚSQUEDA POR TEXTO -->
@include('components.search-input')

@push('scripts')
    <script>
        $(document).ready(function() {
            // LIMPIAR TODOS LOS FILTROS
            $('#btn-limpiar-filtros-productos').click(function() {
                $('#estado_filter').val('');
                $('#marca_filter').val('');
                $('#tax_filter').val('');
                $('#stock_filter').val('');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#estado_filter, #marca_filter, #tax_filter, #stock_filter').on('change', function() {
                aplicarFiltrosProductos();
            });

            // Función para aplicar filtros
            function aplicarFiltrosProductos() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var estadoFilter = $('#estado_filter').val();
                var marcaFilter = $('#marca_filter').val();
                var taxFilter = $('#tax_filter').val();
                var stockFilter = $('#stock_filter').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (estadoFilter !== '') params.set('estado_filter', estadoFilter);
                if (marcaFilter !== '') params.set('marca_filter', marcaFilter);
                if (taxFilter !== '') params.set('tax_filter', taxFilter);
                if (stockFilter !== '') params.set('stock_filter', stockFilter);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('estado_filter') || request()->get('marca_filter') || request()->get('tax_filter') || request()->get('stock_filter'))
                $('#card-filtros-productos').removeClass('collapsed-card');
                $('#card-filtros-productos .card-body').show();
                $('#btn-collapse-productos i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-productos .card-header').on('click', function() {
                let $card = $('#card-filtros-productos');
                let $icon = $('#btn-collapse-productos i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
