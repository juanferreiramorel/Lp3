@php
    $empresaModel = $empresa ?? null;
@endphp

<!-- Raz&oacute;n Social Field -->
<div class="form-group col-sm-6">
    {!! Form::label('razon_social', 'Raz&oacute;n Social:') !!}
    {!! Form::text('razon_social', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'placeholder' => 'Ingrese la Raz&oacute;n Social']) !!}
</div>

<!-- Nombre Comercial Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nombre_comercial', 'Nombre Comercial:') !!}
    {!! Form::text('nombre_comercial', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => 'Ingrese el Nombre Comercial']) !!}
</div>

<!-- RUC Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ruc', 'RUC:') !!}
    {!! Form::text('ruc', null, ['class' => 'form-control', 'required', 'maxlength' => 50, 'placeholder' => 'Ingrese el RUC']) !!}
</div>

<!-- Tel&eacute;fono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('telefono', 'Tel&eacute;fono:') !!}
    {!! Form::text('telefono', null, ['class' => 'form-control', 'maxlength' => 40, 'placeholder' => 'Ingrese el Tel&eacute;fono']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 100, 'placeholder' => 'Ingrese el Email']) !!}
</div>

<!-- Logo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('logo', 'Logo de la Empresa:') !!}
    <div class="bg-light border rounded p-3">
        <div class="custom-file">
            {!! Form::file('logo', [
                'class' => 'custom-file-input',
                'id' => 'empresa-logo-input',
                'accept' => 'image/*',
            ]) !!}
            <label class="custom-file-label" for="empresa-logo-input">Selecciona un archivo...</label>
        </div>
        <small class="form-text text-muted mt-2">Formatos permitidos: JPG, PNG, GIF (m&aacute;x. 2MB)</small>
        <small class="form-text text-muted" id="empresa-logo-selected-name">
            @if ($empresaModel?->logo)
                Archivo actual: {{ $empresaModel->logo }}
            @else
                No has seleccionado ning&uacute;n archivo.
            @endif
        </small>
    </div>

    @if ($empresaModel?->logo)
        <div class="mt-2 text-center">
            <p class="mb-2">Vista previa actual:</p>
            <img src="{{ asset('img/empresas/' . $empresaModel->logo) }}" alt="Logo actual" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
        </div>
    @endif
</div>

<!-- Direcci&oacute;n Field -->
<div class="form-group col-sm-12">
    {!! Form::label('direccion', 'Direcci&oacute;n:') !!}
    {!! Form::textarea('direccion', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Ingrese la Direcci&oacute;n de la Empresa']) !!}
</div>

@once
    <style>
        #empresa-logo-input ~ .custom-file-label::after {
            content: 'Buscar archivo' !important;
        }
    </style>
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('empresa-logo-input');
                if (!input) {
                    return;
                }

                var label = input.nextElementSibling;
                var helper = document.getElementById('empresa-logo-selected-name');

                var updateFileName = function () {
                    if (!input.files.length) {
                        if (label) {
                            label.textContent = 'Selecciona un archivo...';
                        }
                        if (helper) {
                            helper.textContent = 'No has seleccionado ning&uacute;n archivo.';
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

                input.addEventListener('change', updateFileName);
            });
        </script>
    @endpush
@endonce
