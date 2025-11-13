@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-6 col-12 mb-2 mb-sm-0">
                    <h1>Detalle del Recibo</h1>
                </div>
                <div class="col-sm-6 col-12">
                    <a class="float-sm-right btn btn-default btn-block d-sm-inline-block"
                       href="{{ route('recibossimples.index') }}">
                       <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @include('recibossimples.show_fields')
                </div>
            </div>
        </div>
    </div>
@endsection
