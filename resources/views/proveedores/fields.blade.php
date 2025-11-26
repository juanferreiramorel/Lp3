<!-- Descripcion Field -->
<div class="form-group col-sm-12">
    {!! Form::label('descripcion', 'Descripcion:') !!}
    {!! Form::text('descripcion', null, ['class' => 'form-control' , 'required']) !!}
</div>

<!-- RUC Field -->
<div class="form-group col-sm-12">
    {!! Form::label('ruc', 'RUC:') !!}
    {!! Form::text('ruc', null, ['class' => 'form-control']) !!}
</div>

<!-- Direccion Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('direccion', 'Direccion:') !!}
    {!! Form::textarea('direccion', null, ['class' => 'form-control', 'rows' => '3']) !!}
</div>

<!-- Telefono Field -->
<div class="form-group col-sm-12">
    {!! Form::label('telefono', 'Telefono:') !!}
    {!! Form::text('telefono', null, ['class' => 'form-control', 'required']) !!}
</div>