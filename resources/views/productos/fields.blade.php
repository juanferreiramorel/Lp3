@php
    $productoModel = $productos ?? null;
@endphp

<!-- Descripci&oacute;n Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descripcion', 'Descripci&oacute;n:') !!}
    {!! Form::text('descripcion', null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Ingrese la descripci&oacute;n del producto',
    ]) !!}
</div>

<!-- Precio Field -->
<div class="form-group col-sm-6">
    {!! Form::label('precio', 'Precio:') !!}
    {!! Form::text('precio', isset($productos) ? number_format($productos->precio, 0, ',', '.') : null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Ingrese el precio del producto',
        'onkeyup' => 'format(this)',
    ]) !!}
</div>

<!-- Tipo IVA Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tipo_iva', 'Tipo IVA:') !!}
    {!! Form::select('tipo_iva', $tipo_iva, null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Seleccione el tipo de IVA',
    ]) !!}
</div>

<!-- Controlar Stock Field -->
<div class="form-group col-sm-6">
    {!! Form::label('controlar_stock', 'Controlar Stock:') !!}
    {!! Form::select('controlar_stock', [1 => 'S&iacute;', 0 => 'No'], old('controlar_stock', isset($productos) ? (int) $productos->controlar_stock : 1), [
        'class' => 'form-control',
        'required',
    ]) !!}
</div>

<!-- Id Marca Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_marca', 'Marca:') !!}
    {!! Form::select('id_marca', $marcas, null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Seleccione una marca',
    ]) !!}
</div>

<!-- imagen_producto Field -->
<div class="form-group col-sm-6">
    {!! Form::label('imagen_producto', 'Imagen del Producto:') !!}
    <div class="bg-light border rounded p-3">
        <div class="custom-file">
            {!! Form::file('imagen_producto', [
                'class' => 'custom-file-input',
                'id' => 'producto-imagen-input',
                'accept' => 'image/*',
            ]) !!}
            <label class="custom-file-label" for="producto-imagen-input">Selecciona una imagen...</label>
        </div>
        <small class="form-text text-muted mt-2">
            Formatos permitidos: JPG, PNG, GIF (m&aacute;x. 2MB)
        </small>
        <small class="form-text text-muted" id="producto-imagen-selected-name">
            @if ($productoModel?->imagen_producto)
                Archivo actual: {{ $productoModel->imagen_producto }}
            @else
                No has seleccionado ning&uacute;n archivo.
            @endif
        </small>
    </div>

    @if ($productoModel?->imagen_producto)
        <div class="mt-2 text-center">
            <p class="mb-2">Vista previa actual:</p>
            <img src="{{ asset('img/productos/' . $productoModel->imagen_producto) }}" alt="Imagen del Producto" style="width: 150px; height: 80px;" class="img-thumbnail">
        </div>
    @endif
</div>

@once
    <style>
        #producto-imagen-input ~ .custom-file-label::after {
            content: 'Buscar archivo' !important;
        }
    </style>
@endonce

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('producto-imagen-input');
                if (!input) {
                    return;
                }

                var label = input.nextElementSibling;
                var helper = document.getElementById('producto-imagen-selected-name');

                var updateFileName = function () {
                    if (!input.files.length) {
                        if (label) {
                            label.textContent = 'Selecciona una imagen...';
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
