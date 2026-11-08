{{-- Printed listing. dompdf resolves no external stylesheet and supports only
     a subset of CSS, so everything here is inline and deliberately plain. A
     printed sheet is always light, whatever theme the app is showing. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 14mm 10mm 16mm 10mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #18181b;
            margin: 0;
        }

        .encabezado {
            border-bottom: 1.5px solid #18181b;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .encabezado h1 {
            font-size: 13pt;
            margin: 0 0 2px 0;
            letter-spacing: -0.2pt;
        }

        .encabezado .meta { font-size: 7.5pt; color: #52525b; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background-color: #ebebed;
            border-bottom: 1px solid #a1a1aa;
            text-align: left;
            padding: 5px 6px;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        tbody td {
            border-bottom: 0.5px solid #e4e4e7;
            padding: 4px 6px;
            vertical-align: top;
        }

        /* Zebra striping survives a page break better than a hover state. */
        tbody tr:nth-child(even) td { background-color: #fafafa; }

        .vacio { text-align: center; padding: 18px; color: #71717a; }

        /* dompdf renders this on every page once the footer is positioned. */
        .pie {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #71717a;
            border-top: 0.5px solid #e4e4e7;
            padding-top: 3px;
        }

        .pie .pagina:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $titulo }}</h1>
        <div class="meta">
            {{ config('app.name') }} &middot; Generado el {{ $generado }} &middot;
            {{ count($filas) }} {{ count($filas) === 1 ? 'registro' : 'registros' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach ($fila as $celda)
                        <td>{{ $celda }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="vacio" colspan="{{ max(count($headings), 1) }}">
                        No hay registros para los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        {{ config('app.name') }} &middot; {{ $titulo }} &middot; Página <span class="pagina"></span>
    </div>
</body>
</html>
