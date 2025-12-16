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
            margin-top: 20px;
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000000;
        }

        .contenido h1 {
            font-size: 28pt;
            font-weight: bold;
            margin-top: 16pt;
            margin-bottom: 8pt;
            color: #1a1a1a;
            line-height: 1.3;
        }

        .contenido h2 {
            font-size: 22pt;
            font-weight: bold;
            margin-top: 14pt;
            margin-bottom: 7pt;
            color: #1a1a1a;
            line-height: 1.3;
        }

        .contenido h3 {
            font-size: 18pt;
            font-weight: bold;
            margin-top: 12pt;
            margin-bottom: 6pt;
            color: #1a1a1a;
            line-height: 1.3;
        }

        .contenido h4 {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10pt;
            margin-bottom: 5pt;
            color: #1a1a1a;
        }

        .contenido h5 {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 8pt;
            margin-bottom: 4pt;
            color: #1a1a1a;
        }

        .contenido h6 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 6pt;
            margin-bottom: 3pt;
            color: #1a1a1a;
        }

        .contenido p {
            margin-top: 0;
            margin-bottom: 10pt;
            text-align: justify;
        }

        .contenido ul,
        .contenido ol {
            padding-left: 1.5em;
            margin-bottom: 10pt;
        }

        .contenido li {
            margin-bottom: 5pt;
        }

        .contenido blockquote {
            border-left: 4px solid #3b82f6;
            padding-left: 16px;
            margin: 16px 0;
            font-style: italic;
            color: #4b5563;
        }

        .contenido a {
            color: #2563eb;
            text-decoration: underline;
        }

        .contenido table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .contenido table,
        .contenido th,
        .contenido td {
            border: 1px solid #000;
        }

        .contenido th,
        .contenido td {
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        
        .contenido th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .contenido img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        .contenido figure {
            margin: 15px 0;
            text-align: center;
            page-break-inside: avoid;
        }
        
        .contenido figcaption {
            font-size: 10pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        /* Estilos de imagen de CKEditor */
        .contenido .image-style-side {
            float: right;
            margin-left: 1.5em;
            max-width: 50%;
        }
        
        .contenido .image-style-align-left {
            float: left;
            margin-right: 1.5em;
        }
        
        .contenido .image-style-align-center {
            margin: 0 auto;
            display: block;
        }
        
        .contenido .image-style-align-right {
            float: right;
            margin-left: 1.5em;
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
        
        /* Reglas de impresión */
        h1, h2, h3, h4, h5, h6 {
            page-break-after: avoid;
        }
        
        table, figure, img {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <div class="contenido">{!! $contrato->contenido_generado !!}</div>

</body>
</html>
