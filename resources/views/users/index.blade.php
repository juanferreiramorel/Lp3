@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Listado de Usuarios</h1>
                </div>
                <div class="col-sm-6">
                    @can('users create')
                        <a class="btn btn-primary float-right"
                        href="{{ route('users.create') }}">
                        <i class="fas fa-plus"></i>
                            Nuevo Usuario
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

        <div class="card tabla-container">
            @include('users.table')
        </div>
    </div>

@endsection
