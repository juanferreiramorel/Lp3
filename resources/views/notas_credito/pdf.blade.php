<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <title>Nota Crédito - {{ $nota->nro_nota }}</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 297mm;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #014224; /* Dark Green for Credit Note distinction */
        }

        .company-info {
            flex: 1;
        }

        .company-info h1 {
            color: #014224;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .company-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .company-logo {
            flex-shrink: 0;
            margin-left: 20px;
        }

        .company-logo img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
        }

        /* Client Info & Invoice Details */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .client-info {
            flex: 1;
            margin-right: 20px;
        }

        .client-info h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: bold;
            border-bottom: 2px solid #014224;
            padding-bottom: 5px;
            display: inline-block;
        }

        .client-info h4 {
            color: #014224;
            font-size: 18px;
            margin: 10px 0;
            font-weight: bold;
        }

        .client-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        .invoice-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            min-width: 280px;
        }

        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-details th {
            text-align: left;
            padding: 8px 10px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-details td {
            text-align: right;
            padding: 8px 10px;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-details tr:last-child th,
        .invoice-details tr:last-child td {
            border-bottom: none;
            font-weight: bold;
            color: #014224;
            font-size: 14px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #014224;
            color: white;
        }

        .items-table thead th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th:nth-child(3) {
            text-align: center;
        }

        .items-table thead th:nth-child(4),
        .items-table thead th:nth-child(5),
        .items-table thead th:nth-child(6) {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #ddd;
        }

        .items-table tbody tr:hover {
            background: #f9f9f9;
        }

        .items-table tbody td {
            padding: 10px;
            color: #555;
            font-size: 13px;
        }

        .items-table tbody td:nth-child(3) {
            text-align: center;
            font-weight: 600;
        }

        .items-table tbody td:nth-child(4),
        .items-table tbody td:nth-child(5),
        .items-table tbody td:nth-child(6) {
            text-align: right;
            font-weight: 500;
        }

        /* Total Section */
        .total-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 2px solid #014224;
            margin-bottom: 30px;
        }

        .total-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-section th {
            text-align: left;
            padding: 10px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .total-section td {
            text-align: right;
            padding: 10px;
            color: #333;
            font-size: 14px;
        }

        .total-section tr:first-child {
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
        }

        .total-section tr:last-child th,
        .total-section tr:last-child td {
            font-size: 18px;
            font-weight: bold;
            color: #014224;
            padding-top: 15px;
        }

        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .action-buttons .btn {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
        }

        /* Print Styles */
        @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                max-width: 100%;
                box-shadow: none;
                padding: 10mm;
                margin: 0;
            }

            header {
                page-break-after: avoid;
            }

            .items-table {
                page-break-inside: avoid;
            }

            .items-table thead {
                display: table-header-group;
            }

            .items-table tbody tr {
                page-break-inside: avoid;
            }

            .action-buttons {
                display: none !important;
            }

            .total-section {
                page-break-inside: avoid;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }

        /* Screen only styles */
        @media screen {
            .invoice-container {
                margin-top: 20px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <header>
            <div class="company-info">
                <h1>Nota de Crédito</h1>
                <p><strong>{{ $nota->sucursal->empresa->nombre_comercial ?? $nota->sucursal->empresa->razon_social ?? 'Empresa' }}</strong></p>
                @if($nota->sucursal->empresa->ruc ?? false)
                <p><strong>RUC:</strong> {{ $nota->sucursal->empresa->ruc }}</p>
                @endif
                @if($nota->sucursal->empresa->direccion ?? false)
                <p>{{ $nota->sucursal->empresa->direccion }}</p>
                @endif
                @if($nota->sucursal->empresa->telefono ?? false)
                <p>Tel: {{ $nota->sucursal->empresa->telefono }}</p>
                @endif
                @if($nota->sucursal->empresa->email ?? false)
                <p>Email: {{ $nota->sucursal->empresa->email }}</p>
                @endif
            </div>
            <div class="company-logo">
                @if($nota->sucursal->empresa->logo ?? false)
                <img alt="Logo" src="{{ asset('img/empresas/' . $nota->sucursal->empresa->logo) }}">
                @endif
            </div>
        </header>

        <div class="info-section">
            <div class="client-info">
                <h3>Cliente</h3>
                <h4>{{ $nota->cliente->clie_nombre ?? '' }} {{ $nota->cliente->clie_apellido ?? '' }}</h4>
                <p><strong>C.I./RUC:</strong> {{ $nota->cliente->clie_ci ?? 'S/N' }}</p>
                <p><strong>Dirección:</strong> {{ $nota->cliente->clie_direccion ?? 'Sin Dirección' }}</p>
                <p><strong>Teléfono:</strong> {{ $nota->cliente->clie_telefono ?? 'Sin Teléfono' }}</p>
            </div>

            <div class="invoice-details">
                <table>
                    <tr>
                        <th>Nro Nota</th>
                        <td>{{ $nota->nro_nota }}</td>
                    </tr>
                    @if($nota->timbrado)
                    <tr>
                        <th>Timbrado Nº</th>
                        <td>{{ $nota->timbrado->numero_timbrado }}</td>
                    </tr>
                    <tr>
                        <th>Vigencia</th>
                        <td>{{ \Carbon\Carbon::parse($nota->timbrado->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($nota->timbrado->fecha_vencimiento)->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Fecha Emisión</th>
                        <td>{{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</td>
                    </tr>
                     <tr>
                        <th>Doc Relacionado</th>
                        <td>{{ $nota->venta->factura_nro ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>{{ $nota->estado }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>IVA</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if (count($nota->detalles))
                    @foreach ($nota->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->id_producto }}</td>
                            <td>{{ $detalle->producto->descripcion ?? 'N/A' }}</td>
                            <td>{{ number_format($detalle->tax->porcentaje ?? 0, 0) }}%</td>
                            <td>{{ number_format($detalle->cantidad, 2, ',', '.') }}</td>
                            <td>Gs. {{ number_format($detalle->precio, 0, ',', '.') }}</td>
                            <td>Gs. {{ number_format($detalle->cantidad * $detalle->precio, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="total-section">
            <table>
                <tr>
                    <th>Exento (0%):</th>
                    <td>Gs. {{ number_format($nota->total_exento ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Gravado 5%:</th>
                    <td>Gs. {{ number_format($nota->total_gravado_5 ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Gravado 10%:</th>
                    <td>Gs. {{ number_format($nota->total_gravado_10 ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 1px solid #ddd;">
                    <th>IVA 5%:</th>
                    <td>Gs. {{ number_format($nota->total_iva_5 ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>IVA 10%:</th>
                    <td>Gs. {{ number_format($nota->total_iva_10 ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Total IVA:</th>
                    <td>Gs. {{ number_format($nota->total_iva ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 2px solid #014224;">
                    <th>TOTAL:</th>
                    <td>Gs. {{ number_format($nota->total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Total en letras:</th>
                    <td colspan="2">{{ $numero_a_letras }}</td>
                </tr>
                @if($nota->motivo)
                 <tr>
                    <th>Motivo:</th>
                    <td colspan="2" style="font-size: 12px; color: #777;">{{ $nota->motivo }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if(count($nota->cobros) > 0)
        <div class="row" style="margin-top: 20px; page-break-inside: avoid;">
            <div class="col-12">
                <h5 style="color: #014224; border-bottom: 2px solid #014224; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold;">Aplicaciones / Formas de Pago</h5>
                <table class="table table-bordered table-sm" style="width: 100%; border-collapse: collapse;">
                    <thead style="background-color: #f2f2f2;">
                        <tr>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Referencia / Método</th>
                            <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nota->cobros as $cobro)
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd; font-size: 13px;">
                                @if($cobro->cuentaACobrar)
                                    Cuota Nro: {{ $cobro->cuentaACobrar->numero_cuota ?? 'N/A' }} 
                                    (Venc: {{ \Carbon\Carbon::parse($cobro->cuentaACobrar->fecha_vencimiento)->format('d/m/Y') }})
                                @else
                                    {{ $cobro->metodoPago->descripcion ?? 'Pago Directo / Reembolso' }}
                                    @if($cobro->nro_voucher) - Ref: {{ $cobro->nro_voucher }} @endif
                                @endif
                            </td>
                            <td style="padding: 8px; text-align: right; border: 1px solid #ddd; font-size: 13px;">Gs. {{ number_format($cobro->cobro_importe, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="action-buttons">
            <button onclick="window.print();" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir
            </button>
            <button onclick="window.location.href='{{ route('notas_credito.index') }}';" class="btn btn-secondary">
                <i class="fa fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</body>

</html>
