<!-- Componente de filtros para metodo_pagos -->
<div class="card mb-4 collapsed-card" id="card-filtros-metodo_pagos" style="border-radius: 0.5rem;">
    <div class="card-header py-3 d-flex justify-content-between align-items-center"
         style="cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-metodo_pagos">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form id="form-busqueda-metodo_pagos">
            <div class="row">
                <!-- COMBO ESTADO -->
                <div class="form-group col-md-3">
                    {!! Form::label('estado_filter', 'Estado:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('estado_filter', [
                        '' => '📋 Todos',
                        '1' => '🟢 Solo Activos',
                        '0' => '🔴 Solo Inactivos'
                    ], request()->get('estado_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'estado_filter',
                    ]) !!}
                </div>

                <!-- COMBO TIPO -->
                <div class="form-group col-md-3">
                    {!! Form::label('tipo_filter', 'Tipo:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('tipo_filter', 
                        ['' => '💳 Todos los tipos'] + $tipos->toArray(), 
                        request()->get('tipo_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%;',
                        'id' => 'tipo_filter',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros-metodo_pagos"
                        
                        data-toggle="tooltip" data-placement="top"
                        title="Limpiar filtros">
                        <i class="fas fa-broom"></i>
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
            $('#btn-limpiar-filtros-metodo_pagos').click(function() {
                $('#estado_filter').val('');
                $('#tipo_filter').val('');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#estado_filter, #tipo_filter').on('change', function() {
                aplicarFiltrosMetodoPagos();
            });

            // Función para aplicar filtros
            function aplicarFiltrosMetodoPagos() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var estadoFilter = $('#estado_filter').val();
                var tipoFilter = $('#tipo_filter').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (estadoFilter !== '') params.set('estado_filter', estadoFilter);
                if (tipoFilter !== '') params.set('tipo_filter', tipoFilter);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('estado_filter') || request()->get('tipo_filter'))
                $('#card-filtros-metodo_pagos').removeClass('collapsed-card');
                $('#card-filtros-metodo_pagos .card-body').show();
                $('#btn-collapse-metodo_pagos i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-metodo_pagos .card-header').on('click', function() {
                let $card = $('#card-filtros-metodo_pagos');
                let $icon = $('#btn-collapse-metodo_pagos i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
