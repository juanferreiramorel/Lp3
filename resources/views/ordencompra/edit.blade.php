@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Editar Orden de Compra</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <div class="card">
            {!! Form::model($ordenCompra, ['route' => ['ordencompra.update', $ordenCompra->id_orden_compra], 'method' => 'patch', 'id' => 'form-ordencompra']) !!}

            <div class="card-body">
                @include('ordencompra.fields')
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('ordencompra.index') }}" class="btn btn-default">Cancelar</a>
            </div>

            {!! Form::close() !!}
        </div>
    </div>
@endsection
