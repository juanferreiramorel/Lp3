<!-- Descripcion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descripcion', 'Descripción:') !!}
    {!! Form::text('descripcion', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese la descripción']) !!}
</div>

<!-- Id Tipo Metodo Pago Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_tipo_metodo_pago', 'Tipo de Método:') !!}
    {!! Form::select('id_tipo_metodo_pago', $tipos, null, ['class' => 'form-control', 'required', 'placeholder' => 'Seleccione un tipo']) !!}
</div>
