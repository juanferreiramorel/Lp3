<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <title>Recibo De Pago - #{{ $recibo->id_recibo }}</title>
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
            position: relative;
        }

        /* Marca de agua para recibos anulados */
        @if($recibo->estado == 'ANULADO')
        .invoice-container::before {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.15);
            z-index: 1;
            pointer-events: none;
        }
        @endif

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #012442;
            position: relative;
            z-index: 2;
        }

        .company-info {
            flex: 1;
        }

        .company-info h1 {
            color: #012442;
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

        /* Client Info & Receipt Details */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
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
            border-bottom: 2px solid #012442;
            padding-bottom: 5px;
            display: inline-block;
        }

        .client-info h4 {
            color: #012442;
            font-size: 18px;
            margin: 10px 0;
            font-weight: bold;
        }

        .client-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        .receipt-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            min-width: 280px;
        }

        .receipt-details table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-details th {
            text-align: left;
            padding: 8px 10px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
        }

        .receipt-details td {
            text-align: right;
            padding: 8px 10px;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
        }

        .receipt-details tr:last-child th,
        .receipt-details tr:last-child td {
            border-bottom: none;
            font-weight: bold;
            color: #012442;
            font-size: 16px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .items-table thead {
            background: #012442;
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

        .items-table thead th:nth-child(2),
        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4) {
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

        .items-table tbody td:nth-child(2),
        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4) {
            text-align: right;
            font-weight: 500;
        }

        /* Total Section */
        .total-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 2px solid #012442;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
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
            color: #012442;
            padding-top: 15px;
        }

        /* Payment Info */
        .payment-info {
            background: #f0f8ff;
            padding: 15px 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .payment-info h4 {
            color: #007bff;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .payment-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            position: relative;
            z-index: 2;
        }

        .action-buttons .btn {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .invoice-container {
                padding: 15px;
                min-height: auto;
            }

            /* Header responsive */
            header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .company-info {
                margin-bottom: 15px;
            }

            .company-info h1 {
                font-size: 22px;
            }

            .company-info p {
                font-size: 13px;
            }

            .company-logo {
                margin-left: 0;
                margin-top: 10px;
            }

            /* Info section responsive */
            .info-section {
                flex-direction: column;
            }

            .client-info {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .receipt-details {
                min-width: 100%;
                width: 100%;
            }

            /* Tables responsive */
            .items-table {
                font-size: 12px;
            }

            .items-table thead th {
                padding: 8px 5px;
                font-size: 11px;
            }

            .items-table tbody td {
                padding: 8px 5px;
                font-size: 12px;
            }

            /* Payment info table responsive */
            .payment-info table {
                font-size: 12px;
            }

            .payment-info th,
            .payment-info td {
                padding: 6px !important;
                font-size: 11px !important;
            }

            /* Make payment table scrollable on very small screens */
            @media screen and (max-width: 480px) {
                .payment-info {
                    overflow-x: auto;
                }

                .payment-info table {
                    min-width: 480px;
                }
            }

            /* Total section responsive */
            .total-section {
                padding: 15px;
            }

            .total-section th,
            .total-section td {
                font-size: 12px;
            }

            .total-section tr:last-child th,
            .total-section tr:last-child td {
                font-size: 16px;
            }

            /* Action buttons responsive */
            .action-buttons .btn {
                display: block;
                width: 100%;
                margin: 5px 0;
                padding: 10px 20px;
                font-size: 14px;
            }
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

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <header>
            <div class="company-info">
                <h1>RECIBO DE PAGO N° {{ str_pad($recibo->id_recibo, 8, '0', STR_PAD_LEFT) }}</h1>
                <h4><strong>{{ $recibo->empresa_nombre ?? $recibo->empresa_razon_social ?? 'Empresa' }}</strong></h4>
                @if($recibo->empresa_ruc)
                <p><strong>RUC:</strong> {{ $recibo->empresa_ruc }}</p>
                @endif
                @if($recibo->empresa_direccion)
                <p>{{ $recibo->empresa_direccion }}</p>
                @endif
                @if($recibo->empresa_telefono)
                <p>Tel: {{ $recibo->empresa_telefono }}</p>
                @endif
                @if($recibo->empresa_email)
                <p>Email: {{ $recibo->empresa_email }}</p>
                @endif
            </div>
            <div class="company-logo">
                @if($recibo->empresa_logo)
                <img alt="Logo {{ $recibo->empresa_nombre }}" src="{{ asset('storage/' . $recibo->empresa_logo) }}">
                @endif
            </div>
        </header>

        <div class="info-section">
            <div class="client-info">
                <h3>Cliente</h3>
                <h4>{{ $recibo->cliente }}</h4>
                <p><strong>CI/RUC:</strong> {{ $recibo->clie_ci }}</p>
                @if($recibo->clie_telefono)
                    <p><strong>Teléfono:</strong> {{ $recibo->clie_telefono }}</p>
                @endif
            </div>

            <div class="receipt-details">
                <table>
                    <tr>
                        <th>Fecha</th>
                        <td>{{ \Carbon\Carbon::parse($recibo->fecha_recibo)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>
                            <span class="badge badge-{{ $recibo->estado == 'ACTIVO' ? 'success' : 'danger' }}">
                                {{ $recibo->estado }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Monto Total</th>
                        <td>Gs. {{ number_format($recibo->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if (count($detalle_recibo))
                    @foreach ($detalle_recibo as $detalle)
                        <tr>
                            <td>{{ $detalle->concepto }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>Gs. {{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                            <td>Gs. {{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="payment-info">
            <h4>Formas de Pago</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead style="background: #007bff; color: white;">
                    <tr>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Método de Pago</th>
                        <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Importe</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cobros as $cobro)
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;">{{ $cobro->metodo_pago }}</td>
                            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">Gs. {{ number_format($cobro->importe, 0, ',', '.') }}</td>
                            <td style="padding: 8px; border: 1px solid #ddd;">{{ $cobro->observacion ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot style="background: #f0f8ff; font-weight: bold;">
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">TOTAL PAGADO:</td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #007bff;">Gs. {{ number_format(collect($cobros)->sum('importe'), 0, ',', '.') }}</td>
                        <td colspan="2" style="padding: 8px; border: 1px solid #ddd;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="total-section">
            <table>
                <tr>
                    <th>Total en letras:</th>
                    <td colspan="2">{{ ucfirst($total_letras) }} guaraníes</td>
                </tr>
                <tr>
                    <th>TOTAL:</th>
                    <td>Gs. {{ number_format($recibo->total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #777; font-size: 13px; position: relative; z-index: 2;">
            <p>Fecha de emisión: {{ \Carbon\Carbon::parse($recibo->created_at)->format('d/m/Y H:i') }}</p>
        </div>

        <div class="action-buttons">
            <button onclick="window.print();" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir
            </button>
            <button onclick="window.location.href='{{ route('recibossimples.index') }}';" class="btn btn-secondary">
                <i class="fa fa-times"></i> Cerrar
            </button>
        </div>
    </div>

    <script>
        // Auto-print option (uncomment if needed)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
