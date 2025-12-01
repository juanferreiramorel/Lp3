@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Reporte de Sucursales</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <div class="clearfix"></div>
        <!-- Card de filtros -->
        <div class="card">
            <div class="card-body p-3">
                <h3>Filtros</h3>
                <div class="row">
                    <div class="form-group col-sm-3">
                        {!! Form::label('desde', 'Código Desde:') !!}
                        {!! Form::text('desde', request()->get('desde', null), [
                            'class' => 'form-control',
                            'placeholder' => 'Ingrese el código',
                            'id' => 'desde',
                        ]) !!}
                    </div>

                    <div class="form-group col-sm-3">
                        {!! Form::label('hasta', 'Código Hasta:') !!}
                        {!! Form::text('hasta', request()->get('hasta', null), [
                            'class' => 'form-control',
                            'placeholder' => 'Ingrese el código',
                            'id' => 'hasta',
                        ]) !!}
                    </div>

                    <div class="form-group col-sm-3">
                        <button class="btn btn-success" type="button" data-toggle="tooltip" data-placement="top"
                            title="Buscar" id="btn-consultar" style="margin-top:32px">
                            <i class="fas fa fa-search"></i>
                        </button>

                        <button class="btn btn-default" type="button" data-toggle="tooltip" title="Limpiar"
                            id="btn-limpiar" style="margin-top:32px">
                            <i class="fas fa fa-eraser"></i>
                        </button>

                        <button class="btn btn-primary" id="btn-exportar" type="button" data-toggle="tooltip"
                            title="Exportar a PDF" style="margin-top:32px">
                            <i class="fas fa-print"></i> PDF
                        </button>

                        <button class="btn btn-primary" id="btn-exportar-excel" type="button" data-toggle="tooltip"
                            title="Exportar a Excel" style="margin-top:32px">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin filtros -->

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" id="sucursales-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Descripción</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Ciudad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sucursales as $sucursal)
                                <tr>
                                    <td>{{ $sucursal->id_sucursal }}</td>
                                    <td>{{ $sucursal->descripcion }}</td>
                                    <td>{{ $sucursal->direccion }}</td>
                                    <td>{{ $sucursal->telefono }}</td>
                                    <td>{{ $sucursal->ciudad }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No se encontraron registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    Mostrando {{ count($sucursales) }} de {{ $total }} registros
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    @php
                        $params = [];
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
    <script>
        $(document).ready(function() {
            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Botón de consulta
            $('#btn-consultar').click(function(e) {
                e.preventDefault();
                window.location.href = '{{ url('reporte-sucursales') }}?desde=' + $('#desde').val() +
                    '&hasta=' + $('#hasta').val();
            });

            // Botón de exportación
            $('#btn-exportar').click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-sucursales') }}?desde=' + $('#desde').val() + '&hasta=' + $(
                    '#hasta').val() + '&exportar=pdf', '_blank');
            });

            // Botón de exportación a Excel
            $('#btn-exportar-excel').click(function(e) {
                e.preventDefault();
                window.open('{{ url('reporte-sucursales') }}?desde=' + $('#desde').val() + '&hasta=' + $(
                    '#hasta').val() + '&exportar=excel', '_blank');
            });

            // Botón de limpieza
            $('#btn-limpiar').click(function(e) {
                e.preventDefault();
                $('#desde').val('');
                $('#hasta').val('');
                window.location.href = '{{ url('reporte-sucursales') }}';
            });
        });
    </script>
@endpush
