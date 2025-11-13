<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <title>Factura - {{ $ventas->factura_nro }}</title>
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
            border-bottom: 3px solid #e40101;
        }

        .company-info {
            flex: 1;
        }

        .company-info h1 {
            color: #e40101;
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
            border-bottom: 2px solid #e40101;
            padding-bottom: 5px;
            display: inline-block;
        }

        .client-info h4 {
            color: #e40101;
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
            color: #e40101;
            font-size: 16px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #e40101;
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

        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4),
        .items-table thead th:nth-child(5) {
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

        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4),
        .items-table tbody td:nth-child(5) {
            text-align: right;
            font-weight: 500;
        }

        /* Total Section */
        .total-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 2px solid #e40101;
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
            color: #e40101;
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
                <h1>Factura</h1>
                <p><strong>{{ $ventas->empresa_nombre ?? $ventas->empresa_razon_social ?? 'Empresa' }}</strong></p>
                @if($ventas->empresa_ruc)
                <p><strong>RUC:</strong> {{ $ventas->empresa_ruc }}</p>
                @endif
                @if($ventas->empresa_direccion)
                <p>{{ $ventas->empresa_direccion }}</p>
                @endif
                @if($ventas->empresa_telefono)
                <p>Tel: {{ $ventas->empresa_telefono }}</p>
                @endif
                @if($ventas->empresa_email)
                <p>Email: {{ $ventas->empresa_email }}</p>
                @endif
            </div>
            <div class="company-logo">
                @if($ventas->empresa_logo)
                <img alt="Logo {{ $ventas->empresa_nombre }}" src="{{ $ventas->empresa_logo }}">
                @endif
            </div>
        </header>

        <div class="info-section">
            <div class="client-info">
                <h3>Cliente</h3>
                <h4>{{ $ventas->cliente }}</h4>
                <p><strong>Dirección:</strong> {{ $ventas->clie_direccion }}</p>
                <p><strong>Teléfono:</strong> {{ $ventas->clie_telefono }}</p>
            </div>

            <div class="invoice-details">
                <table>
                    <tr>
                        <th>Factura Nº</th>
                        <td>{{ $ventas->factura_nro }}</td>
                    </tr>
                    <tr>
                        <th>Condición</th>
                        <td>{{ $ventas->condicion_venta }}</td>
                    </tr>
                    <tr>
                        <th>Fecha</th>
                        <td>{{ \Carbon\Carbon::parse($ventas->fecha_venta)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Monto Total</th>
                        <td>Gs. {{ number_format($ventas->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if (count($detalle_venta))
                    @foreach ($detalle_venta as $value)
                        <tr>
                            <td>{{ $value->id_producto }}</td>
                            <td>{{ $value->descripcion }}</td>
                            <td>{{ $value->cantidad }}</td>
                            <td>Gs. {{ number_format($value->precio, 0, ',', '.') }}</td>
                            <td>Gs. {{ number_format($value->precio * $value->cantidad, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="total-section">
            <table>
                <tr>
                    <th>Total en letras:</th>
                    <td colspan="2">{{ $numero_a_letras }}</td>
                </tr>
                <tr>
                    <th>TOTAL:</th>
                    <td>Gs. {{ number_format($ventas->total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="action-buttons">
            <button onclick="window.print();" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir
            </button>
            <button onclick="window.location.href='{{ route('ventas.index') }}';" class="btn btn-secondary">
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
