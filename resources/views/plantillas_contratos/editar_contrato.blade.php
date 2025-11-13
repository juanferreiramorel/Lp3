@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-edit"></i>
                        Editar Contrato Generado
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('plantillas_contratos.ver_contrato', $contrato->id_contrato_generado) }}">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')

        <div class="row">
            <!-- Información del Contrato -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Contrato</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Plantilla:</dt>
                            <dd class="col-sm-7">{{ $contrato->nombre_plantilla }}</dd>

                            <dt class="col-sm-5">Cliente:</dt>
                            <dd class="col-sm-7">{{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</dd>

                            <dt class="col-sm-5">CI:</dt>
                            <dd class="col-sm-7">{{ $contrato->clie_ci }}</dd>

                            <dt class="col-sm-5">Generado:</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</dd>

                            <dt class="col-sm-5">Por:</dt>
                            <dd class="col-sm-7">{{ $contrato->usuario_nombre ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Atención:</strong> Los cambios realizados sobrescribirán el contenido actual del contrato.
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-edit"></i> Editar Contenido del Contrato</h3>
                    </div>

                    {!! Form::open(['route' => ['plantillas_contratos.actualizar_contrato', $contrato->id_contrato_generado], 'method' => 'PUT']) !!}

                    <div class="card-body">
                        <!-- Contenido del Contrato -->
                        <div class="form-group">
                            {!! Form::label('contenido_generado', 'Contenido del Contrato:') !!}
                            {!! Form::textarea('contenido_generado', $contrato->contenido_generado, [
                                'class' => 'form-control',
                                'id' => 'contenido_generado',
                                'rows' => 25,
                                'required',
                                'style' => 'font-family: "Courier New", monospace; font-size: 14px;'
                            ]) !!}
                            <small class="form-text text-muted">
                                <i class="fas fa-edit"></i>
                                Modifique el contenido según sea necesario.
                            </small>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            {!! Form::label('observaciones', 'Observaciones:') !!}
                            {!! Form::textarea('observaciones', $contrato->observaciones, [
                                'class' => 'form-control',
                                'rows' => 3,
                                'placeholder' => 'Agregue o modifique las observaciones'
                            ]) !!}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                {!! Form::button('<i class="fas fa-save"></i> Actualizar Contrato', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-primary btn-lg btn-block'
                                ]) !!}
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('plantillas_contratos.ver_contrato', $contrato->id_contrato_generado) }}"
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
