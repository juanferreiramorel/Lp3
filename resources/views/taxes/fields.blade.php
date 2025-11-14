<!-- Nombre Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nombre', 'Nombre del Impuesto:') !!}
    {!! Form::text('nombre', null, [
        'class' => 'form-control',
        'required',
        'maxlength' => 100,
        'placeholder' => 'Ej: IVA, IEPS, ISR',
    ]) !!}
</div>

<!-- Porcentaje Field -->
<div class="form-group col-sm-6">
    {!! Form::label('porcentaje', 'Porcentaje (%):') !!}
    {!! Form::number('porcentaje', null, [
        'class' => 'form-control',
        'required',
        'step' => '0.01',
        'min' => '0',
        'max' => '100',
        'placeholder' => 'Ej: 10.00',
    ]) !!}
</div>

<!-- Descripcion Field -->
<div class="form-group col-sm-12">
    {!! Form::label('descripcion', 'Descripción:') !!}
    {!! Form::textarea('descripcion', null, [
        'class' => 'form-control',
        'maxlength' => 255,
        'rows' => 3,
        'placeholder' => 'Descripción opcional del impuesto',
    ]) !!}
</div>

<!-- Activo Field -->
<div class="form-group col-sm-12">
    <div class="form-check">
        {!! Form::hidden('activo', 0) !!}
        {!! Form::checkbox('activo', 1, isset($tax) ? $tax->activo : true, ['class' => 'form-check-input', 'id' => 'activo']) !!}
        {!! Form::label('activo', 'Activo', ['class' => 'form-check-label']) !!}
    </div>
    <small class="form-text text-muted">
        Desmarque esta opción si desea desactivar el impuesto sin eliminarlo
    </small>
</div>
