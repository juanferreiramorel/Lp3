<!-- Descripcion Field -->
<div class="col-sm-12">
    {!! Form::label('descripcion', 'Descripcion:') !!}
    <p>{{ $sucursales->descripcion }}</p>
</div>

<!-- Direccion Field -->
<div class="col-sm-12">
    {!! Form::label('direccion', 'Direccion:') !!}
    <p>{{ $sucursales->direccion }}</p>
</div>

<!-- Telefono Field -->
<div class="col-sm-12">
    {!! Form::label('telefono', 'Telefono:') !!}
    <p>{{ $sucursales->telefono }}</p>
</div>

<!-- Id Ciudad Field -->
<div class="col-sm-12">
    {!! Form::label('id_ciudad', 'Id Ciudad:') !!}
    <p>{{ $sucursales->id_ciudad }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12">
    {!! Form::label('estado', 'Estado:') !!}
    <p>
        @if($sucursales->estado)
            <span class="badge badge-success">Activo</span>
        @else
            <span class="badge badge-secondary">Inactivo</span>
        @endif
    </p>

