@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Notas de Débito</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('notas_debito.create') }}">
                        <i class="fas fa-plus"></i>
                        Nueva Nota de Débito
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        <div class="card tabla-container">
            @include('notas_debito.table')
        </div>
    </div>
@endsection

@push('scripts')
<script>
    @if(session('print_nota_id'))
        Swal.fire({
            title: 'Nota de Débito Registrada',
            text: "¿Desea imprimir el comprobante?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, imprimir',
            cancelButtonText: 'Cerrar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('{{ route('notas_debito.pdf', session('print_nota_id')) }}', '_blank');
            }
        });
    @endif
</script>
@endpush
