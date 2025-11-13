@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        <i class="fas fa-edit"></i>
                        Vista Previa del Contrato - Puede Editar Antes de Guardar
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')

        <div class="row">
            <!-- Información del Cliente -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-user"></i> Datos del Cliente</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Nombre:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_nombre }} {{ $cliente->clie_apellido }}</dd>

                            <dt class="col-sm-5">CI:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_ci }}</dd>

                            <dt class="col-sm-5">Teléfono:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_telefono ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Dirección:</dt>
                            <dd class="col-sm-7">{{ $cliente->clie_direccion ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Ciudad:</dt>
                            <dd class="col-sm-7">{{ $cliente->ciudad ?? 'N/A' }}</dd>

                            <dt class="col-sm-5">Departamento:</dt>
                            <dd class="col-sm-7">{{ $cliente->departamento ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Plantilla Usada</h3>
                    </div>
                    <div class="card-body">
                        <h5>{{ $plantilla->nombre_plantilla }}</h5>
                        @if ($plantilla->descripcion)
                            <p class="text-muted">{{ $plantilla->descripcion }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Revise y Edite el Contenido Antes de Guardar
                        </h3>
                    </div>

                    {!! Form::open(['route' => 'plantillas_contratos.guardar_contrato']) !!}

                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Puede modificar el contenido del contrato según sus necesidades.
                            Una vez guardado, podrá editarlo nuevamente si es necesario.
                        </div>

                        <!-- Campos ocultos -->
                        {!! Form::hidden('id_plantilla_contrato', $plantilla->id_plantilla_contrato) !!}
                        {!! Form::hidden('id_cliente', $cliente->id_cliente) !!}

                        <!-- Contenido del Contrato -->
                        <div class="form-group">
                            {!! Form::label('contenido_generado', 'Contenido del Contrato:') !!}
                            {!! Form::textarea('contenido_generado', $contenidoGenerado, [
                                'class' => 'form-control',
                                'id' => 'contenido_generado',
                                'rows' => 25,
                                'required',
                                'style' => 'font-family: "Courier New", monospace; font-size: 14px;'
                            ]) !!}
                            <small class="form-text text-muted">
                                <i class="fas fa-edit"></i>
                                Edite el contenido según sea necesario. Los cambios se guardarán al confirmar.
                            </small>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            {!! Form::label('observaciones', 'Observaciones (Opcional):') !!}
                            {!! Form::textarea('observaciones', $observaciones, [
                                'class' => 'form-control',
                                'rows' => 3,
                                'placeholder' => 'Agregue notas o comentarios sobre este contrato'
                            ]) !!}
                        </div>

                        <!-- Información de la fecha -->
                        <div class="alert alert-light">
                            <strong><i class="fas fa-calendar"></i> Fecha del Contrato:</strong>
                            {{ \Carbon\Carbon::parse($fecha_contrato)->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                {!! Form::button('<i class="fas fa-save"></i> Guardar Contrato', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-success btn-lg btn-block'
                                ]) !!}
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('plantillas_contratos.generar_formulario', $plantilla->id_plantilla_contrato) }}"
                                   class="btn btn-default btn-lg btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>

    @push('page_scripts')
        <script>
            // Auto-ajustar altura del textarea según el contenido
            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const textarea = document.getElementById('contenido_generado');
                autoResize(textarea);

                textarea.addEventListener('input', function() {
                    autoResize(this);
                });
            });
        </script>
    @endpush
@endsection
