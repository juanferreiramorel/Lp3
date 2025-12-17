<!-- Componente de filtros para cajas -->
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
        <form id="form-busqueda-cajas">
            <div class="row">
                <!-- COMBO ESTADO -->
                <div class="form-group col-md-3">
                    {!! Form::label('estado_filter', 'Estado:') !!}
                    {!! Form::select('estado_filter', [
                        '' => '📋 Todas las cajas',
                        '1' => '🟢 Solo Activas',
                        '0' => '🔴 Solo Inactivas'
                    ], request()->get('estado_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'estado_filter',
                    ]) !!}
                </div>

                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-3">
                    {!! Form::label('sucursal_filter', 'Sucursal:') !!}
                    {!! Form::select('sucursal_filter', 
                        ['' => '🏢 Todas las sucursales'] + $sucursales->toArray(), 
                        request()->get('sucursal_filter', ''), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'sucursal_filter',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center">
                    <label>&nbsp;</label>
                    <button class="btn btn-warning btn-sm btn-block" type="button"
                        id="btn-limpiar-filtros-cajas"
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
            $('#btn-limpiar-filtros-cajas').click(function() {
                $('#estado_filter').val('');
                $('#sucursal_filter').val('');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#estado_filter, #sucursal_filter').on('change', function() {
                aplicarFiltrosCajas();
            });

            // Función para aplicar filtros
            function aplicarFiltrosCajas() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var estadoFilter = $('#estado_filter').val();
                var sucursalFilter = $('#sucursal_filter').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (estadoFilter !== '') params.set('estado_filter', estadoFilter);
                if (sucursalFilter !== '') params.set('sucursal_filter', sucursalFilter);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('estado_filter') || request()->get('sucursal_filter'))
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
