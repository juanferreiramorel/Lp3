@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Cargos</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('cargos.create') }}">
                        <i class="fas fa-plus"></i>
                        Nuevo Cargo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="clearfix">
            @include('cargos.filtros')
        </div>

        <!-- agregar la clase tabla-container para mostrar los valores filtrados de table-->
        <div class="card tabla-container">
            @include('cargos.table')
        </div>
    </div>
@endsection
