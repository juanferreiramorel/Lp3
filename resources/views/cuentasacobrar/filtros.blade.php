<!-- Componente de filtros para cuentas a cobrar -->
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

                <!-- COMBO CLIENTE -->
                <div class="form-group col-md-3">
                    {!! Form::label('id_cliente', 'Cliente:') !!}
                    {!! Form::select('id_cliente', $clientes, request()->get('id_cliente', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todos los clientes',
                        'id' => 'id_cliente',
                    ]) !!}
                </div>

                <!-- COMBO ESTADO CUOTA -->
                <div class="form-group col-md-2">
                    {!! Form::label('estado_cuota', 'Estado:') !!}
                    {!! Form::select('estado_cuota', $estados_cuota, request()->get('estado_cuota', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todos',
                        'id' => 'estado_cuota',
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
<div class="clearfix mb-3">
    <form id="form-busqueda-texto">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control buscar" name="buscar"
                value="{{ request()->get('buscar', null) }}"
                placeholder="Buscar por: Factura, Cliente, CI, Fecha, Monto..."
                data-url="{{ url()->current() }}">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
    </form>
</div>

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
                $('#id_cliente').val('').trigger('change');
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

            $('#id_cliente, #estado_cuota, #id_sucursal').on('change', function() {
                aplicarFiltros();
            });

            // Función para aplicar filtros
            function aplicarFiltros() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var idCliente = $('#id_cliente').val();
                var estadoCuota = $('#estado_cuota').val();
                var idSucursal = $('#id_sucursal').val();
                var buscar = $('.buscar').val();

                if (fechaDesde) params.set('fecha_desde', fechaDesde);
                if (fechaHasta) params.set('fecha_hasta', fechaHasta);
                if (idCliente) params.set('id_cliente', idCliente);
                if (estadoCuota) params.set('estado_cuota', estadoCuota);
                if (idSucursal) params.set('id_sucursal', idSucursal);
                if (buscar) params.set('buscar', buscar);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Manejar el submit del formulario de búsqueda de texto
            $('#form-busqueda-texto').on('submit', function(e) {
                e.preventDefault();
                var buscar = $('.buscar').val();
                var url = '{{ url()->current() }}';

                // Mantener los filtros actuales
                var params = new URLSearchParams(window.location.search);
                if (buscar) {
                    params.set('buscar', buscar);
                } else {
                    params.delete('buscar');
                }

                window.location.href = url + '?' + params.toString();
            });

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('id_cliente') || request()->get('estado_cuota') || request()->get('id_sucursal'))
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
