<!-- Componente de filtros para cuentas a pagar -->
<div class="card mb-4 collapsed-card" id="card-filtros-cxp" style="border-radius: 0.5rem;">
    <div class="card-header py-3 d-flex justify-content-between align-items-center"
         style="cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-cxp">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form id="form-busqueda">
            <div class="row">
                <!-- RANGO DE FECHAS -->
                <div class="form-group col-md-2">
                    {!! Form::label('fecha_desde', 'Fecha Desde:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::input('date', 'fecha_desde', request()->get('fecha_desde', null), [
                        'class' => 'form-control form-control-sm shadow-none border-0',
                                                'id' => 'fecha_desde',
                    ]) !!}
                </div>

                <div class="form-group col-md-2">
                    {!! Form::label('fecha_hasta', 'Fecha Hasta:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::input('date', 'fecha_hasta', request()->get('fecha_hasta', null), [
                        'class' => 'form-control form-control-sm shadow-none border-0',
                                                'id' => 'fecha_hasta',
                    ]) !!}
                </div>

                <!-- COMBO PROVEEDOR -->
                <div class="form-group col-md-3">
                    {!! Form::label('id_proveedor', 'Proveedor:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('id_proveedor', $proveedores, request()->get('id_proveedor', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%;',
                        'placeholder' => 'Todos los proveedores',
                        'id' => 'id_proveedor',
                    ]) !!}
                </div>

                <!-- COMBO ESTADO CUOTA -->
                <div class="form-group col-md-2">
                    {!! Form::label('estado_cuota', 'Estado:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('estado_cuota', $estados_cuota, request()->get('estado_cuota', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%;',
                        'placeholder' => 'Todos',
                        'id' => 'estado_cuota',
                    ]) !!}
                </div>

                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-2">
                    {!! Form::label('id_sucursal', 'Sucursal:', ['style' => 'font-size: 0.85rem;']) !!}
                    {!! Form::select('id_sucursal', $sucursales, request()->get('id_sucursal', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%;',
                        'placeholder' => 'Todas',
                        'id' => 'id_sucursal',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-1 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros"
                        
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
            // Inicializar Select2 en los filtros con tamaño pequeño

            // LIMPIAR TODOS LOS FILTROS
            $('#btn-limpiar-filtros').click(function() {
                $('#fecha_desde').val('');
                $('#fecha_hasta').val('');
                $('#id_proveedor').val('').trigger('change');
                $('#estado_cuota').val('').trigger('change');
                $('#id_sucursal').val('').trigger('change');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#fecha_desde, #fecha_hasta').on('change', function() {
                aplicarFiltros();
            });

            $('#id_proveedor, #estado_cuota, #id_sucursal').on('change', function() {
                aplicarFiltros();
            });

            // Función para aplicar filtros
            function aplicarFiltros() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var idProveedor = $('#id_proveedor').val();
                var estadoCuota = $('#estado_cuota').val();
                var idSucursal = $('#id_sucursal').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (fechaDesde) params.set('fecha_desde', fechaDesde);
                if (fechaHasta) params.set('fecha_hasta', fechaHasta);
                if (idProveedor) params.set('id_proveedor', idProveedor);
                if (estadoCuota) params.set('estado_cuota', estadoCuota);
                if (idSucursal) params.set('id_sucursal', idSucursal);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            // Abrir card de filtros si hay filtros activos
            @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('id_proveedor') || request()->get('estado_cuota') || request()->get('id_sucursal'))
                $('#card-filtros-cxp').removeClass('collapsed-card');
                $('#card-filtros-cxp .card-body').show();
                $('#btn-collapse-cxp i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-cxp .card-header').on('click', function() {
                let $card = $('#card-filtros-cxp');
                let $icon = $('#btn-collapse-cxp i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
