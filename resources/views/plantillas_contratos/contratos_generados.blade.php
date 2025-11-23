@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-file-contract"></i>
                        Contratos Generados
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('plantillas_contratos.index') }}">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Plantillas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="clearfix">
            @includeIf('layouts.buscador', ['url' => url()->current()])
        </div>

        <div class="card tabla-container">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" id="contratos-generados-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Plantilla</th>
                                <th>Cliente</th>
                                <th>CI</th>
                                <th>Fecha Generación</th>
                                <th>Generado por</th>
                                <th>Observaciones</th>
                                <th colspan="2">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contratos as $contrato)
                                <tr>
                                    <td>{{ $contrato->id_contrato_generado }}</td>
                                    <td>{{ $contrato->nombre_plantilla }}</td>
                                    <td>{{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</td>
                                    <td>{{ $contrato->clie_ci }}</td>
                                    <td>{{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $contrato->usuario_nombre ?? '-' }}</td>
                                    <td>{{ $contrato->observaciones ? Str::limit($contrato->observaciones, 30) : '-' }}</td>
                                    <td style="width: 200px">
                                        {!! Form::open(['route' => ['plantillas_contratos.eliminar_contrato', $contrato->id_contrato_generado], 'method' => 'delete', 'style' => 'display: inline;']) !!}
                                        <div class='btn-group'>
                                            <a href="{{ route('plantillas_contratos.ver_contrato', [$contrato->id_contrato_generado]) }}"
                                                class='btn btn-info btn-xs' title="Ver Contrato">
                                                <i class="far fa-eye"></i>
                                            </a>
                                            <a href="{{ route('plantillas_contratos.editar_contrato', [$contrato->id_contrato_generado]) }}"
                                                class='btn btn-warning btn-xs' title="Editar Contrato">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            <a href="{{ route('plantillas_contratos.descargar_pdf', [$contrato->id_contrato_generado]) }}"
                                                class='btn btn-danger btn-xs' title="Descargar PDF" target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                                'type' => 'submit',
                                                'class' => 'btn btn-danger btn-xs alert-delete',
                                                'data-mensaje' => 'Contrato de ' . $contrato->clie_apellido . ', ' . $contrato->clie_nombre,
                                                'title' => 'Eliminar'
                                            ]) !!}
                                        </div>
                                        {!! Form::close() !!}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox"></i>
                                        No hay contratos generados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    <div class="float-right">
                        @include('adminlte-templates::common.paginate', ['records' => $contratos])
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
