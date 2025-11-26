@extends('layouts.app')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Compras</h1></div>
      <div class="col-sm-6">
        <button type="button" id="btn-export-excel-compras" class="btn btn-success float-right ml-2"
            data-toggle="tooltip" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </button>
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

@push('scripts')
<script>
    $(document).ready(function() {
        // Botón Exportar Excel
        $('#btn-export-excel-compras').on('click', function(e) {
            e.preventDefault();
            let url = '{{ url('compras') }}';
            let params = new URLSearchParams(window.location.search);
            params.set('exportar', 'excel');
            window.open(url + '?' + params.toString(), '_blank');
        });
    });
</script>
@endpush