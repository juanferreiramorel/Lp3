@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 col-12 mb-2 mb-sm-0">
                    <h1>Recibos Simples</h1>
                </div>
                <div class="col-sm-6 col-12">
                    @can('recibossimples create')
                        <a class="btn btn-primary float-sm-right btn-block d-sm-inline-block" href="{{ route('recibossimples.create') }}">
                            <i class="fas fa-plus"></i>
                            Nuevo Recibo
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')

        @includeIf('recibossimples.filtros', [
            'clientes' => $clientes,
            'sucursales' => $sucursales,
            'estados' => $estados
        ])

        <div class="card tabla-container">
            @include('recibossimples.table')
        </div>
    </div>
@endsection
