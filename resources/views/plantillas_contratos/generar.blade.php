@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        <i class="fas fa-file-contract"></i>
                        Generar Contrato desde Plantilla
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="row">
            <!-- Información de la Plantilla -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Plantilla Seleccionada</h3>
                    </div>
                    <div class="card-body">
                        <h5>{{ $plantilla->nombre_plantilla }}</h5>
                        @if ($plantilla->descripcion)
                            <p class="text-muted">{{ $plantilla->descripcion }}</p>
                        @endif
                        <hr>
                        <a href="{{ route('plantillas_contratos.show', $plantilla->id_plantilla_contrato) }}"
                           class="btn btn-sm btn-info btn-block" target="_blank">
                            <i class="fas fa-eye"></i> Ver Plantilla Completa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Formulario de Generación -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Datos del Contrato</h3>
                    </div>

                    {!! Form::open(['route' => ['plantillas_contratos.generar', $plantilla->id_plantilla_contrato]]) !!}

                    <div class="card-body">
                        <!-- Selección de Cliente -->
                        <div class="form-group">
                            {!! Form::label('id_cliente', 'Seleccionar Cliente:') !!}
                            <select name="id_cliente" id="id_cliente" class="form-control select2"
                                    data-placeholder="-- Seleccione un Cliente --" required>
                                <option value="">-- Seleccione un Cliente --</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id_cliente }}">
                                        {{ $cliente->clie_apellido }}, {{ $cliente->clie_nombre }} - CI: {{ $cliente->clie_ci }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                Los datos del cliente seleccionado reemplazarán las palabras clave en la plantilla
                            </small>
                        </div>

                        <!-- Fecha del Contrato -->
                        <div class="form-group">
                            {!! Form::label('fecha_contrato', 'Fecha del Contrato (Opcional):') !!}
                            {!! Form::date('fecha_contrato', \Carbon\Carbon::now(), ['class' => 'form-control']) !!}
                            <small class="form-text text-muted">
                                Si no se especifica, se usará la fecha actual
                            </small>
                        </div>

                        <!-- Observaciones -->
                        <div class="form-group">
                            {!! Form::label('observaciones', 'Observaciones (Opcional):') !!}
                            {!! Form::textarea('observaciones', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Notas adicionales sobre este contrato']) !!}
                        </div>

                        <!-- Vista Previa del Cliente -->
                        <div id="preview-cliente" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-user-check"></i> Datos del Cliente Seleccionado:</h6>
                            <div id="datos-cliente"></div>
                        </div>
                    </div>

                    <div class="card-footer">
                        {!! Form::submit('Generar Contrato', ['class' => 'btn btn-success']) !!}
                        <a href="{{ route('plantillas_contratos.index') }}" class="btn btn-default">Cancelar</a>
                    </div>

                    {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>

    @push('page_scripts')
        <script>
            $(document).ready(function() {
                // Select2 comes from window.initSelects() in the layout; the
                // placeholder travels on the element's data-placeholder.

                // Cargar datos del cliente cuando se selecciona
                $('#id_cliente').on('change', function() {
                    const clienteId = $(this).val();

                    if (clienteId) {
                        // Aquí podrías hacer una petición AJAX para obtener los datos completos del cliente
                        // Por ahora, solo mostramos el preview básico
                        $('#preview-cliente').show();
                        $('#datos-cliente').html('<p class="mb-0"><i class="fas fa-spinner fa-spin"></i> Cargando datos del cliente...</p>');

                        // Simular obtención de datos (en producción, deberías hacer una petición AJAX real)
                        setTimeout(() => {
                            const clienteTexto = $(this).find('option:selected').text();
                            $('#datos-cliente').html(`
                                <p class="mb-0"><strong>Cliente:</strong> ${clienteTexto}</p>
                                <small class="text-muted">Los datos completos del cliente se cargarán al generar el contrato</small>
                            `);
                        }, 500);
                    } else {
                        $('#preview-cliente').hide();
                    }
                });
            });
        </script>
    @endpush
@endsection
