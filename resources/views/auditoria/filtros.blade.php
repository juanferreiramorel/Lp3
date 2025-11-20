<!-- Componente de filtros para auditoría -->
<div class="card filtros-auditoria collapsed-card mb-3">
    <div class="card-header filtros-header">
        <div class="filtros-title-wrapper">
            <div class="filtros-icon">
                <i class="fas fa-sliders-h"></i>
            </div>
            <div>
                <h3 class="card-title mb-0">Filtros Avanzados</h3>
                <br>
                <p class="filtros-subtitle mb-0">Refina tu búsqueda con criterios específicos</p>
            </div>
        </div>
        <div class="card-tools">
            <button type="button" class="btn btn-filtros" data-card-widget="collapse">
                <span class="btn-text">VER FILTROS</span>
                <i class="fas fa-chevron-down ml-2"></i>
            </button>
        </div>
    </div>
    <div class="card-body filtros-body" style="display: none;">
        <form id="form-busqueda">
            <div class="row">
                <!-- RANGO DE FECHAS -->
                <div class="form-group col-md-2">
                    {!! Form::label('fecha_desde', 'Fecha Desde:') !!}
                    {!! Form::input('date', 'fecha_desde', request()->get('fecha_desde', null), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'fecha_desde',
                    ]) !!}
                </div>

                <div class="form-group col-md-2">
                    {!! Form::label('fecha_hasta', 'Fecha Hasta:') !!}
                    {!! Form::input('date', 'fecha_hasta', request()->get('fecha_hasta', null), [
                        'class' => 'form-control form-control-sm',
                        'id' => 'fecha_hasta',
                    ]) !!}
                </div>

                <!-- COMBO USUARIO -->
                <div class="form-group col-md-2">
                    {!! Form::label('user_id', 'Usuario:') !!}
                    {!! Form::select('user_id', $usuarios, request()->get('user_id', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todos',
                        'id' => 'user_id',
                    ]) !!}
                </div>

                <!-- COMBO OPERACIÓN -->
                <div class="form-group col-md-2">
                    {!! Form::label('operation', 'Operación:') !!}
                    {!! Form::select('operation', $operaciones, request()->get('operation', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todas',
                        'id' => 'operation',
                    ]) !!}
                </div>

                <!-- COMBO TABLA -->
                <div class="form-group col-md-2">
                    {!! Form::label('table_name', 'Tabla:') !!}
                    {!! Form::select('table_name', $tablas, request()->get('table_name', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todas',
                        'id' => 'table_name',
                    ]) !!}
                </div>

                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-2">
                    {!! Form::label('id_sucursal', 'Sucursal:') !!}
                    {!! Form::select('id_sucursal', $sucursales, request()->get('id_sucursal', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todas',
                        'id' => 'id_sucursal',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2">
                    <label>&nbsp;</label>
                    <button class="btn btn-limpiar-filtros btn-block" type="button"
                        id="btn-limpiar-filtros"
                        data-toggle="tooltip" data-placement="top"
                        title="Limpiar todos los filtros">
                        <i class="fas fa-redo mr-2"></i>
                        <span>Limpiar Filtros</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    /* === FILTROS CARD === */
    .filtros-auditoria {
        border-radius: 4px;
        border: 1px solid #dee2e6;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        overflow: hidden;
    }
    .filtros-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .filtros-title-wrapper {
        display: flex;
        align-items: center;
        gap: 14px;
        color: #ffffff;
    }
    .filtros-icon {
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .card-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.15rem;
    }
    .filtros-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.85rem;
        margin-top: 2px;
    }
    .btn-filtros {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }
    .btn-filtros:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.4);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .btn-filtros i {
        transition: transform 0.3s ease;
    }
    .filtros-auditoria:not(.collapsed-card) .btn-filtros i {
        transform: rotate(180deg);
    }
    .filtros-body {
        background: #fafbfc;
        padding: 24px;
    }
    .filtros-body .form-group label {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    .filtros-body .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .filtros-body .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .btn-limpiar-filtros {
        background: #ffc107;
        color: #212529;
        border: 1px solid #ffc107;
        border-radius: 4px;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.15s ease;
    }
    .btn-limpiar-filtros:hover {
        background: #e0a800;
        border-color: #d39e00;
        color: #212529;
    }

    @media (max-width: 768px) {
        .filtros-header {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }
        .btn-filtros {
            width: 100%;
        }
    }
</style>

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
            @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('user_id') || request()->get('operation') || request()->get('table_name') || request()->get('id_sucursal'))
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
                        $button.html('<span class="btn-text">OCULTAR FILTROS</span><i class="fas fa-chevron-down ml-2"></i>');
                    } else {
                        $button.html('<span class="btn-text">VER FILTROS</span><i class="fas fa-chevron-down ml-2"></i>');
                    }
                }, 350); // Esperar a que termine la animación
            });
        });
    </script>
@endpush
