<!-- Componente de filtros para recibos simples -->
{{-- Every control here used to carry its colours in a style="" attribute, which
     is why this whole panel stayed light when the rest of the app went dark.
     Colour now comes from the theme tokens. --}}
<div class="card mb-4 collapsed-card" id="card-filtros-recibos">
    <div class="card-header py-3 d-flex justify-content-between align-items-center"
         style="cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2" aria-hidden="true"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="btn-collapse-recibos"
                    aria-label="Mostrar u ocultar los filtros">
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form id="form-busqueda">
            <div class="row">
                <!-- RANGO DE FECHAS -->
                <div class="form-group col-md-3 col-6">
                    {!! Form::label('fecha_desde', 'Fecha Desde:') !!}
                    {!! Form::input('date', 'fecha_desde', request()->get('fecha_desde', null), [
                    'class' => 'form-control form-control-sm',
                    'id' => 'fecha_desde',
                    ]) !!}
                </div>

                <div class="form-group col-md-3 col-6">
                    {!! Form::label('fecha_hasta', 'Fecha Hasta:') !!}
                    {!! Form::input('date', 'fecha_hasta', request()->get('fecha_hasta', null), [
                    'class' => 'form-control form-control-sm',
                    'id' => 'fecha_hasta',
                    ]) !!}
                </div>

                <!-- COMBO CLIENTE -->
                <div class="form-group col-md-3 col-12">
                    {!! Form::label('id_cliente', 'Cliente:') !!}
                    {!! Form::select('id_cliente', $clientes, request()->get('id_cliente', null), [
                    'class' => 'form-control form-control-sm select2',
                    'style' => 'width: 100%;',
                    'placeholder' => 'Todos los clientes',
                    'id' => 'id_cliente',
                    ]) !!}
                </div>

                <!-- COMBO ESTADO -->
                <div class="form-group col-md-2 col-8">
                    {!! Form::label('estado', 'Estado:') !!}
                    {!! Form::select('estado', $estados, request()->get('estado', null), [
                    'class' => 'form-control form-control-sm select2',
                    'style' => 'width: 100%;',
                    'placeholder' => 'Todos',
                    'id' => 'estado',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-1 col-4 d-flex align-items-end">
                    <label class="d-none d-md-block">&nbsp;</label>
                    <button class="btn btn-default btn-block" type="button"
                        id="btn-limpiar-filtros"
                        data-toggle="tooltip" data-placement="top"
                        aria-label="Limpiar los filtros"
                        title="Limpiar filtros">
                        <i class="fas fa-broom" aria-hidden="true"></i>
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
        // Inicializar Select2 en los filtros con tamaño pequeño

        // LIMPIAR TODOS LOS FILTROS
        $('#btn-limpiar-filtros').click(function() {
            $('#fecha_desde').val('');
            $('#fecha_hasta').val('');
            $('#id_cliente').val('').trigger('change');
            $('#estado').val('').trigger('change');
            $('.buscar').val('');
            // Redirigir a la página sin parámetros
            window.location.href = '{{ url()->current() }}';
        });

        // Aplicar filtros cuando cambian los campos
        $('#fecha_desde, #fecha_hasta').on('change', function() {
            aplicarFiltros();
        });

        $('#id_cliente, #estado').on('change', function() {
            aplicarFiltros();
        });

        // Función para aplicar filtros
        function aplicarFiltros() {
            var url = '{{ url()->current() }}';
            var params = new URLSearchParams();

            // Agregar solo los filtros que tienen valor
            // Simple selectors matching OrdenCompra reference
            var fechaDesde = $('#fecha_desde').val();
            var fechaHasta = $('#fecha_hasta').val();
            var idCliente = $('#id_cliente').val();
            var estado = $('#estado').val();
            var buscar = $('.buscar').val();
            var searchMode = $('.search-mode').val();

            if (fechaDesde) params.set('fecha_desde', fechaDesde);
            if (fechaHasta) params.set('fecha_hasta', fechaHasta);
            if (idCliente) params.set('id_cliente', idCliente);
            if (estado) params.set('estado', estado);
            if (buscar) params.set('buscar', buscar);
            if (searchMode) params.set('search_mode', searchMode);

            var queryString = params.toString();
            window.location.href = url + (queryString ? '?' + queryString : '');
        }

        // Abrir card de filtros si hay filtros activos
        @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('id_cliente') || request()->get('estado'))
            $('#card-filtros-recibos').removeClass('collapsed-card');
            $('#card-filtros-recibos .card-body').show();
            $('#btn-collapse-recibos i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        @endif

        // Cambiar icono del botón al expandir/colapsar
        $('#card-filtros-recibos .card-header').on('click', function() {
            let $card = $('#card-filtros-recibos');
            let $icon = $('#btn-collapse-recibos i');

            if ($card.hasClass('collapsed-card')) {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
    });
</script>
@endpush
