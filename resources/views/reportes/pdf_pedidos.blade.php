<html>

<head>
    <title>Reporte de pedidos</title>
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
            <b>Reporte de pedidos<b>
        </p>
        <br>
        <div class="box-body">
            <table class="tabla">
                <thead>
                    @foreach ($pedidosAll as $pedido)
                        <tr>
                            <td colspan="2"><b>Fecha:</b>
                                {{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y') }}</td>
                            <td><b>Cliente:</b> {{ $pedido->cliente }}</td>
                            <td><b>CI:</b> {{ $pedido->clie_ci ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td colspan="2"><b>Sucursal:</b> {{ $pedido->sucursal ?? '-' }}</td>
                            <td><b>Usuario:</b> {{ $pedido->usuario ?? '-' }}</td>
                            <td><b>Nro Pedido:</b> {{ $pedido->id_pedido }}</td>
                        </tr>
                        <tr>
                            <td><b>Estado:</b> {{ $pedido->estado }}</td>
                            <td><b>Total Pedido:</b> {{ number_format($pedido->total_pedido, 0, ',', '.') }}</td>
                            @if(isset($pedido->total_exento))
                            <td><b>Total Exento:</b> {{ number_format($pedido->total_exento, 0, ',', '.') }}</td>
                            <td><b>Total Gravado 5%:</b> {{ number_format($pedido->total_gravado_5 ?? 0, 0, ',', '.') }}</td>
                            @endif
                        </tr>
                        @if(isset($pedido->total_gravado_10))
                        <tr>
                            <td><b>Total Gravado 10%:</b> {{ number_format($pedido->total_gravado_10, 0, ',', '.') }}</td>
                            <td><b>IVA 5%:</b> {{ number_format($pedido->total_iva_5 ?? 0, 0, ',', '.') }}</td>
                            <td><b>IVA 10%:</b> {{ number_format($pedido->total_iva_10 ?? 0, 0, ',', '.') }}</td>
                            <td><b>Total IVA:</b> {{ number_format($pedido->total_iva ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @endif

                        <!-- detalle de pedidos -->
                        <tr style="border: 1px; color:#000; background: #C5C9D3">
                            <td>Código Producto</td>
                            <td>Descripción</td>
                            <td>Cantidad</td>
                            <td>Precio Unit</td>
                            <td>Subtotal</td>
                        </tr>
                        <!-- usar forelse para mostrar mensaje si no hay detalles -->
                        @forelse ($array_detalles_all[$pedido->id_pedido] as $key => $det)
                            <tr>
                                <td>{{ $det->id_producto }}</td>
                                <td>{{ $det->producto }}</td>
                                <td>{{ $det->cantidad }}</td>
                                <td>{{ number_format($det->precio_unitario, 0, ',', '.') }}</td>
                                <td>{{ number_format($det->precio_unitario * $det->cantidad, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center"><b>No existe detalle para el pedido..</b>
                                </td>
                            </tr>
                        @endforelse
                        <!-- salto de linea entre pedidos -->
                        <br>
                        <tr><!-- imprimir una linea horizontal entre pedidos -->
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

