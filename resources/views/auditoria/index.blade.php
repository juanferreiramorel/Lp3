@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Auditoria</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        @includeIf('auditoria.filtros', [
            'usuarios' => $usuarios,
            'sucursales' => $sucursales,
            'operaciones' => $operaciones,
            'tablas' => $tablas
        ])

        <!-- agregar la clase tabla-container para mostrar los valores filtrados de table-->
        <div class="card tabla-container">
            @include('auditoria.table')
        </div>
    </div>
@endsection
