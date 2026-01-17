<!-- Componente de filtros para stock -->
<div class="card shadow-sm border-0 mb-4 collapsed-card" id="card-filtros-stock" style="border-radius: 0.5rem;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
         style="border-bottom: 1px solid #f0f0f0; border-radius: 0.5rem 0.5rem 0 0; cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title text-dark m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-stock">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="card-body bg-light" style="display: none; border-radius: 0 0 0.5rem 0.5rem;">
        <form id="form-busqueda">
            <div class="row">
                <!-- COMBO SUCURSAL -->
                <div class="form-group col-md-4">
                    {!! Form::label('sucursales', 'Sucursal:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('sucursales', $sucursales, request()->get('sucursales', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todas las sucursales',
                        'id' => 'sucursales',
                    ]) !!}
                </div>

                <!-- COMBO PRODUCTO -->
                <div class="form-group col-md-6">
                    {!! Form::label('productos', 'Producto:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('productos', $productos, request()->get('productos', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todos los productos',
                        'id' => 'productos',
                    ]) !!}
                </div>

                <!-- BOTÓN LIMPIAR FILTROS -->
                <div class="form-group col-md-2 text-center d-flex align-items-end">
                    <button class="btn btn-light btn-sm btn-block border" type="button"
                        id="btn-limpiar-filtros"
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
            // Abrir card de filtros si hay filtros activos
            @if(request()->get('sucursales') || request()->get('productos'))
                $('#card-filtros-stock').removeClass('collapsed-card');
                $('#card-filtros-stock .card-body').show();
                $('#btn-collapse-stock i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

            // Cambiar icono del botón al expandir/colapsar
            $('#card-filtros-stock .card-header').on('click', function() {
                let $card = $('#card-filtros-stock');
                let $icon = $('#btn-collapse-stock i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
