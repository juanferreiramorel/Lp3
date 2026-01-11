@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cash-register"></i> Gestión de Caja</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('ventas.index') }}">
                        <i class="fas fa-arrow-left"></i> Volver a Ventas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        <div class="row">
            <!-- Info Caja Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                        </div>
                        <h3 class="profile-username text-center">{{ $apertura->usuario }}</h3>
                        <p class="text-muted text-center">{{ $apertura->sucursal }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Caja</b> <a class="float-right">{{ $apertura->caja }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Fecha Apertura</b> <a class="float-right">{{ \Carbon\Carbon::parse($apertura->fecha_apertura)->format('d/m/Y') }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Estado</b> 
                                <a class="float-right">
                                    <span class="badge badge-{{ $apertura->estado == 'ABIERTA' ? 'success' : 'danger' }}">
                                        {{ $apertura->estado }}
                                    </span>
                                </a>
                            </li>
                            @if($apertura->fecha_cierre)
                            <li class="list-group-item">
                                <b>Fecha Cierre</b> <a class="float-right">{{ \Carbon\Carbon::parse($apertura->fecha_cierre)->format('d/m/Y') }}</a>
                            </li>
                            @endif
                        </ul>

                        @if($apertura->estado == 'ABIERTA')
                            <!-- Botones de Acción -->
                             <div class="btn-group w-100 mb-2">
                                <a href="{{ url('apertura_cierre/imprimir_cierre/' . $apertura->id_apertura) }}" target="_blank" class="btn btn-info">
                                    <i class="fas fa-print"></i> Pre-Arqueo (PDF)
                                </a>
                            </div>

                            <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalCierre">
                                <i class="fas fa-lock"></i> <b>Cerrar Caja</b>
                            </button>
                        @else
                             <a href="{{ url('apertura_cierre/imprimir_cierre/' . $apertura->id_apertura) }}" target="_blank" class="btn btn-default btn-block">
                                <i class="fas fa-print"></i> Imprimir Reporte Cierre
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Resumen Financiero -->
            <div class="col-md-8">
                <!-- Tarjetas de Totales -->
                <div class="row">
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Monto Apertura</span>
                                <span class="info-box-number">{{ number_format($apertura->monto_apertura, 0, ',', '.') }} Gs.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Recaudado</span>
                                <span class="info-box-number">{{ number_format($total_general_recaudado, 0, ',', '.') }} Gs.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Esperado</span>
                                <span class="info-box-number">{{ number_format($total_esperado, 0, ',', '.') }} Gs.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle por medio de pago -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Desglose de Recaudación</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Forma de Pago</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($totales_consolidados as $forma => $monto)
                                    <tr>
                                        <td>{{ $forma }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($monto, 0, ',', '.') }} Gs.</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No se registraron movimientos aún.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-light">
                                    <td class="text-right"><strong>TOTAL RECAUDADO:</strong></td>
                                    <td class="text-right"><strong class="text-success">{{ number_format($total_general_recaudado, 0, ',', '.') }} Gs.</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="alert alert-info alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <h5><i class="icon fas fa-info"></i> Nota:</h5>
                  El "Total Recaudado" incluye cobros de Ventas y Notas de Débito de la fecha.
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Cierre de Caja -->
    <div class="modal fade" id="modalCierre" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Cierre de Caja</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form-cierre" method="POST" action="{{ url('apertura_cierre/cerrar_caja/' . $apertura->id_apertura) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="lead text-center">Resumen Final</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td>Monto Apertura:</td>
                                    <td class="text-right">{{ number_format($apertura->monto_apertura, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Recaudación:</td>
                                    <td class="text-right">{{ number_format($total_general_recaudado, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-light font-weight-bold">
                                    <td>Total en Caja:</td>
                                    <td class="text-right">{{ number_format($total_esperado, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="form-group mt-3">
                            <label for="monto_cierre">Monto de Cierre (Lo que realmente hay en caja):</label>
                            <input type="text" class="form-control form-control-lg text-center" name="monto_cierre" id="monto_cierre" 
                                value="{{ number_format($total_esperado, 0, ',', '.') }}" required onkeyup="formatMoney(this)">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btn-confirmar-cierre">
                            <i class="fas fa-check"></i> Proceder al Cierre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function formatMoney(input) {
        let value = input.value.replace(/\./g, '').replace(/,/g, '');
        value = value.replace(/\D/g, '');
        
        if (value === '') {
            input.value = '';
            return;
        }
        
        // Format with thousand separators
        input.value = new Intl.NumberFormat('es-PY').format(parseInt(value));
    }

    $(document).ready(function() {
        $('#form-cierre').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = $('#btn-confirmar-cierre');
            
            Swal.fire({
                title: '¿Está seguro?',
                text: "Una vez cerrada la caja, no podrá reabrirla ni registrar más ventas en ella.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, cerrar caja'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    
                    // Ajax Submit
                    $.ajax({
                        url: $(form).attr('action'),
                        method: 'POST',
                        data: $(form).serialize(),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Cerrada!', response.message, 'success').then(() => {
                                    // Open report
                                    window.open("{{ url('apertura_cierre/imprimir_cierre/' . $apertura->id_apertura) }}", '_blank');
                                    // Reload page
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Proceder al Cierre');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Hubo un problema al cerrar la caja', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-check"></i> Proceder al Cierre');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
