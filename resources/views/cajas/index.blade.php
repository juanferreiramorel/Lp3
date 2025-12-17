@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Cajas</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right"
                       href="{{ route('cajas.create') }}">
                       <i class="fas fa-plus"></i>
                        Nueva Caja
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        {{-- @include('flash::message') --}}

        <div class="clearfix">
            @include('cajas.filtros')
        </div>

        <div class="card tabla-container">
            @include('cajas.table')
        </div>
    </div>

@endsection
