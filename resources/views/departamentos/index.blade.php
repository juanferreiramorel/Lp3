@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Departamentos</h1>
                </div>
                <div class="col-sm-6">
                    @can('departamentos create')
                    <a class="btn btn-primary float-right"
                       href="{{ route('departamentos.create') }}">
                        <i class="fas fa-plus"></i>
                        Nuevo Departamento
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

         <div class="clearfix">
            @includeIf('layouts.buscador', ['url' => url()->current()])
        </div>

        <!-- agregar la clase tabla-container para mostrar los valores filtrados de table-->
        <div class="card tabla-container">
            @include('departamentos.table')
        </div>
    </div>

@endsection
