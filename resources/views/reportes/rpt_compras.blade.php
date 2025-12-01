@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Reportes Compras</h1>
                </div>

            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <!-- filtro de proveedores -->
                    <div class="form-group col-sm-3">
                        {!! Form::label('proveedor', 'Proveedores:') !!}
                        {!! Form::select('proveedor', $proveedores, request()->get('proveedor', null), [
                            'class' => 'form-control',
                            'placeholder' => 'Seleccione',
                            'id' => 'proveedores',
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
                    <table class="table" id="compras-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Proveedor</th>
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
                            @forelse ($compras as $compra)
                                <tr>
                                    <td>{{ $compra->id_compra }}</td>
                                    <td>{{ $compra->proveedor }}</td>
                                    <td>{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</td>
                                    <td>{{ $compra->condicion_compra }}</td>
                                    <td>{{ number_format($compra->total, 0, ',', '.') }}</td>
                                    <td>{{ $compra->estado }}</td>
                                    <td>{{ $compra->factura }}</td>
                                    <td>{{ $compra->sucursal }}</td>
                                    <td>{{ $compra->usuario }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    Mostrando {{ count($compras) }} de {{ $total }} registros
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    @php
                        $params = [];
                        if(request()->get('proveedor')) $params['proveedor'] = request()->get('proveedor');
                        if(request()->get('desde')) $params['desde'] = request()->get('desde');
                        if(request()->get('hasta')) $params['hasta'] = request()->get('hasta');
                        $queryString = http_build_query($params);
                    @endphp
                    @if($page > 1)
                        <li class="page-item"><a class="page-link" href="?{{ $queryString }}&page={{ $page - 1 }}">«</a></li>
                    @endif

                    @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
                        <li class="page-item {{ $i == $page ? 'active' : '' }}">
                            <a class="page-link" href="?{{ $queryString }}&page={{ $i }}">{{ $i }}</a>
                        </li>
                    @endfor

                    @if($page < $totalPages)
                        <li class="page-item"><a class="page-link" href="?{{ $queryString }}&page={{ $page + 1 }}">»</a></li>
                    @endif
                </ul>
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
                $('#proveedores').val('');
                window.location.href = '{{ url('reporte-compras') }}';
            });

            $("#btn-consultar").click(function(e) {
                e.preventDefault();
                window.location.href = '{{ url('reporte-compras') }}?proveedor=' + $("#proveedores").val()+'&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val();
            });

            $("#btn-exportar").click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-compras') }}?proveedor=' + $("#proveedores").val()+
                '&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val()+'&exportar=pdf', '_blank');
            });

            $("#btn-exportar-excel").click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-compras') }}?proveedor=' + $("#proveedores").val()+
                '&desde='+$('#desde').val()+
                '&hasta='+$('#hasta').val()+'&exportar=excel', '_blank');
            });
        })
    </script>
@endpush

