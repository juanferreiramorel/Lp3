@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Notas de Crédito</h1>
                </div>
                <div class="col-sm-6">
                    <!-- Boton Nueva Nota -->
                    <!-- Verificar si la caja esta abierta y es del dia actual entonces muestro el boton nueva -->
                    @if (
                        !empty($caja_abierta) &&
                            \Carbon\Carbon::parse($caja_abierta->fecha_apertura)->format('Y-m-d') == \Carbon\Carbon::now()->format('Y-m-d'))
                        <a class="btn btn-primary float-right" href="{{ route('notas_credito.create') }}">
                            <i class="fas fa-plus"></i>
                            Nueva Nota de Crédito
                        </a>
                    @endif

                    <!-- Boton Abrir Caja -->
                    <!-- Verificar si la caja esta cerrada entonces muestro el boton abrir caja -->
                    @if (empty($caja_abierta))
                        <a class="btn btn-default float-right mr-2" data-toggle="modal" data-target="#apertura"
                            href="#">
                            <i class="fas fa-cart-plus"></i>
                            Abrir Caja
                        </a>
                    @endif

                    <!-- Boton Ver Caja (Dashboard) -->
                    <!-- Verificar si la caja esta abierta y es del dia actual entonces muestro el boton ver caja -->
                    @if (
                        !empty($caja_abierta) &&
                            \Carbon\Carbon::parse($caja_abierta->fecha_apertura)->format('Y-m-d') <= \Carbon\Carbon::now()->format('Y-m-d'))
                        <a class="btn btn-warning float-right mr-2" href="{{ route('apertura-cierre-caja.show', [$caja_abierta->id_apertura]) }}?return_url=notas_credito.index">
                            <i class="fas fa-cash-register"></i>
                            Ver Caja
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        {{-- Uncomment filters when created --}}
        @include('notas_credito.filtros', [
            'clientes' => $clientes,
            'sucursales' => $sucursales,
            'estados' => $estados
        ])

        <div class="card tabla-container">
            @include('notas_credito.table')
        </div>

        <!-- Modal Apertura Caja y compartir la variable cajas-->
        <x-modal_apertura_caja :cajas="$cajas" />
    </div>
@endsection

@push('scripts')
<script>
    @if(session('print_nota_id'))
        Swal.fire({
            title: 'Nota de Crédito Registrada',
            text: "¿Desea imprimir el comprobante?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, imprimir',
            cancelButtonText: 'Cerrar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('{{ route('notas_credito.pdf', session('print_nota_id')) }}', '_blank');
            }
        });
    @endif
</script>
@endpush
