<html>
<head>
    <title>Libro Venta</title>
    <style>
        @page {
            margin: 0cm 0cm;
            margin-bottom: 2cm;
        }

        body {
            margin-top: 1cm;
            margin-left: 1cm;
            margin-right: 1cm;
            margin-bottom: 1cm;
        }

        .tabla {
            font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            border: 0px solid #ddd;
        }

        .tabla td,
        .tabla th {
            border: 0px solid #ddd;
            padding: 2px;
        }

        .tabla tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .tabla tr:hover {
            background-color: #ddd;
        }

        .tabla th {
            padding-top: 3px;
            padding-bottom: 3px;
            background-color: #f6efef;
            color: black;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        th {
            font-size: 10px;
        }

        td {
            font-size: 10px;
            padding-left: 5px;
            padding-bottom: 2px;
        }

        .text-right {
            text-align: right;
        }
        
        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="box box-primary">
        <p style="text-align: center;">
            <b>LIBRO DE VENTAS (IVA)</b>
        </p>
        <br>
        <div class="box-body">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nro Factura</th>
                        <th>RUC</th>
                        <th>Cliente</th>
                        <th class="text-right">Exenta</th>
                        <th class="text-right">Gravada 5%</th>
                        <th class="text-right">Gravada 10%</th>
                        <th class="text-right">IVA 5%</th>
                        <th class="text-right">IVA 10%</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventasAll as $venta)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                            <td>{{ $venta->factura_nro }}</td>
                            <td>{{ $venta->ruc }}</td>
                            <td>{{ substr($venta->cliente, 0, 30) }}</td>
                            <td class="text-right">{{ number_format($venta->total_exento, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($venta->total_gravado_5, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($venta->total_gravado_10, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($venta->total_iva_5, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($venta->total_iva_10, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($venta->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">No se encontraron registros</td>
                        </tr>
                    @endforelse
                    
                    <tr>
                        <td colspan="10"><hr></td>
                    </tr>
                    
                    <tr style="font-weight: bold; background-color: #e6e6e6;">
                        <td colspan="4" class="text-right">TOTALES:</td>
                        <td class="text-right">{{ number_format($totalesGenerales->sum_exento, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalesGenerales->sum_gravado_5, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalesGenerales->sum_gravado_10, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalesGenerales->sum_iva_5, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalesGenerales->sum_iva_10, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalesGenerales->total_general, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
