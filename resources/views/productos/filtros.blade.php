<!-- Componente de filtros para productos -->
<div class="card card-info collapsed-card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filtros de Búsqueda
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                VER FILTROS <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form id="form-busqueda-productos">
            <div class="row">
                <!-- COMBO ESTADO -->
                <div class="form-group col-md-2">
                    {!! Form::label('estado_filter', 'Estado:') !!}
                    {!! Form::select('estado_filter', [
                        '' => '📋 Todos',
                        '1' => '🟢 Activos',
                        '0' => '🔴 Inactivos'
                    ], request()->get('estado_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'estado_filter',
                    ]) !!}
                </div>

                <!-- COMBO MARCA -->
                <div class="form-group col-md-3">
                    {!! Form::label('marca_filter', 'Marca:') !!}
                    {!! Form::select('marca_filter', 
                        ['' => '🏷️ Todas las marcas'] + $marcas->toArray(), 
                        request()->get('marca_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'marca_filter',
                    ]) !!}
                </div>

                <!-- COMBO IMPUESTO -->
                <div class="form-group col-md-2">
                    {!! Form::label('tax_filter', 'Impuesto:') !!}
                    {!! Form::select('tax_filter', 
                        ['' => '💰 Todos'] + $taxes->toArray(), 
                        request()->get('tax_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'tax_filter',
                    ]) !!}
                </div>

                <!-- COMBO CONTROLA STOCK -->
                <div class="form-group col-md-2">
                    {!! Form::label('stock_filter', 'Controla Stock:') !!}
                    {!! Form::select('stock_filter', [
                        '' => '📦 Todos',
                        '1' => '✅ Sí controla',
                        '0' => '❌ No controla'
                    ], request()->get('stock_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'stock_filter',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center">
                    <label>&nbsp;</label>
                    <button class="btn btn-warning btn-sm btn-block" type="button"
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
                $('.collapsed-card .btn-tool').click();
            @endif

            // Cambiar texto e icono del botón al expandir/colapsar
            $('[data-card-widget="collapse"]').on('click', function() {
                let $card = $(this).closest('.card');
                let $button = $(this);

                // Verificar el estado ANTES de que AdminLTE cambie la clase
                let isCollapsed = $card.hasClass('collapsed-card');

                setTimeout(function() {
                    // Si estaba colapsado, ahora está expandido
                    if (isCollapsed) {
                        $button.html('OCULTAR FILTROS <i class="fas fa-minus"></i>');
                    } else {
                        $button.html('VER FILTROS <i class="fas fa-plus"></i>');
                    }
                }, 350); // Esperar a que termine la animación
            });
        });
    </script>
@endpush
