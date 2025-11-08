@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Órdenes de Compra</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right"
                       href="{{ route('ordencompra.create') }}">
                       <i class="fas fa-plus"></i>
                        Nueva Orden de Compra
                    </a>
                </div>
            </div>
        </div>
    </section>


    <div class="content px-3">
       @include('sweetalert::alert')

        @includeIf('ordencompra.filtros', [
            'proveedores' => $proveedores,
            'sucursales' => $sucursales,
            'estados' => $estados
        ])

        <div class="card tabla-container">
            @include('ordencompra.table')
        </div>
    </div>
@endsection
