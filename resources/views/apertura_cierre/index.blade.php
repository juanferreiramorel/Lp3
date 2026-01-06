@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Arqueo de Caja</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        @include('apertura_cierre.filtros')

        <div class="card tabla-container">
            @include('apertura_cierre.table')
        </div>
    </div>
@endsection
