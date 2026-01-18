<!-- Componente de filtros para cargos -->
<div class="card shadow-sm border-0 mb-4 collapsed-card" id="card-filtros-cargos" style="border-radius: 0.5rem;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
         style="border-bottom: 1px solid #f0f0f0; border-radius: 0.5rem 0.5rem 0 0; cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title text-dark m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-cargos">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body bg-light" style="display: none; border-radius: 0 0 0.5rem 0.5rem;">
        <form id="form-busqueda-cargos">
            <div class="row">
                <!-- COMBO ESTADO -->
                <div class="form-group col-md-3">
                    {!! Form::label('estado_filter', 'Estado:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('estado_filter', [
                        '' => '📋 Todos',
                        '1' => '🟢 Solo Activos',
                        '0' => '🔴 Solo Inactivos'
                    ], request()->get('estado_filter', ''), [
                        'class' => 'form-control form-control-sm shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'id' => 'estado_filter',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros-cargos"
                        style="color: #64748b; background-color: #fff; border-color: #e2e8f0; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-radius: 0.375rem;"
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
            $('#btn-limpiar-filtros-cargos').click(function() {
                $('#estado_filter').val('');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#estado_filter').on('change', function() {
                aplicarFiltrosCargos();
            });

            // Función para aplicar filtros
            function aplicarFiltrosCargos() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var estadoFilter = $('#estado_filter').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (estadoFilter !== '') params.set('estado_filter', estadoFilter);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('estado_filter'))
                $('#card-filtros-cargos').removeClass('collapsed-card');
                $('#card-filtros-cargos .card-body').show();
                $('#btn-collapse-cargos i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-cargos .card-header').on('click', function() {
                let $card = $('#card-filtros-cargos');
                let $icon = $('#btn-collapse-cargos i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
