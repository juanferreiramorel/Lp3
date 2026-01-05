@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Pagar Cuota</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('cuentasapagar.index') }}">Cuentas a Pagar</a></li>
                        <li class="breadcrumb-item active">Pagar</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title">Detalle del Pago - Factura N° {{ $compra->factura }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-building"></i> Proveedor</h5>
                        <p>
                            <strong>Nombre:</strong> {{ $proveedor->descripcion }}<br>
                            <strong>RUC:</strong> {{ $proveedor->ruc }}<br>
                            <strong>Teléfono:</strong> {{ $proveedor->telefono }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-file-invoice-dollar"></i> Detalle de la Cuota</h5>
                        <p>
                            <strong>N° Cuota:</strong> {{ $cuenta->nro_cuota }}<br>
                            <strong>Vencimiento:</strong> {{ \Carbon\Carbon::parse($cuenta->vencimiento)->format('d/m/Y') }}<br>
                            <strong>Importe Cuota:</strong> Gs. {{ number_format($cuenta->importe, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <hr>

                <div class="alert alert-info">
                    <h4><i class="icon fas fa-info"></i> Saldo Pendiente: Gs. <span id="saldo-pendiente-header">{{ number_format($saldo_pendiente, 0, ',', '.') }}</span></h4>
                </div>

                <form id="form-pagar" method="POST">
                    @csrf
                    <input type="hidden" name="id_cta" value="{{ $cuenta->id_cta }}">
                    <input type="hidden" id="saldo_total" value="{{ $saldo_pendiente }}">

                    <div class="row mb-3">
                         <div class="col-md-4">
                            <label>Fecha de Pago:</label>
                            <input type="date" class="form-control" name="fecha_pago" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <h4>Métodos de Pago</h4>
                    <div id="lista-pagos">
                        <!-- Primer metodo de pago por defecto -->
                        <div class="row pago-item mb-2" id="pago-row-0">
                            <div class="col-md-4">
                                <label>Método</label>
                                <select class="form-control select-metodo" name="pagos[0][id_metodo_pago]" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($metodos_pago as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Monto</label>
                                <input type="text" class="form-control text-right input-monto" name="pagos[0][monto]" 
                                       placeholder="0" onkeyup="formatCobro(this); calcularTotalPagado()" required>
                            </div>
                            <div class="col-md-4">
                                <label>N° Voucher / Ref.</label>
                                <input type="text" class="form-control" name="pagos[0][nro_voucher]" placeholder="Opcional">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-remove-pago" disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-info btn-sm" id="btn-add-pago">
                                <i class="fas fa-plus"></i> Agregar otro medio de pago
                            </button>
                        </div>
                    </div>

                    <div class="row mt-4 justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <th>Total a Pagar:</th>
                                    <td class="text-right"><h5 id="total-pagar-sum">0</h5></td>
                                </tr>
                                <tr>
                                    <th>Saldo Restante:</th>
                                    <td class="text-right text-danger"><h5 id="saldo-restante-sum">{{ number_format($saldo_pendiente, 0, ',', '.') }}</h5></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary float-right" id="btn-procesar">
                            <i class="fas fa-check"></i> Procesar Pago e Imprimir Recibo
                        </button>
                        <a href="{{ route('cuentasapagar.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let contadorPagos = 1;
    let saldoTotal = parseFloat($('#saldo_total').val());

    $(document).ready(function() {
        $('#btn-add-pago').click(function() {
            let template = `
                <div class="row pago-item mb-2" id="pago-row-${contadorPagos}">
                    <div class="col-md-4">
                        <select class="form-control select-metodo" name="pagos[${contadorPagos}][id_metodo_pago]" required>
                            <option value="">Seleccione...</option>
                            @foreach($metodos_pago as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control text-right input-monto" name="pagos[${contadorPagos}][monto]" 
                               placeholder="0" onkeyup="formatCobro(this); calcularTotalPagado()" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="pagos[${contadorPagos}][nro_voucher]" placeholder="Opcional">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-remove-pago" onclick="removerPago(${contadorPagos})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#lista-pagos').append(template);
            contadorPagos++;
        });

        // Form Submit
        $('#form-pagar').on('submit', function(e) {
            e.preventDefault();
            
            // Validar montos
            let totalIngresado = 0;
            let error = false;
            
            $('.input-monto').each(function() {
                let val = $(this).val().replace(/\./g, '');
                if(val === '' || parseFloat(val) <= 0) {
                    error = true;
                }
                totalIngresado += parseFloat(val || 0);
            });

            if(error) {
                Swal.fire('Error', 'Por favor ingrese montos válidos mayores a 0', 'error');
                return;
            }

            if(totalIngresado > saldoTotal) {
                Swal.fire('Error', 'El total a pagar no puede superar el saldo pendiente', 'error');
                return;
            }
            
            if(totalIngresado <= 0) {
                 Swal.fire('Error', 'Debe ingresar al menos un pago', 'error');
                return;
            }

            // AJAX Submit
            let formData = $(this).serialize();
            let btn = $('#btn-procesar');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                url: '{{ route("cuentasapagar.procesar_multi") }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Pago Exitoso',
                            text: 'El pago se ha registrado correctamente',
                            icon: 'success'
                        }).then(() => {
                            // Imprimir recibo(s)
                            if(response.url_recibo) {
                                window.open(response.url_recibo, '_blank');
                            }
                            window.location.href = "{{ route('cuentasapagar.index') }}";
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                        btn.prop('disabled', false).html('<i class="fas fa-check"></i> Procesar Pago e Imprimir Recibo');
                    }
                },
                error: function(xhr) {
                    let msg = 'Ocurrió un error al procesar';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error', msg, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check"></i> Procesar Pago e Imprimir Recibo');
                }
            });
        });
    });

    function removerPago(id) {
        $('#pago-row-' + id).remove();
        calcularTotalPagado();
    }

    function formatCobro(input) {
        var value = input.value.replace(/\./g, '');
        if (!isNaN(value) && value !== '') {
            input.value = new Intl.NumberFormat('es-PY').format(value);
        }
    }

    function calcularTotalPagado() {
        let total = 0;
        $('.input-monto').each(function() {
            let val = $(this).val().replace(/\./g, '');
            total += parseFloat(val || 0);
        });
        
        $('#total-pagar-sum').text(new Intl.NumberFormat('es-PY').format(total));
        
        let restante = saldoTotal - total;
        if(restante < 0) restante = 0;
        
        $('#saldo-restante-sum').text(new Intl.NumberFormat('es-PY').format(restante));
        
        if(total > saldoTotal) {
            $('#saldo-restante-sum').addClass('text-danger');
            $('#total-pagar-sum').addClass('text-danger');
        } else {
            $('#saldo-restante-sum').removeClass('text-danger');
             $('#total-pagar-sum').removeClass('text-danger');
        }
    }
</script>
@endpush
