<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Nombre y Apellido:') !!}
    {!! Form::text('name', null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Ingrese el Nombre y Apellido',
    ]) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Nick Name:') !!}
    {!! Form::text('email', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese el Nick Name']) !!}
</div>


<!-- password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password', 'Ingresar Contraseña:') !!}
    {!! Form::password('password', [
        'class' => 'form-control',
        isset($users) ? '' : 'required',
        'placeholder' => isset($users) ? 'Dejar vacio si no se actualiza' : 'Ingrese la Contraseña',
    ]) !!}
</div>


<!-- Ci Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ci', 'Nro Documento:') !!}
    {!! Form::text('ci', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese el Nro Documento']) !!}
</div>

<!-- Direccion Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('direccion', 'Direccion:') !!}
    {!! Form::textarea('direccion', null, [
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Ingrese la Dirección',
    ]) !!}
</div>

<!-- Telefono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('telefono', 'Telefono:') !!}
    {!! Form::text('telefono', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el nro de telefono']) !!}
</div>

<!-- Fecha Ingreso Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha_ingreso', 'Fecha Ingreso:') !!}
    {!! Form::date('fecha_ingreso', null, ['class' => 'form-control', 'id' => 'fecha_ingreso', 'required']) !!}
</div>

<!-- Role Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('role_id', 'Rol:') !!}
    {!! Form::select('role_id', $roles, null, [
        'class' => 'form-control',
        'placeholder' => 'Seleccione un rol',
        'required',
    ]) !!}
</div>

<!-- Sucursal Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_sucursal', 'Sucursal:') !!}
    {!! Form::select('id_sucursal', $sucursales, null, [
        'class' => 'form-control',
        'placeholder' => 'Seleccione una sucursal',
        'required',
    ]) !!}
</div>

<!-- Sucursales Permitidas Field -->
<div class="form-group col-sm-6">
    <div class="d-flex justify-content-between align-items-center">
        {!! Form::label('sucursales_permitidas', 'Sucursales que podrá ver:', ['class' => 'mb-0 font-weight-bold']) !!}
        <div class="btn-group btn-group-sm" role="group" aria-label="Gestión de sucursales">
            <button type="button" class="btn btn-link p-0" id="btn-sucursales-select-all">
                <i class="fas fa-check-double mr-1"></i>Seleccionar todo
            </button>
            <span class="text-muted mx-1">|</span>
            <button type="button" class="btn btn-link p-0 text-danger" id="btn-sucursales-clear">
                <i class="fas fa-times mr-1"></i>Limpiar
            </button>
        </div>
    </div>
    <small class="text-muted d-block mb-2">Utiliza los botones para agregar o quitar sucursales rápidamente o busca por nombre desde el campo.</small>
    {!! Form::select(
        'sucursales_permitidas[]',
        $sucursales,
        old('sucursales_permitidas', $sucursalesAsignadas ?? []),
        [
            'class' => 'form-control select2',
            'id' => 'sucursales_permitidas',
            'multiple' => 'multiple',
            'data-placeholder' => 'Seleccione una o varias sucursales',
        ]
    ) !!}
    <div class="d-flex justify-content-between mt-1">
        <small class="form-text text-muted mb-0">Siempre tendrá acceso a su sucursal principal.</small>
        <small class="text-muted mb-0">
            <span id="sucursales-count" class="font-weight-bold">0</span> seleccionadas
        </small>
    </div>
</div>

@push('scripts')
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const $select = $('#sucursales_permitidas');
                if (!$select.length) {
                    return;
                }

                const selectAllBtn = document.getElementById('btn-sucursales-select-all');
                const clearBtn = document.getElementById('btn-sucursales-clear');
                const counter = document.getElementById('sucursales-count');

                const updateCounter = () => {
                    const selected = $select.val() || [];
                    counter.textContent = selected.length;
                };

                const getAllValues = () => {
                    return Array.from($select[0].options)
                        .map(option => option.value)
                        .filter(value => value !== '');
                };

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function (event) {
                        event.preventDefault();
                        const allValues = getAllValues();
                        $select.val(allValues).trigger('change');
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function (event) {
                        event.preventDefault();
                        $select.val([]).trigger('change');
                    });
                }

                $select.on('change', updateCounter);
                // Inicializar contador al cargar la vista
                updateCounter();
            });
        </script>
    @endonce
@endpush
