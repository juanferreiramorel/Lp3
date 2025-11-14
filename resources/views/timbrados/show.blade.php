@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detalles del Timbrado</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('timbrados.index') }}">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información del Timbrado</h3>
                        <div class="card-tools">
                            @can('timbrados edit')
                            <a href="{{ route('timbrados.edit', $timbrado->id_timbrado) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Empresa:</dt>
                                    <dd class="col-sm-7">{{ $timbrado->empresa->razon_social }}</dd>

                                    <dt class="col-sm-5">Nro. Timbrado:</dt>
                                    <dd class="col-sm-7"><strong>{{ $timbrado->numero_timbrado }}</strong></dd>

                                    <dt class="col-sm-5">Establecimiento:</dt>
                                    <dd class="col-sm-7">{{ $timbrado->establecimiento }}</dd>

                                    <dt class="col-sm-5">Punto Expedición:</dt>
                                    <dd class="col-sm-7">{{ $timbrado->punto_expedicion }}</dd>

                                    <dt class="col-sm-5">Estado:</dt>
                                    <dd class="col-sm-7">
                                        @if($timbrado->estado == 'VIGENTE')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Vigente
                                            </span>
                                        @elseif($timbrado->estado == 'VENCIDO')
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Vencido
                                            </span>
                                        @elseif($timbrado->estado == 'AGOTADO')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-exclamation-circle"></i> Agotado
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-ban"></i> Cancelado
                                            </span>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Fecha Inicio:</dt>
                                    <dd class="col-sm-7">{{ $timbrado->fecha_inicio->format('d/m/Y') }}</dd>

                                    <dt class="col-sm-5">Fecha Vencimiento:</dt>
                                    <dd class="col-sm-7">
                                        {{ $timbrado->fecha_vencimiento->format('d/m/Y') }}
                                        @if($timbrado->proximoAVencer())
                                            <span class="badge badge-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Próximo a vencer
                                            </span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-5">Días restantes:</dt>
                                    <dd class="col-sm-7">
                                        @if($timbrado->dias_hasta_vencimiento > 0)
                                            <span class="text-{{ $timbrado->dias_hasta_vencimiento <= 30 ? 'warning' : 'success' }}">
                                                {{ $timbrado->dias_hasta_vencimiento }} días
                                            </span>
                                        @else
                                            <span class="text-danger">Vencido</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-5">Rango Autorizado:</dt>
                                    <dd class="col-sm-7">
                                        {{ number_format($timbrado->rango_inicio) }} - {{ number_format($timbrado->rango_fin) }}
                                    </dd>

                                    <dt class="col-sm-5">Total de Facturas:</dt>
                                    <dd class="col-sm-7">{{ number_format($stats['total_facturas']) }}</dd>
                                </dl>
                            </div>
                        </div>

                        @if($timbrado->observaciones)
                        <div class="row mt-3">
                            <div class="col-12">
                                <dl class="row">
                                    <dt class="col-sm-2">Observaciones:</dt>
                                    <dd class="col-sm-10">{{ $timbrado->observaciones }}</dd>
                                </dl>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Estadísticas de Uso</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-file-invoice"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Facturas Disponibles</span>
                                <span class="info-box-number">{{ number_format($stats['facturas_disponibles']) }}</span>
                            </div>
                        </div>

                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Facturas Usadas</span>
                                <span class="info-box-number">{{ number_format($stats['facturas_usadas']) }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h5>Porcentaje de Uso</h5>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar {{ $stats['porcentaje_uso'] >= 90 ? 'bg-danger' : ($stats['porcentaje_uso'] >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     role="progressbar"
                                     style="width: {{ $stats['porcentaje_uso'] }}%"
                                     aria-valuenow="{{ $stats['porcentaje_uso'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    <strong>{{ number_format($stats['porcentaje_uso'], 1) }}%</strong>
                                </div>
                            </div>
                        </div>

                        @if($timbrado->proximoAAgotarse())
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atención:</strong> El timbrado está próximo a agotarse ({{ $stats['porcentaje_uso'] }}% usado)
                        </div>
                        @endif

                        @if($timbrado->proximoAVencer())
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-clock"></i>
                            <strong>Atención:</strong> El timbrado vence en {{ $stats['dias_hasta_vencimiento'] }} días
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice"></i> Facturas Asociadas
                </h3>
            </div>
            <div class="card-body">
                @if($timbrado->ventas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nro. Factura</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timbrado->ventas()->orderBy('fecha_venta', 'desc')->limit(10)->get() as $venta)
                                <tr>
                                    <td>{{ $venta->numero_factura_display }}</td>
                                    <td>{{ $venta->cliente->nombre ?? 'N/A' }}</td>
                                    <td>{{ $venta->fecha_venta->format('d/m/Y') }}</td>
                                    <td>{{ number_format($venta->total, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $venta->estado == 'COBRADO' ? 'success' : 'warning' }}">
                                            {{ $venta->estado }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($timbrado->ventas->count() > 10)
                    <p class="text-center text-muted mt-2">
                        Mostrando las últimas 10 facturas de {{ $timbrado->ventas->count() }} en total
                    </p>
                    @endif
                @else
                    <p class="text-center text-muted">No hay facturas asociadas a este timbrado</p>
                @endif
            </div>
        </div>

    </div>
@endsection
