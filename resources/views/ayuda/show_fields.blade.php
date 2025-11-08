<!-- Titulo Field -->
<div class="col-sm-6">
    {!! Form::label('title', 'Titulo:') !!}
    <p>{{ $helpTopic->title }}</p>
</div>

<!-- Modulo Field -->
<div class="col-sm-6">
    {!! Form::label('module', 'Modulo:') !!}
    <p>{{ $helpTopic->module ?? '-' }}</p>
</div>

<!-- Ruta Field -->
<div class="col-sm-6">
    {!! Form::label('route_name', 'Ruta Laravel / URL:') !!}
    <p>{{ $helpTopic->route_name ?? '-' }}</p>
</div>

<!-- Permiso Field -->
<div class="col-sm-6">
    {!! Form::label('permission_slug', 'Permiso asociado:') !!}
    <p>{{ $helpTopic->permission_slug ?? '-' }}</p>
</div>

<!-- Accion Field -->
<div class="col-sm-6">
    {!! Form::label('action_key', 'Clave de accion:') !!}
    <p>{{ $helpTopic->action_key ?? '-' }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-6">
    {!! Form::label('is_active', 'Estado:') !!}
    <p>{{ $helpTopic->is_active ? 'Activo' : 'Inactivo' }}</p>
</div>

<!-- Descripcion Field -->
<div class="col-sm-12">
    {!! Form::label('description', 'Descripcion:') !!}
    <p>{{ $helpTopic->description ?? 'Sin descripcion' }}</p>
</div>

<!-- PDF Field -->
<div class="col-sm-6">
    {!! Form::label('pdf_path', 'Archivo PDF:') !!}
    <p>
        <a href="{{ route('ayuda.pdf', $helpTopic->id_help) }}" target="_blank">
            <i class="fas fa-file-pdf"></i> Abrir PDF
        </a>
    </p>
</div>

<!-- Control Fields -->
<div class="col-sm-6">
    {!! Form::label('created_at', 'Creado:') !!}
    <p>{{ $helpTopic->created_at }}</p>
</div>

<div class="col-sm-6">
    {!! Form::label('updated_at', 'Actualizado:') !!}
    <p>{{ $helpTopic->updated_at }}</p>
</div>
