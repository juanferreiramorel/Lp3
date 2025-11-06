@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sucursales</h1>
                </div>
                <div class="col-sm-6">
                    <!-- Botón Agregar Sucursal -->
                    <a class="btn btn-primary float-right mr-2"
                       href="{{ route('sucursales.create') }}">
                       <i class="fas fa-plus"></i>
                        Agregar Sucursal
                    </a>
                    
                    <!-- Botón Agregar Ciudad que abre en nueva pestaña -->
                    <a class="btn btn-success float-right",
                       style="margin-right: 5px;",
                       title="Agregar Ciudad",
                       href="{{ route('ciudades.create') }}"
                       target="_blank">
                       <i class="fas fa-plus"></i>
                       
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix">
            @includeIf('layouts.buscador', ['url' => url()->current()])
        </div>

        <div class="card tabla-container">
            @include('sucursales.table')
        </div>
    </div>

@endsection
