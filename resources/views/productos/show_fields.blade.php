<!-- Descripcion Field -->
<div class="col-sm-12">
    {!! Form::label('descripcion', 'Descripcion:') !!}
    <p>{{ $productos->descripcion }}</p>
</div>

<!-- Codigo de Barras Field -->
<div class="col-sm-12">
    {!! Form::label('codigo_barras', 'C&oacute;digo de Barras:') !!}
    <p>{{ $productos->codigo_barras ?? '-' }}</p>
</div>

<!-- Precio Field -->
<div class="col-sm-12">
    {!! Form::label('precio', 'Precio:') !!}
    <p>{{ $productos->precio }}</p>
</div>

<!-- Tipo Iva Field -->
<div class="col-sm-12">
    {!! Form::label('tipo_iva', 'Tipo Iva:') !!}
    <p>{{ $productos->tipo_iva }}</p>
</div>

<!-- Controlar Stock Field -->
<div class="col-sm-12">
    {!! Form::label('controlar_stock', 'Controlar Stock:') !!}
    <p>{{ $productos->controlar_stock ? 'Sí' : 'No' }}</p>
</div>

<!-- Id Marca Field -->
<div class="col-sm-12">
    {!! Form::label('id_marca', 'Id Marca:') !!}
    <p>{{ $productos->id_marca }}</p>
</div>

