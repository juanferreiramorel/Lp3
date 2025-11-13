<!-- Componente de filtros para auditoría -->
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
                <div class="form-group col-md-1 text-center">
                    <label>&nbsp;</label>
                    <button class="btn btn-warning btn-sm btn-block" type="button"
                        id="btn-limpiar-filtros"
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
                        $button.html('OCULTAR FILTROS <i class="fas fa-minus"></i>');
                    } else {
                        $button.html('VER FILTROS <i class="fas fa-plus"></i>');
                    }
                }, 350); // Esperar a que termine la animación
            });
        });
    </script>
@endpush
