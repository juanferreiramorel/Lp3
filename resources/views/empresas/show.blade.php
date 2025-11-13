@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detalle de Empresa</h1>
                </div>
                <div class="col-sm-6">
                    @can('empresas edit')
                    <a class="btn btn-primary float-right mr-2"
                       href="{{ route('empresas.edit', $empresa->id_empresa) }}">
                       <i class="fas fa-edit"></i>
                        Editar
                    </a>
                    @endcan
                    <a class="btn btn-default float-right mr-2"
                       href="{{ route('empresas.index') }}">
                       <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @include('empresas.show_fields')
                </div>
            </div>
        </div>
    </div>
@endsection
