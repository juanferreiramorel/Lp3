<!-- Descripcion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descripcion', 'Descripcion:') !!}
    {!! Form::text('descripcion', null, [
        'class' => 'form-control',
        'required',
        'placeholder' => 'Ingrese la descripción del producto',
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
    {!! Form::file('imagen_producto', ['class' => 'form-control', 'accept' => 'image/*']) !!}

    <!-- Mostrar la imagen actual si existe -->
    @if (isset($productos) && $productos->imagen_producto)
        <div class="mt-2">
            <p>Imagen actual:</p>
            <img src="{{ asset('img/productos/' . $productos->imagen_producto) }}" alt="Imagen del Producto"
                style="width: 150px; height: 80px; display: block; margin: auto;">
        </div>
    @endif
</div>

<!-- Impuestos Field (Multi-select) -->
<div class="form-group col-sm-12">
    {!! Form::label('taxes', 'Impuestos:') !!}
    <div class="card">
        <div class="card-body">
            @if(isset($taxes) && count($taxes) > 0)
                <div class="row">
                    @foreach($taxes as $tax)
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="form-check">
                                {!! Form::checkbox('taxes[]', $tax->id_tax,
                                    isset($productos) && isset($selectedTaxes) ? in_array($tax->id_tax, $selectedTaxes) : false,
                                    ['class' => 'form-check-input', 'id' => 'tax_' . $tax->id_tax]
                                ) !!}
                                {!! Form::label('tax_' . $tax->id_tax, $tax->nombre . ' (' . number_format($tax->porcentaje, 2) . '%)', ['class' => 'form-check-label']) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">
                    No hay impuestos disponibles.
                    <a href="{{ route('taxes.create') }}" target="_blank">Crear impuesto</a>
                </p>
            @endif
        </div>
    </div>
</div>
