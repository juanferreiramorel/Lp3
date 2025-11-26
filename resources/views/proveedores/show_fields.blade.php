<!-- Descripcion Field -->
<div class="col-sm-12">
    {!! Form::label('descripcion', 'Descripcion:') !!}
    <p>{{ $proveedores->descripcion }}</p>
</div>

<!-- RUC Field -->
<div class="col-sm-12">
    {!! Form::label('ruc', 'RUC:') !!}
    <p>{{ $proveedores->ruc }}</p>
</div>

<!-- Direccion Field -->
<div class="col-sm-12">
    {!! Form::label('direccion', 'Direccion:') !!}
    <p>{{ $proveedores->direccion }}</p>
</div>

<!-- Telefono Field -->
<div class="col-sm-12">
    {!! Form::label('telefono', 'Telefono:') !!}
    <p>{{ $proveedores->telefono }}</p>
</div>

