@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detalles del Impuesto</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('taxes.index') }}">
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
                    <div class="col-sm-6">
                        <h5>ID:</h5>
                        <p>{{ $tax->id_tax }}</p>
                    </div>
                    <div class="col-sm-6">
                        <h5>Nombre:</h5>
                        <p>{{ $tax->nombre }}</p>
                    </div>
                    <div class="col-sm-6">
                        <h5>Porcentaje:</h5>
                        <p>{{ number_format($tax->porcentaje, 2) }}%</p>
                    </div>
                    <div class="col-sm-6">
                        <h5>Estado:</h5>
                        <p>
                            @if($tax->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-12">
                        <h5>Descripción:</h5>
                        <p>{{ $tax->descripcion ?? 'Sin descripción' }}</p>
                    </div>
                    <div class="col-sm-6">
                        <h5>Fecha de Creación:</h5>
                        <p>{{ $tax->created_at }}</p>
                    </div>
                    <div class="col-sm-6">
                        <h5>Última Actualización:</h5>
                        <p>{{ $tax->updated_at }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(count($productos) > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Productos con este impuesto ({{ count($productos) }})</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Marca</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $producto)
                            <tr>
                                <td>{{ $producto->id_producto }}</td>
                                <td>{{ $producto->descripcion }}</td>
                                <td>{{ number_format($producto->precio, 0, ',', '.') }}</td>
                                <td>{{ $producto->marcas }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            <i class="icon fas fa-info"></i>
            No hay productos asociados a este impuesto.
        </div>
        @endif
    </div>
@endsection
