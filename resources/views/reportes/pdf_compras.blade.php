<html>

<head>
    <title>Reporte de compras</title>
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
            /*text-align: left;*/
            background-color: #f6efef;
            color: black;
        }

        tr.was-replaced td {
            text-decoration: line-through;
        }

        th {
            font-size: 12px;
            font-weight: bold;
            padding-left: 5px;
            padding-bottom: 2px;
        }

        td {
            font-size: 12px;
            padding-left: 5px;
            padding-bottom: 2px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="box box-primary">
        <p style="text-align: center;">
            <b>Reporte de compras<b>
        </p>
        <br>
        <div class="box-body">
            <table class="tabla">
                <thead>
                    @foreach ($comprasAll as $compra)
                        <tr>
                            <td colspan="2"><b>Fecha:</b>
                                {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</td>
                            <td><b>Proveedor:</b> {{ $compra->proveedor }}</td>
                            <td><b>Condición Compra:</b> {{ $compra->condicion_compra }}</td>
                        </tr>

                        @if (!empty($compra->intervalo))
                            <tr>
                                <td colspan="2"><b>Intervalo Vto:</b> {{ $compra->intervalo }}</td>
                                <td><b>Cantidad Cuota:</b> {{ $compra->cantidad_cuotas }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="2"><b>Sucursal:</b> {{ $compra->sucursal }}</td>
                            <td><b>Factura Nro:</b> {{ $compra->factura }}</td>
                            <td><b>Estado:</b> {{ $compra->estado }}</td>
                            <td><b>Usuario:</b> {{ $compra->usuario }}</td>
                        </tr>
                        <tr>
                            <td><b>Total:</b> {{ number_format($compra->total, 0, ',', '.') }}</td>
                            <td><b>Total Exento:</b> {{ number_format($compra->total_exento, 0, ',', '.') }}</td>
                            <td><b>Total Gravado 5%:</b> {{ number_format($compra->total_gravado_5, 0, ',', '.') }}</td>
                            <td><b>Total Gravado 10%:</b> {{ number_format($compra->total_gravado_10, 0, ',', '.') }}</td>
                            <td><b>Total IVA:</b> {{ number_format($compra->total_iva, 0, ',', '.') }}</td>
                        </tr>

                        <!-- detalle de compras -->
                        <tr style="border: 1px; color:#000; background: #C5C9D3">
                            <td>Código Producto</td>
                            <td>Descripción</td>
                            <td>Cantidad</td>
                            <td>Precio Unit</td>
                            <td>Subtotal</td>
                        </tr>
                        <!-- usar forelse para mostrar mensaje si no hay detalles -->
                        @forelse ($array_detalles_all[$compra->id_compra] as $key => $det)
                            <tr>
                                <td>{{ $det->id_producto }}</td>
                                <td>{{ $det->producto }}</td>
                                <td>{{ $det->cantidad }}</td>
                                <td>{{ number_format($det->precio_unitario, 0, ',', '.') }}</td>
                                <td>{{ number_format($det->precio_unitario * $det->cantidad, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center"><b>No existe detalle para la compra..</b>
                                </td>
                            </tr>
                        @endforelse
                        <!-- salto de linea entre compras -->
                        <br>
                        <tr><!-- imprimir una linea horizontal entre compras -->
                            <td colspan="5">
                                <hr>
                            </td>
                        </tr>
                    @endforeach
                </thead>
            </table>
        </div>
    </div>
</body>

</html>

