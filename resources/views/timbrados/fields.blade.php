<!-- Empresa Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_empresa', 'Empresa:') !!}
    {!! Form::select('id_empresa', $empresas->pluck('razon_social', 'id_empresa'), null, ['class' => 'form-control', 'required', 'placeholder' => 'Seleccione una empresa']) !!}
</div>

<!-- Tipo Timbrado Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_tipo_timbrado', 'Tipo de Documento:') !!}
    {!! Form::select('id_tipo_timbrado', $tipos->pluck('descripcion', 'id_tipo_timbrado'), null, ['class' => 'form-control', 'required', 'placeholder' => 'Seleccione el tipo']) !!}
</div>

<!-- Número de Timbrado Field -->
<div class="form-group col-sm-6">
    {!! Form::label('numero_timbrado', 'Número de Timbrado:') !!}
    {!! Form::text('numero_timbrado', null, ['class' => 'form-control', 'required', 'maxlength' => 50, 'placeholder' => 'Ej: 12345678']) !!}
    <small class="form-text text-muted">Número de autorización fiscal</small>
</div>

<!-- Fecha Inicio Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha_inicio', 'Fecha de Inicio:') !!}
    {!! Form::date('fecha_inicio', isset($timbrado) && $timbrado->fecha_inicio ? $timbrado->fecha_inicio->format('Y-m-d') : null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Fecha Vencimiento Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha_vencimiento', 'Fecha de Vencimiento:') !!}
    {!! Form::date('fecha_vencimiento', isset($timbrado) && $timbrado->fecha_vencimiento ? $timbrado->fecha_vencimiento->format('Y-m-d') : null, ['class' => 'form-control', 'required']) !!}
    <small class="form-text text-muted">Debe ser posterior a la fecha de inicio</small>
</div>

<!-- Establecimiento Field -->
<div class="form-group col-sm-6">
    {!! Form::label('establecimiento', 'Establecimiento:') !!}
    {!! Form::text('establecimiento', '001', ['class' => 'form-control', 'required', 'maxlength' => 3, 'pattern' => '[0-9]{3}', 'placeholder' => '001']) !!}
    <small class="form-text text-muted">3 dígitos (001-999)</small>
</div>

<!-- Punto de Expedición Field -->
<div class="form-group col-sm-6">
    {!! Form::label('punto_expedicion', 'Punto de Expedición:') !!}
    {!! Form::text('punto_expedicion', '001', ['class' => 'form-control', 'required', 'maxlength' => 3, 'pattern' => '[0-9]{3}', 'placeholder' => '001']) !!}
    <small class="form-text text-muted">3 dígitos (001-999)</small>
</div>

<!-- Rango Inicio Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rango_inicio', 'Rango Inicial:') !!}
    {!! Form::number('rango_inicio', null, ['class' => 'form-control', 'required', 'min' => 1, 'placeholder' => 'Ej: 1']) !!}
    <small class="form-text text-muted">Número inicial del rango autorizado</small>
</div>

<!-- Rango Fin Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rango_fin', 'Rango Final:') !!}
    {!! Form::number('rango_fin', null, ['class' => 'form-control', 'required', 'min' => 1, 'placeholder' => 'Ej: 10000']) !!}
    <small class="form-text text-muted">Número final del rango autorizado (debe ser mayor al inicial)</small>
</div>

@if(isset($timbrado))
<!-- Estado Field (solo en edición) -->
<div class="form-group col-sm-6">
    {!! Form::label('estado', 'Estado:') !!}
    {!! Form::select('estado', [
        'VIGENTE' => 'Vigente',
        'VENCIDO' => 'Vencido',
        'AGOTADO' => 'Agotado',
        'CANCELADO' => 'Cancelado'
    ], null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Número Actual Field (solo lectura en edición) -->
<div class="form-group col-sm-6">
    {!! Form::label('numero_actual_display', 'Facturas Utilizadas:') !!}
    <input type="text" class="form-control" readonly value="{{ $timbrado->numero_actual ?? 0 }}">
    <small class="form-text text-muted">Este valor se actualiza automáticamente al crear facturas</small>
</div>
@endif

<!-- Observaciones Field -->
<div class="form-group col-sm-12">
    {!! Form::label('observaciones', 'Observaciones:') !!}
    {!! Form::textarea('observaciones', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Observaciones o notas adicionales (opcional)']) !!}
</div>

@push('page_scripts')
<script>
$(document).ready(function() {
    // Validar que rango_fin sea mayor que rango_inicio
    $('#rango_fin, #rango_inicio').on('input', function() {
        var inicio = parseInt($('#rango_inicio').val()) || 0;
        var fin = parseInt($('#rango_fin').val()) || 0;

        if (fin <= inicio && fin > 0) {
            $('#rango_fin')[0].setCustomValidity('El rango final debe ser mayor al inicial');
        } else {
            $('#rango_fin')[0].setCustomValidity('');
        }
    });

    // Validar que fecha_vencimiento sea posterior a fecha_inicio
    $('#fecha_vencimiento, #fecha_inicio').on('change', function() {
        var inicio = new Date($('#fecha_inicio').val());
        var fin = new Date($('#fecha_vencimiento').val());

        if (fin <= inicio && $('#fecha_vencimiento').val()) {
            $('#fecha_vencimiento')[0].setCustomValidity('La fecha de vencimiento debe ser posterior a la de inicio');
        } else {
            $('#fecha_vencimiento')[0].setCustomValidity('');
        }
    });
});
</script>
@endpush
