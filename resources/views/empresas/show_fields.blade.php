<!-- Id Empresa Field -->
<div class="col-sm-12">
    {!! Form::label('id_empresa', 'ID:') !!}
    <p>{{ $empresa->id_empresa }}</p>
</div>

<!-- Razón Social Field -->
<div class="col-sm-12">
    {!! Form::label('razon_social', 'Razón Social:') !!}
    <p>{{ $empresa->razon_social }}</p>
</div>

<!-- Nombre Comercial Field -->
<div class="col-sm-12">
    {!! Form::label('nombre_comercial', 'Nombre Comercial:') !!}
    <p>{{ $empresa->nombre_comercial ?? 'N/A' }}</p>
</div>

<!-- RUC Field -->
<div class="col-sm-12">
    {!! Form::label('ruc', 'RUC:') !!}
    <p>{{ $empresa->ruc }}</p>
</div>

<!-- Dirección Field -->
<div class="col-sm-12">
    {!! Form::label('direccion', 'Dirección:') !!}
    <p>{{ $empresa->direccion ?? 'N/A' }}</p>
</div>

<!-- Teléfono Field -->
<div class="col-sm-12">
    {!! Form::label('telefono', 'Teléfono:') !!}
    <p>{{ $empresa->telefono ?? 'N/A' }}</p>
</div>

<!-- Email Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $empresa->email ?? 'N/A' }}</p>
</div>

<!-- Logo Field -->
<div class="col-sm-12">
    {!! Form::label('logo', 'Logo:') !!}
    @if($empresa->logo)
        <div>
            <img src="{{ $empresa->logo }}" alt="Logo {{ $empresa->nombre_comercial }}" style="max-width: 200px; max-height: 200px;">
        </div>
        <p><small>{{ $empresa->logo }}</small></p>
    @else
        <p>N/A</p>
    @endif
</div>

<!-- Sucursales Asociadas -->
@if(isset($sucursales) && count($sucursales) > 0)
<div class="col-sm-12 mt-4">
    <h5>Sucursales Asociadas</h5>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sucursales as $sucursal)
                <tr>
                    <td>{{ $sucursal->id_sucursal }}</td>
                    <td>{{ $sucursal->descripcion }}</td>
                    <td>{{ $sucursal->direccion }}</td>
                    <td>{{ $sucursal->telefono }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Fecha de Creación:') !!}
    <p>{{ \Carbon\Carbon::parse($empresa->created_at)->format('d/m/Y H:i:s') }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Última Actualización:') !!}
    <p>{{ \Carbon\Carbon::parse($empresa->updated_at)->format('d/m/Y H:i:s') }}</p>
</div>
