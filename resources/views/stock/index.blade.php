@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Stock</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @includeIf('stock.filtros', [
            'sucursales' => $sucursales,
            'productos' => $productos
        ])

        <div class="card tabla-container">
            @include('stock.table')
        </div>
    </div>
@endsection
