<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Arqueo de Caja #{{ $cierre_caja->id_apertura }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.4; }
        
        .header-table { width: 100%; border-bottom: 2px solid #555; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #2c3e50; }
        .doc-title { font-size: 24px; font-weight: bold; text-transform: uppercase; text-align: right; color: #2c3e50; }
        .doc-subtitle { font-size: 14px; text-align: right; color: #7f8c8d; }

        .info-box { width: 100%; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 120px; }
        .status-badge { 
            padding: 4px 8px; border-radius: 4px; font-weight: bold; display: inline-block;
            background-color: {{ $cierre_caja->estado == 'ABIERTA' ? '#28a745' : '#6c757d' }};
            color: white; 
        }

        .section-title { font-size: 14px; font-weight: bold; border-bottom: 1px solid #ddd; margin-bottom: 10px; padding-bottom: 5px; color: #2c3e50; text-transform: uppercase; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background-color: #f8f9fa; color: #333; font-weight: bold; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; }
        .data-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .data-table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .total-row td { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #ddd; font-size: 13px; }

        .summary-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 30px; page-break-inside: avoid; }
        .summary-section { page-break-inside: avoid; page-break-before: auto; }
        .summary-table { width: 100%; }
        .summary-table td { padding: 5px; }
        .summary-value { text-align: right; font-weight: bold; font-size: 13px; }

        .signatures { margin-top: 80px; width: 100%; page-break-inside: avoid; text-align: center; }
        .signature-line { border-top: 1px solid #333; width: 40%; display: inline-block; margin: 0 2.5%; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="50%" valign="top">
                <div class="company-name">{{ config('app.name', 'EMPRESA') }}</div>
                <div>RUC: 80023456-1</div>
                <div>Dirección: Av. Principal 123</div>
                <div>Teléfono: (0981) 123-456</div>
            </td>
            <td width="50%" valign="top">
                <div class="doc-title">Arqueo de Caja</div>
                <div class="doc-subtitle">Nro: {{ str_pad($cierre_caja->id_apertura, 6, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
    </table>

    <!-- Info General -->
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="label">Fecha Apertura:</td>
                <td>{{ \Carbon\Carbon::parse($cierre_caja->fecha_apertura)->format('d/m/Y') }}</td>
                <td class="label">Usuario:</td>
                <td>{{ $cierre_caja->usuario }}</td>
            </tr>
            <tr>
                <td class="label">Sucursal:</td>
                <td>{{ $cierre_caja->sucursal }}</td>
                <td class="label">Caja:</td>
                <td>{{ $cierre_caja->caja }}</td>
            </tr>
            <tr>
                <td class="label">Estado:</td>
                <td><span class="status-badge">{{ $cierre_caja->estado }}</span></td>
                <td class="label">Generado:</td>
                <td>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <!-- Detalle de Cobros por Forma de Pago -->
    <div class="section-title">Detalle de Cobros por Forma de Pago</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Forma de Pago</th>
                <th class="text-right">Total Cobrado (GS)</th>
            </tr>
        </thead>
        <tbody>
            @php $total_cobrado = 0; @endphp
            @foreach ($totales_forma_pago as $pago)
                @php $total_cobrado += $pago->total_cobro; @endphp
                <tr>
                    <td>{{ $pago->forma_pago }}</td>
                    <td class="text-right">{{ number_format($pago->total_cobro, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL RECAUDADO</td>
                <td class="text-right">{{ number_format($total_cobrado, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Listado de Facturas -->
    @if(isset($facturas) && count($facturas) > 0)
    <div class="section-title">Detalle de Facturas Emitidas ({{ count($facturas) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nro. Factura</th>
                <th>CI/RUC</th>
                <th>Cliente</th>
                <th class="text-center">Fecha</th>
                <th class="text-right">Total (GS)</th>
            </tr>
        </thead>
        <tbody>
            @php $total_facturas = 0; @endphp
            @foreach ($facturas as $factura)
                @php $total_facturas += $factura->total; @endphp
                <tr>
                    <td>{{ $factura->factura_nro }}</td>
                    <td>{{ $factura->clie_ci }}</td>
                    <td>{{ $factura->cliente }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($factura->fecha_venta)->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($factura->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL FACTURAS</td>
                <td class="text-right">{{ number_format($total_facturas, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="section-title">Detalle de Facturas Emitidas</div>
    <p style="color: #7f8c8d; font-style: italic;">No se emitieron facturas en este arqueo.</p>
    @endif

    <!-- Listado de Notas de Débito -->
    @if(isset($notas_debito) && count($notas_debito) > 0)
    <div class="section-title">Detalle de Notas de Débito Emitidas ({{ count($notas_debito) }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nro. Nota</th>
                <th>CI/RUC</th>
                <th>Cliente</th>
                <th class="text-center">Fecha</th>
                <th class="text-right">Total (GS)</th>
            </tr>
        </thead>
        <tbody>
            @php $total_notas = 0; @endphp
            @foreach ($notas_debito as $nota)
                @php $total_notas += $nota->total; @endphp
                <tr>
                    <td>{{ $nota->nro_nota }}</td>
                    <td>{{ $nota->clie_ci }}</td>
                    <td>{{ $nota->cliente }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($nota->fecha_emision)->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($nota->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL NOTAS DE DÉBITO</td>
                <td class="text-right">{{ number_format($total_notas, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Resumen General -->
    <div class="summary-section">
        <div class="section-title">Resumen de Caja</div>
        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td>Monto Apertura (Fondo Fijo):</td>
                    <td class="summary-value">{{ number_format($cierre_caja->monto_apertura, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>(+) Total Ventas (Sistema):</td>
                    <td class="summary-value">{{ number_format($total_cobrado, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid #ccc; padding-top: 5px;"><strong>(=) Total Esperado en Caja:</strong></td>
                    <td class="summary-value" style="border-top: 1px solid #ccc; padding-top: 5px;">
                        {{ number_format($cierre_caja->monto_apertura + $total_cobrado, 0, ',', '.') }}
                    </td>
                </tr>
                @if($cierre_caja->estado == 'CERRADA')
                <tr>
                    <td style="padding-top: 10px;"><strong>Monto Cierre (Declarado):</strong></td>
                    <td class="summary-value" style="padding-top: 10px; color: #007bff;">
                        {{ number_format($cierre_caja->monto_cierre, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td><strong>Diferencia:</strong></td>
                    @php $diferencia = $cierre_caja->monto_cierre - ($cierre_caja->monto_apertura + $total_cobrado); @endphp
                    <td class="summary-value" style="color: {{ $diferencia < 0 ? 'red' : ($diferencia > 0 ? 'green' : 'black') }};">
                        {{ number_format($diferencia, 0, ',', '.') }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Firmas -->
    <div class="signatures">
        <div class="signature-line">
            {{ $cierre_caja->usuario }}<br>
            <strong>Cajero/a</strong>
        </div>
        <div class="signature-line">
            <br>
            <strong>Verificado por</strong>
        </div>
    </div>

</body>
</html>
