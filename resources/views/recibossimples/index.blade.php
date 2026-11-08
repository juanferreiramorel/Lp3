@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6 col-12 mb-2 mb-sm-0">
                    <h1>Recibos Simples</h1>
                </div>
                <div class="col-sm-6 col-12 d-flex align-items-center justify-content-sm-end">
                    @can('recibossimples create')
                        {{-- Sized to its label. It used to stretch the full width of
                             the column, which made one ordinary action the loudest
                             thing on the page. --}}
                        <a class="btn btn-primary d-inline-flex align-items-center" href="{{ route('recibossimples.create') }}">
                            <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                            Nuevo Recibo
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

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
