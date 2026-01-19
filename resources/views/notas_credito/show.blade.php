@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-6">
                    <h1>
                        Nota de Crédito Detalle
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="float-right btn btn-default"
                       href="{{ route('notas_credito.index') }}">
                       <i class="fas fa-arrow-left"></i> Atras
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <!-- Nro Nota Field -->
                    <div class="col-sm-3">
                        {!! Form::label('nro_nota', 'Nro Nota:') !!}
                        <p>{{ $nota->nro_nota }}</p>
                    </div>

                    <!-- Fecha Field -->
                    <div class="col-sm-3">
                        {!! Form::label('fecha', 'Fecha Emisión:') !!}
                        <p>{{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</p>
                    </div>

                    <!-- Cliente Field -->
                    <div class="col-sm-6">
                        {!! Form::label('cliente', 'Cliente:') !!}
                        <p>{{ $nota->cliente->clie_nombre ?? '' }} {{ $nota->cliente->clie_apellido ?? '' }} - {{ $nota->cliente->clie_ci ?? 'S/CI' }}</p>
                    </div>

                    <!-- Documento Relacionado Field -->
                    <div class="col-sm-3">
                        {!! Form::label('venta', 'Doc. Relacionado (Factura):') !!}
                        <p>
                            @if($nota->venta)
                                <a href="{{ route('ventas.show', $nota->id_venta) }}" target="_blank">
                                    {{ $nota->venta->factura_nro }}
                                </a>
                            @else
                                <span class="text-muted">Sin referencia</span>
                            @endif
                        </p>
                    </div>

                     <!-- Estado Field -->
                     <div class="col-sm-3">
                        {!! Form::label('estado', 'Estado:') !!}
                        <p>
                            <span class="badge bg-{{ $nota->estado == 'COMPLETADO' ? 'info' : ($nota->estado == 'ANULADO' ? 'danger' : 'success') }}">
                                {{ $nota->estado }}
                            </span>
                        </p>
                    </div>

                    <!-- Usuario Field -->
                    <div class="col-sm-3">
                        {!! Form::label('user', 'Generado por:') !!}
                        <p>{{ $nota->usuario->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <hr>

                <!-- Motivo Field -->
                <div class="row mb-3">
                    <div class="col-sm-12">
                        @if($nota->motivo)
                            <strong>Motivo:</strong>
                            <p class="text-muted">{{ $nota->motivo }}</p>
                        @endif
                    </div>
                </div>

                <!-- Detalles Table -->
                <div class="row mt-4">
                    <div class="col-md-7">
                        <h4>Items Detallados</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-right">Precio</th>
                                        <th class="text-right">Exenta</th>
                                        <th class="text-right">5%</th>
                                        <th class="text-right">10%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nota->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->producto->id_producto ?? '' }}</td>
                                        <td>{{ $detalle->producto->descripcion ?? 'Producto no encontrado' }}</td>
                                        <td class="text-center">{{ number_format($detalle->cantidad, 2, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($detalle->precio, 0, ',', '.') }}</td>
                                        
                                        <td class="text-right">
                                            @if($detalle->id_tax == 1) {{-- Exento --}} 
                                                {{ number_format($detalle->cantidad * $detalle->precio, 0, ',', '.') }}
                                            @else 0 @endif
                                        </td>
                                        <td class="text-right">
                                            @if($detalle->id_tax == 2) {{-- 5% --}}
                                                {{ number_format($detalle->cantidad * $detalle->precio, 0, ',', '.') }}
                                            @else 0 @endif
                                        </td>
                                        <td class="text-right">
                                            @if($detalle->id_tax == 3 || $detalle->id_tax == null) {{-- 10% defaults --}}
                                                {{ number_format($detalle->cantidad * $detalle->precio, 0, ',', '.') }}
                                            @else 0 @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <h4>Aplicaciones (Descuentos en Cuotas)</h4>
                        <!-- Here we show Cobros with id_nota_credito == this nota, but these cobros are linked to this nota in Controller store -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="bg-warning">
                                    <tr>
                                        <th>Referencia</th>
                                        <th class="text-right">Monto Aplicado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($nota->cobros as $cobro)
                                    <tr>
                                        <td>
                                            @if($cobro->cuentaACobrar)
                                                Cuota Nro: {{ $cobro->cuentaACobrar->numero_cuota ?? 'N/A' }} 
                                                <small>(Venc: {{ \Carbon\Carbon::parse($cobro->cuentaACobrar->fecha_vencimiento)->format('d/m/Y') }})</small>
                                            @else
                                                Pago Directo / Reembolso
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($cobro->cobro_importe, 0, ',', '.') }} Gs.</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No se aplicó a cuotas específicas (Posible reembolso o pago directo)</td>
                                    </tr>
                                    @endforelse
                                    @if(count($nota->cobros) > 0)
                                    <tr class="font-weight-bold">
                                        <td class="text-right">Total Aplicado:</td>
                                        <td class="text-right">{{ number_format($nota->cobros->sum('cobro_importe'), 0, ',', '.') }} Gs.</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Desglose de Impuestos -->
                <div class="col-sm-12 mt-3">
                    <div class="card">
                        <div class="card-header bg-info">
                            <h5 class="mb-0">Desglose de Impuestos (IVA)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Total Exento (0%):</strong></td>
                                            <td class="text-right">{{ number_format($nota->total_exento ?? 0, 0, ',', '.') }} Gs.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Gravado 5%:</strong></td>
                                            <td class="text-right">{{ number_format($nota->total_gravado_5 ?? 0, 0, ',', '.') }} Gs.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Gravado 10%:</strong></td>
                                            <td class="text-right">{{ number_format($nota->total_gravado_10 ?? 0, 0, ',', '.') }} Gs.</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>IVA 5%:</strong></td>
                                            <td class="text-right">{{ number_format($nota->total_iva_5 ?? 0, 0, ',', '.') }} Gs.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>IVA 10%:</strong></td>
                                            <td class="text-right">{{ number_format($nota->total_iva_10 ?? 0, 0, ',', '.') }} Gs.</td>
                                        </tr>
                                        <tr class="table-active">
                                            <td><strong>Total IVA:</strong></td>
                                            <td class="text-right"><strong>{{ number_format($nota->total_iva ?? 0, 0, ',', '.') }} Gs.</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="text-right">
                                        <strong>TOTAL OTORGADO: {{ number_format($nota->total, 0, ',', '.') }} Gs.</strong>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
