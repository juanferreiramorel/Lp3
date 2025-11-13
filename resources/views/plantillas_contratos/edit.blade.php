@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        <i class="fas fa-file-alt"></i>
                        Editar Plantilla de Contrato
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')

        <div class="card">

            {!! Form::model($plantilla, ['route' => ['plantillas_contratos.update', $plantilla->id_plantilla_contrato], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('plantillas_contratos.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('plantillas_contratos.index') }}" class="btn btn-default"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
