@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-12">
                    <h1>Editar Recibo Simple</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="px-3 content">
        @include('adminlte-templates::common.errors')

        <div class="card">
            {!! Form::model($recibo, ['route' => ['recibossimples.update', $recibo->id_recibo], 'method' => 'patch']) !!}

            <div class="card-body">
                <div class="row">
                    @include('recibossimples.fields')
                </div>
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                        {!! Form::submit('Actualizar', ['class' => 'btn btn-primary btn-block']) !!}
                    </div>
                    <div class="col-12 col-sm-auto">
                        <a href="{{ route('recibossimples.index') }}" class="btn btn-default btn-block"> Cancelar </a>
                    </div>
                </div>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
@endsection
