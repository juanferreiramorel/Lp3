@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Métodos de Pago</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right"
                       href="{{ route('metodo_pagos.create') }}">
                       <i class="fas fa-plus"></i>
                        Nuevo Método
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="clearfix">
            @include('metodo_pagos.filtros')
        </div>

        <div class="card tabla-container">
            @include('metodo_pagos.table')
        </div>
    </div>

@endsection
