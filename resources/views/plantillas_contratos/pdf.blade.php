<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato - {{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18pt;
            margin: 0;
            padding: 0;
        }

        .info-contrato {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .info-contrato p {
            margin: 5px 0;
            font-size: 10pt;
        }

        .contenido {
            white-space: pre-wrap;
            text-align: justify;
            margin-top: 20px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #333;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }

        @page {
            margin: 2cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $contrato->nombre_plantilla }}</h1>
    </div>

    <div class="info-contrato">
        <p><strong>Cliente:</strong> {{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</p>
        <p><strong>CI:</strong> {{ $contrato->clie_ci }}</p>
        <p><strong>Fecha de Generación:</strong> {{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</p>
        @if ($contrato->observaciones)
            <p><strong>Observaciones:</strong> {{ $contrato->observaciones }}</p>
        @endif
    </div>

    <div class="contenido">{{ $contrato->contenido_generado }}</div>

    <div class="footer">
        <p>Documento generado el {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
    </div>
</body>
</html>
