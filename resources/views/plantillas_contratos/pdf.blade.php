{{-- The contract as a PDF. Rendered by Chromium (App\Services\ContratoPdf),
     so it is drawn by the same engine family as the editor and with the same
     two stylesheets: CKEditor's content styles, then the contract sheet.

     The stylesheets are inlined rather than linked: the renderer is an
     isolated container that is not allowed to fetch from the network. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato - {{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</title>
    <style>
        {!! file_get_contents(public_path('css/ckeditor5-content.css')) !!}
        {!! file_get_contents(public_path('css/documento-contrato.css')) !!}

        /* The page margins are the editor's padding: 2cm all round, so the
           text column is 17cm in both. */
        @page {
            size: A4;
            margin: 2cm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="ck-content documento">{!! $contrato->contenido_generado !!}</div>
</body>
</html>
