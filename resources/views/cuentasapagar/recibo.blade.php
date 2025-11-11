<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago #{{ $pago->id_pago }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 10px;
            background-color: #f5f5f5;
        }

        .recibo-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background-color: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        @if($anulado)
        .recibo-container::before {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(255, 0, 0, 0.2);
            font-weight: bold;
            z-index: 1;
            pointer-events: none;
        }
        @endif

        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }

        .comprobante-numero {
            text-align: center;
            background-color: #007bff;
            color: white;
            padding: 8px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .info-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .info-section h3 {
            background-color: #f8f9fa;
            padding: 6px 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
        }

        .info-row {
            display: flex;
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            width: 180px;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        .monto-total {
            background-color: #28a745;
            color: white;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .monto-total .label {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .monto-total .valor {
            font-size: 32px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }

        .firma {
            margin-top: 40px;
            text-align: center;
            page-break-inside: avoid;
        }

        .firma-linea {
            border-top: 2px solid #333;
            width: 250px;
            margin: 0 auto 8px;
        }

        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .btn-print:hover {
            background-color: #0056b3;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }

            .recibo-container {
                box-shadow: none;
                padding: 10mm;
                margin: 0;
                max-width: 100%;
                min-height: auto;
            }

            .print-button {
                display: none;
            }

            .btn-print {
                display: none;
            }

            .info-section {
                page-break-inside: avoid;
            }

            .monto-total {
                page-break-inside: avoid;
            }

            .firma {
                page-break-inside: avoid;
            }
        }

        .estado-anulado {
            background-color: #dc3545;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir Recibo
        </button>
    </div>

    <div class="recibo-container">
        <div class="header">
            <h1>RECIBO DE PAGO</h1>
            <h2>{{ $pago->sucursal ?? 'Sistema de Gestión' }}</h2>
            @if($pago->sucursal_direccion)
                <p style="color: #666; margin-top: 5px;">{{ $pago->sucursal_direccion }}</p>
            @endif
            @if($pago->sucursal_telefono)
                <p style="color: #666;">Tel: {{ $pago->sucursal_telefono }}</p>
            @endif
        </div>

        @if($anulado)
        <div class="estado-anulado">
            *** RECIBO ANULADO ***
        </div>
        @endif

        <div class="comprobante-numero">
            N° DE COMPROBANTE: {{ str_pad($pago->id_pago, 8, '0', STR_PAD_LEFT) }}
        </div>

        <div class="info-section">
            <h3>INFORMACIÓN DEL PROVEEDOR</h3>
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $pago->proveedor_nombre }}</div>
            </div>
            @if($pago->proveedor_telefono)
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $pago->proveedor_telefono }}</div>
            </div>
            @endif
            @if($pago->proveedor_direccion)
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value">{{ $pago->proveedor_direccion }}</div>
            </div>
            @endif
        </div>

        <div class="info-section">
            <h3>DETALLE DEL PAGO</h3>
            <div class="info-row">
                <div class="info-label">Fecha de Pago:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($pago->pago_fecha)->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Método de Pago:</div>
                <div class="info-value">{{ $pago->metodo_pago }}</div>
            </div>
            @if($pago->nro_voucher)
            <div class="info-row">
                <div class="info-label">N° Voucher:</div>
                <div class="info-value">{{ $pago->nro_voucher }}</div>
            </div>
            @endif
        </div>

        <div class="info-section">
            <h3>INFORMACIÓN DE LA CUOTA</h3>
            <div class="info-row">
                <div class="info-label">Factura N°:</div>
                <div class="info-value">{{ $pago->factura }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Compra:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($pago->fecha_compra)->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">N° Cuenta:</div>
                <div class="info-value">{{ $pago->id_cta }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">N° Cuota:</div>
                <div class="info-value">{{ $pago->nro_cuota }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Importe Cuota:</div>
                <div class="info-value">₲ {{ number_format($pago->importe_cuota, 0, ',', '.') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Vencimiento:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($pago->vencimiento)->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="monto-total">
            <div class="label">MONTO PAGADO</div>
            <div class="valor">₲ {{ number_format($pago->pago_importe, 0, ',', '.') }}</div>
        </div>

        <div class="info-section">
            <h3>INFORMACIÓN ADICIONAL</h3>
            <div class="info-row">
                <div class="info-label">Usuario que registró:</div>
                <div class="info-value">{{ $pago->usuario_registro }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de emisión:</div>
                <div class="info-value">{{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>

        <div class="footer">
            <p>Este documento es un comprobante válido de pago</p>
            <p>Conserve este recibo para futuras referencias</p>
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar la página (opcional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
