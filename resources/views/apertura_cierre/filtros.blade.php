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

                <!-- COMBO CAJA -->
                <div class="form-group col-md-2">
                    {!! Form::label('id_caja', 'Caja:') !!}
                    {!! Form::select('id_caja', $cajas, request()->get('id_caja', null), [
                        'class' => 'form-control form-control-sm select2',
                        'placeholder' => 'Todas',
                        'id' => 'id_caja',
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
                $('.collapsed-card .btn-tool').click();
            @endif

             // Cambiar texto e icono del botón al expandir/colapsar
             $('[data-card-widget="collapse"]').on('click', function() {
                let $card = $(this).closest('.card');
                let $button = $(this);
                let isCollapsed = $card.hasClass('collapsed-card');
                setTimeout(function() {
                    if (isCollapsed) {
                        $button.html('OCULTAR FILTROS <i class="fas fa-minus"></i>');
                    } else {
                        $button.html('VER FILTROS <i class="fas fa-plus"></i>');
                    }
                }, 350);
            });
        });
    </script>
@endpush
