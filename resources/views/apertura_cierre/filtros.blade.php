<div class="card shadow-sm border-0 mb-4 collapsed-card" id="card-filtros-apertura" style="border-radius: 0.5rem;">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
         style="border-bottom: 1px solid #f0f0f0; border-radius: 0.5rem 0.5rem 0 0; cursor: pointer;"
         data-card-widget="collapse">
        <h5 class="card-title text-dark m-0" style="font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-filter text-muted mr-2"></i> Filtros de Búsqueda
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-muted" id="btn-collapse-apertura">
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

                <!-- COMBO CAJA -->
                <div class="form-group col-md-2">
                    {!! Form::label('id_caja', 'Caja:', ['style' => 'font-size: 0.85rem; color: #555;']) !!}
                    {!! Form::select('id_caja', $cajas, request()->get('id_caja', null), [
                        'class' => 'form-control form-control-sm select2 shadow-none',
                        'style' => 'width: 100%; border: 1px solid #e2e8f0; border-radius: 0.375rem;',
                        'placeholder' => 'Todas',
                        'id' => 'id_caja',
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
                <div class="form-group col-md-1 text-center d-flex align-items-end">
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
            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true
            });

            // LIMPIAR TODOS LOS FILTROS
            $('#btn-limpiar-filtros').click(function() {
                $('#fecha_desde').val('');
                $('#fecha_hasta').val('');
                $('#id_caja').val('').trigger('change');
                $('#user_id').val('').trigger('change');
                $('#id_sucursal').val('').trigger('change');
                $('.buscar').val('');
                window.location.href = '{{ url()->current() }}';
            });

            // Aplicar filtros
            $('#fecha_desde, #fecha_hasta, #id_caja, #user_id, #id_sucursal').on('change', function() {
                var url = '{{ url()->current() }}';
                var params = new URLSearchParams();

                if ($('#fecha_desde').val()) params.set('fecha_desde', $('#fecha_desde').val());
                if ($('#fecha_hasta').val()) params.set('fecha_hasta', $('#fecha_hasta').val());
                if ($('#id_caja').val()) params.set('id_caja', $('#id_caja').val());
                if ($('#user_id').val()) params.set('user_id', $('#user_id').val());
                if ($('#id_sucursal').val()) params.set('id_sucursal', $('#id_sucursal').val());
                if ($('.buscar').val()) params.set('buscar', $('.buscar').val());

                window.location.href = url + '?' + params.toString();
            });

             // Abrir card de filtros si hay filtros activos
             @if(request()->get('fecha_desde') || request()->get('fecha_hasta') || request()->get('id_caja') || request()->get('user_id') || request()->get('id_sucursal'))
                $('#card-filtros-apertura').removeClass('collapsed-card');
                $('#card-filtros-apertura .card-body').show();
                $('#btn-collapse-apertura i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            @endif

             // Cambiar icono del botón al expandir/colapsar
             $('#card-filtros-apertura .card-header').on('click', function() {
                let $card = $('#card-filtros-apertura');
                let $icon = $('#btn-collapse-apertura i');

                if ($card.hasClass('collapsed-card')) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });
        });
    </script>
@endpush
