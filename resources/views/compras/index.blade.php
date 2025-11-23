@extends('layouts.app')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Compras</h1></div>
      <div class="col-sm-6">
        <a class="btn btn-primary float-right" href="{{ route('compras.create') }}">
          <i class="fas fa-plus"></i> Nueva Compra
        </a>
        <a class="btn btn-info float-right mr-2" data-toggle="modal" data-target="#modalOrdenes" href="#">
          <i class="fas fa-file-invoice"></i> Incluir Orden de Compra
        </a>
      </div>
    </div>
  </div>
</section>

<div class="content px-3">
  @includeIf('compras.filtros', [
      'proveedores' => $proveedores,
      'sucursales' => $sucursales,
      'condiciones_compra' => $condiciones_compra
  ])

  <div class="card tabla-container">
    @include('compras.table')
  </div>
</div>

@includeIf('compras.modal_ordenes')
@endsection