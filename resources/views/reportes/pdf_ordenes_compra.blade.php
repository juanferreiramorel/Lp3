<html>

<head>
    <title>Reporte de órdenes de compra</title>
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
            <b>Reporte de órdenes de compra<b>
        </p>
        <br>
        <div class="box-body">
            <table class="tabla">
                <thead>
                    @foreach ($ordenesCompraAll as $ordenCompra)
                        <tr>
                            <td colspan="2"><b>Fecha:</b>
                                {{ \Carbon\Carbon::parse($ordenCompra->fecha_orden)->format('d/m/Y') }}</td>
                            <td><b>Proveedor:</b> {{ $ordenCompra->proveedor }}</td>
                            <td><b>Estado:</b> {{ $ordenCompra->estado }}</td>
                        </tr>
                        <tr>
                            <td colspan="2"><b>Sucursal:</b> {{ $ordenCompra->sucursal ?? '-' }}</td>
                            <td><b>Usuario:</b> {{ $ordenCompra->usuario ?? '-' }}</td>
                            <td><b>Nro Orden:</b> {{ $ordenCompra->id_orden_compra }}</td>
                        </tr>
                        <tr>
                            <td><b>Subtotal:</b> {{ number_format($ordenCompra->subtotal, 0, ',', '.') }}</td>
                            <td><b>Total Exento:</b> {{ number_format($ordenCompra->total_exento, 0, ',', '.') }}</td>
                            <td><b>Total Gravado 5%:</b> {{ number_format($ordenCompra->total_gravado_5, 0, ',', '.') }}</td>
                            <td><b>Total Gravado 10%:</b> {{ number_format($ordenCompra->total_gravado_10, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td><b>IVA 5%:</b> {{ number_format($ordenCompra->total_iva_5, 0, ',', '.') }}</td>
                            <td><b>IVA 10%:</b> {{ number_format($ordenCompra->total_iva_10, 0, ',', '.') }}</td>
                            <td><b>Total IVA:</b> {{ number_format($ordenCompra->total_iva, 0, ',', '.') }}</td>
                            <td><b>Total:</b> {{ number_format($ordenCompra->total, 0, ',', '.') }}</td>
                        </tr>
                        @if(!empty($ordenCompra->observaciones))
                        <tr>
                            <td colspan="4"><b>Observaciones:</b> {{ $ordenCompra->observaciones }}</td>
                        </tr>
                        @endif

                        <!-- detalle de órdenes de compra -->
                        <tr style="border: 1px; color:#000; background: #C5C9D3">
                            <td>Código Producto</td>
                            <td>Descripción</td>
                            <td>Cantidad</td>
                            <td>Precio Unit</td>
                            <td>Subtotal</td>
                        </tr>
                        <!-- usar forelse para mostrar mensaje si no hay detalles -->
                        @forelse ($array_detalles_all[$ordenCompra->id_orden_compra] as $key => $det)
                            <tr>
                                <td>{{ $det->id_producto }}</td>
                                <td>{{ $det->producto }}</td>
                                <td>{{ $det->cantidad }}</td>
                                <td>{{ number_format($det->precio_unitario, 0, ',', '.') }}</td>
                                <td>{{ number_format($det->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center"><b>No existe detalle para la orden de compra..</b>
                                </td>
                            </tr>
                        @endforelse
                        <!-- salto de linea entre órdenes -->
                        <br>
                        <tr><!-- imprimir una linea horizontal entre órdenes -->
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



