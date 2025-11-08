@php
    $permissionOptions = $permissions ?? [];
    $helpTopicModel = $helpTopic ?? null;
    $oldIsActive = old('is_active');
    $isActiveChecked = !is_null($oldIsActive)
        ? (bool) intval($oldIsActive)
        : (bool) ($helpTopicModel->is_active ?? true);
@endphp

<!-- Titulo Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('title', 'Titulo:') !!}
    {!! Form::text('title', null, ['class' => 'form-control', 'required', 'maxlength' => 200]) !!}
</div>

<!-- Modulo Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('module', 'Modulo:') !!}
    {!! Form::text('module', null, ['class' => 'form-control', 'maxlength' => 100]) !!}
</div>

<!-- Ruta Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('route_name', 'Ruta Laravel / URL:') !!}
    {!! Form::text('route_name', null, ['class' => 'form-control', 'maxlength' => 150]) !!}
    <small class="form-text text-muted">Ejemplo: ventas.index</small>
</div>

<!-- Permiso Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('permission_slug', 'Permiso asociado:') !!}
    {!! Form::select('permission_slug', $permissionOptions, null, [
        'class' => 'form-control select2',
        'placeholder' => 'Selecciona un permiso (opcional)',
        'data-placeholder' => 'Selecciona un permiso (opcional)',
        'data-tags' => 'true',
    ]) !!}
    <small class="form-text text-muted">El valor se guarda tal como aparece en el modulo de permisos.</small>
</div>

<!-- Accion Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('action_key', 'Clave de accion:') !!}
    {!! Form::text('action_key', null, ['class' => 'form-control', 'maxlength' => 100]) !!}
    <small class="form-text text-muted">Utiliza esta clave si necesitas multiples ayudas para la misma pantalla.</small>
</div>

<!-- Estado Field -->
<div class="form-group col-sm-12 col-md-6">
    {!! Form::label('is_active', 'Estado:') !!}
    <div class="custom-control custom-switch">
        {!! Form::hidden('is_active', 0) !!}
        {!! Form::checkbox('is_active', 1, $isActiveChecked, ['class' => 'custom-control-input', 'id' => 'is_active']) !!}
        <label class="custom-control-label" for="is_active">Disponible para los usuarios</label>
    </div>
</div>

<!-- Descripcion Field -->
<div class="form-group col-sm-12">
    {!! Form::label('description', 'Descripcion breve:') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]) !!}
</div>

<!-- PDF Field -->
<div class="form-group col-sm-12">
    {!! Form::label('pdf', 'Archivo PDF:') !!}
    <div class="bg-light border rounded p-3">
        <div class="custom-file">
            {!! Form::file('pdf', [
                'class' => 'custom-file-input',
                'id' => 'pdf-file',
                'accept' => 'application/pdf',
                'data-browse' => 'Buscar archivo',
            ]) !!}
            <label class="custom-file-label" for="pdf-file">Selecciona un PDF...</label>
        </div>
        <small class="form-text text-muted mt-2">
            Formato permitido: PDF. Tamano maximo 10 MB.
        </small>
        <small class="form-text text-muted" id="pdf-selected-name">
            @isset($helpTopicModel->pdf_path)
                Archivo actual: {{ basename($helpTopicModel->pdf_path) }}
            @else
                No has seleccionado ningun archivo.
            @endisset
        </small>
    </div>

    @isset($helpTopicModel->pdf_path)
        <div class="alert alert-secondary mt-3 mb-0 d-flex align-items-center">
            <i class="fas fa-file-pdf mr-2"></i>
            <a href="{{ route('ayuda.pdf', $helpTopicModel->id_help) }}" target="_blank" class="text-decoration-none">
                Ver archivo actual
            </a>
        </div>
    @endisset
</div>

@once
    <style>
        #pdf-file ~ .custom-file-label::after {
            content: 'Buscar archivo' !important;
        }
    </style>
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('pdf-file');
                if (!input) {
                    return;
                }

                var label = input.nextElementSibling;
                var helper = document.getElementById('pdf-selected-name');

                var updateLabel = function () {
                    if (!input.files.length) {
                        if (label) {
                            label.textContent = 'Selecciona un PDF...';
                        }
                        if (helper) {
                            helper.textContent = 'No has seleccionado ningun archivo.';
                        }
                        return;
                    }

                    var name = input.files[0].name;
                    if (label) {
                        label.textContent = name;
                    }
                    if (helper) {
                        helper.textContent = name;
                    }
                };

                input.addEventListener('change', updateLabel);
            });
        </script>
    @endpush
@endonce
