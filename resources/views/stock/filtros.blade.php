<!-- Componente de filtros para stock -->
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
                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-4">
                    {!! Form::label('sucursales', 'Sucursal:') !!}
                    {!! Form::select('sucursales', $sucursales, request()->get('sucursales', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todas las sucursales',
                        'id' => 'sucursales',
                    ]) !!}
                </div>

                <!-- COMBO PRODUCTO -->
                <div class="form-group col-md-6">
                    {!! Form::label('productos', 'Producto:') !!}
                    {!! Form::select('productos', $productos, request()->get('productos', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todos los productos',
                        'id' => 'productos',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center">
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
                $('#sucursales').val('').trigger('change');
                $('#productos').val('').trigger('change');
                $('.buscar').val('');
                // Redirigir a la página sin parámetros
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros cuando cambian los campos
            $('#sucursales, #productos').on('change', function() {
                aplicarFiltros();
            });

            // Función para aplicar filtros
            function aplicarFiltros() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                // Agregar solo los filtros que tienen valor
                var sucursal = $('#sucursales').val();
                var producto = $('#productos').val();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();

                if (sucursal) params.set('sucursales', sucursal);
                if (producto) params.set('productos', producto);
                if (buscar) params.set('buscar', buscar);
                 if (searchMode) params.set('search_mode', searchMode);

                var queryString = params.toString();
                window.location.href = url + (queryString ? '?' + queryString : '');
            }

            // Abrir card de filtros si hay filtros activos
            @if(request()->get('sucursales') || request()->get('productos'))
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
