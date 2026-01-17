<!-- Componente de filtros para auditoría -->
<div class="card shadow-sm border-0 mb-4 collapsed-card" id="card-filtros-auditoria" style="border-radius: 0.5rem;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
         style="border-bottom: 1px solid #f0f0f0; border-radius: 0.5rem 0.5rem 0 0; cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title text-dark m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Auditoría
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-auditoria">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body bg-light" style="display: none; border-radius: 0 0 0.5rem 0.5rem;">
        <form id="form-busqueda">
            <div class="row">
                <!-- RANGO DE FECHAS -->
                <div class="form-group col-md-2">
                    {!! Form::label('fecha_desde', 'Fecha Desde:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::input('date', 'fecha_desde', request()->get('fecha_desde', null), [
                        'class' => 'form-control form-control-sm shadow-none border-0',
                        'style' => 'background-color: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'id' => 'fecha_desde',
                    ]) !!}
                </div>

                <div class="form-group col-md-2">
                    {!! Form::label('fecha_hasta', 'Fecha Hasta:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::input('date', 'fecha_hasta', request()->get('fecha_hasta', null), [
                        'class' => 'form-control form-control-sm shadow-none border-0',
                        'style' => 'background-color: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'id' => 'fecha_hasta',
                    ]) !!}
                </div>

                <!-- COMBO USUARIO -->
                <div class="form-group col-md-2">
                    {!! Form::label('user_id', 'Usuario:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('user_id', $usuarios, request()->get('user_id', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todos',
                        'id' => 'user_id',
                    ]) !!}
                </div>

                <!-- COMBO OPERACIÓN -->
                <div class="form-group col-md-2">
                    {!! Form::label('operation', 'Operación:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('operation', $operaciones, request()->get('operation', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todas',
                        'id' => 'operation',
                    ]) !!}
                </div>

                <!-- COMBO TABLA -->
                <div class="form-group col-md-2">
                    {!! Form::label('table_name', 'Tabla:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('table_name', $tablas, request()->get('table_name', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todas',
                        'id' => 'table_name',
                    ]) !!}
                </div>

                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-2">
                    {!! Form::label('id_sucursal', 'Sucursal:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('id_sucursal', $sucursales, request()->get('id_sucursal', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todas',
                        'id' => 'id_sucursal',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros"
                        style="color: #64748b; background-color: #fff; border-color: #e2e8f0; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-radius: 0.375rem;"
                        data-toggle="tooltip" data-placement="top"
                        title="Limpiar todos los filtros">
                        <i class="fas fa-broom mr-2"></i> Clean
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
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true
            });

            // LIMPIAR TODOS LOS FILTROS
            $('#btn-limpiar-filtros').click(function() {
                $('#fecha_desde').val('');
                $('#fecha_hasta').val('');
                $('#user_id').val('').trigger('change');
                $('#operation').val('').trigger('change');
                $('#table_name').val('').trigger('change');
                $('#id_sucursal').val('').trigger('change');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#fecha_desde, #fecha_hasta').on('change', function() {
                aplicarFiltros();
            });

            $('#user_id, #operation, #table_name, #id_sucursal').on('change', function() {
                aplicarFiltros();
            });

            // Función para aplicar filtros
            function aplicarFiltros() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var userId = $('#user_id').val();
                var operation = $('#operation').val();
                var tableName = $('#table_name').val();
                var idSucursal = $('#id_sucursal').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (fechaDesde) params.set('fecha_desde', fechaDesde);
                if (fechaHasta) params.set('fecha_hasta', fechaHasta);
                if (userId) params.set('user_id', userId);
                if (operation) params.set('operation', operation);
                if (tableName) params.set('table_name', tableName);
                if (idSucursal) params.set('id_sucursal', idSucursal);
                if (buscar) params.set('buscar', buscar);
                if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Exponer helpers para acciones rápidas desde otros componentes (chips, métricas)
            window.auditoriaFilters = {
                aplicar: aplicarFiltros,
                setAndApply(config = {}) {
                    if (config.fecha_desde !== undefined) $('#fecha_desde').val(config.fecha_desde);
                    if (config.fecha_hasta !== undefined) $('#fecha_hasta').val(config.fecha_hasta);
                    if (config.user_id !== undefined) $('#user_id').val(config.user_id).trigger('change');
                    if (config.operation !== undefined) $('#operation').val(config.operation).trigger('change');
                    if (config.table_name !== undefined) $('#table_name').val(config.table_name).trigger('change');
                    if (config.id_sucursal !== undefined) $('#id_sucursal').val(config.id_sucursal).trigger('change');
                    if (config.buscar !== undefined) $('.buscar').val(config.buscar);
                    if (config.search_mode !== undefined) $('.search-mode').val(config.search_mode);
                    aplicarFiltros();
                }
            };

            // Abrir card de filtros si hay filtros activos
            // Abrir card de filtros si hay filtros activos
            @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('user_id') || request()->get('operation') || request()->get('table_name') || request()->get('id_sucursal'))
                $('#card-filtros-auditoria').removeClass('collapsed-card');
                $('#card-filtros-auditoria .card-body').show();
                $('#btn-collapse-auditoria i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-auditoria .card-header').on('click', function() {
                let $card = $('#card-filtros-auditoria');
                let $icon = $('#btn-collapse-auditoria i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
