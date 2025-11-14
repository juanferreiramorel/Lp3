<!-- Razón Social Field -->
<div class="form-group col-sm-6">
    {!! Form::label('razon_social', 'Razón Social:') !!}
    {!! Form::text('razon_social', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'placeholder' => 'Ingrese la Razón Social']) !!}
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

<!-- Teléfono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('telefono', 'Teléfono:') !!}
    {!! Form::text('telefono', null, ['class' => 'form-control', 'maxlength' => 40, 'placeholder' => 'Ingrese el Teléfono']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 100, 'placeholder' => 'Ingrese el Email']) !!}
</div>

<!-- Logo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('logo', 'Logo de la Empresa:') !!}
    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
    <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF (máx. 2MB)</small>

    @if(isset($empresa) && $empresa->logo)
        <div class="mt-2">
            <p>Logo actual:</p>
            <img src="{{ asset('img/empresas/' . $empresa->logo) }}" alt="Logo actual" style="max-width: 150px; max-height: 150px; display: block; margin: auto;">
        </div>
    @endif
</div>

<!-- Dirección Field -->
<div class="form-group col-sm-12">
    {!! Form::label('direccion', 'Dirección:') !!}
    {!! Form::textarea('direccion', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Ingrese la Dirección de la Empresa']) !!}
</div>
