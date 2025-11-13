@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Plantillas de Contratos</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('plantillas_contratos.create') }}">
                        <i class="fas fa-plus"></i>
                        Agregar Plantilla
                    </a>
                    <a class="btn btn-info float-right mr-2" href="{{ route('plantillas_contratos.contratos_generados') }}">
                        <i class="fas fa-file-contract"></i>
                        Contratos Generados
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
            @include('plantillas_contratos.table')
        </div>
    </div>

@endsection
