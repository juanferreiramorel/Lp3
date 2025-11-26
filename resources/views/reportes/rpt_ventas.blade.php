@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Reportes Ventas</h1>
                </div>

            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <!-- filtro de clientes -->
                    <div class="form-group col-sm-3">
                        {!! Form::label('cliente', 'Clientes:') !!}
                        {!! Form::select('cliente', $clientes, request()->get('clientes', null), [
                            'class' => 'form-control',
                            'placeholder' => 'Seleccione',
                            'id' => 'clientes',
                        ]) !!}
                    </div>

                    <!-- parametro de fechas desde -->
                    <div class="form-group col-sm-3">
                        {!! Form::label('desde', 'Desde:') !!}
                        {!! Form::date('desde', request()->get('desde', null), ['class' => 'form-control', 'id' => 'desde']) !!}
                    </div>

                    <!-- parametro de fechas hasta -->
                    <div class="form-group col-sm-3">
                        {!! Form::label('hasta', 'Hasta:') !!}
                        {!! Form::date('hasta', request()->get('hasta', null), ['class' => 'form-control', 'id' => 'hasta']) !!}
                    </div>

                    <!-- parametro de buscar -->
                    <div class="form-group col-sm-3">
                        <!-- botones de buscar, limpiar y exportar -->
                        <button class="btn btn-success" type="button" title="Generar Reporte" id="btn-consultar"
                            style="margin-top:30px">
                            <i class="fas fa fa-search"></i>
                        </button>

                        <button class="btn btn-default" type="button"
                            style="margin-top:30px"
                            id="btn-limpiar"
                            data-toggle="tooltip" data-placement="top"
                            title="Limpiar">
                            <i class="fas fa-eraser"></i>
                        </button>

                        <button class="btn btn-primary" id="btn-exportar" type="button" data-toggle="tooltip"
                            title="Exportar a PDF" style="margin-top:30px">
                            <i class="fas fa-print"></i> PDF
                        </button>

                        <button class="btn btn-primary" id="btn-exportar-excel" type="button" data-toggle="tooltip"
                            title="Exportar a Excel" style="margin-top:30px">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" id="clientes-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Condición</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Nro Factura</th>
                                <th>Sucursal</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ventas as $venta)
                                <tr>
                                    <td>{{ $venta->id_venta }}</td>
                                    <td>{{ $venta->cliente }}</td>
                                    <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                                    <td>{{ $venta->condicion_venta }}</td>
                                    <td>{{ number_format($venta->total, 0, ',', '.') }}</td>
                                    <td>{{ $venta->estado }}</td>
                                    <td>{{ $venta->factura_nro }}</td>
                                    <td>{{ $venta->sucursal }}</td>
                                    <td>{{ $venta->usuario }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // boton para limpiar los filtros
            $('#btn-limpiar').click(function(e) {
                e.preventDefault();
                // limpiar filtros de los input
                $('#desde').val('');
                $('#hasta').val('');
                $('#clientes').val('');
                window.location.href = '{{ url('reporte-ventas') }}';
            });

            $("#btn-consultar").click(function(e) {
                e.preventDefault();
                window.location.href = '{{ url('reporte-ventas') }}?cliente=' + $("#clientes").val()+'&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val();
            });

            $("#btn-exportar").click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-ventas') }}?cliente=' + $("#clientes").val()+
                '&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val()+'&exportar=pdf', '_blank');
            });

            $("#btn-exportar-excel").click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-ventas') }}?cliente=' + $("#clientes").val()+
                '&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val()+'&exportar=excel', '_blank');
            });
        })
    </script>
@endpush
