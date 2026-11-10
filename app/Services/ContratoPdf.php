<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a generated contract into a PDF that looks like the editor.
 *
 * The PDF is printed by Chromium running in Gotenberg, an isolated container
 * on the stack's internal network. DomPDF stays only as a fallback, so a
 * contract can still be downloaded if the renderer is down: it is logged,
 * because that fallback does NOT match the editor.
 */
class ContratoPdf
{
    /**
     * @return array{0: string, 1: string} The PDF bytes and the engine that produced them.
     */
    public function generar(object $contrato): array
    {
        $html = view('plantillas_contratos.pdf', ['contrato' => $contrato])->render();

        try {
            return [$this->conChromium($html), 'chromium'];
        } catch (Throwable $e) {
            Log::error('Contract PDF fell back to DomPDF: it will not match the editor', [
                'contrato' => $contrato->id_contrato_generado ?? null,
                'error'    => $e->getMessage(),
            ]);

            return [Pdf::loadHTML($html)->setPaper('a4')->output(), 'dompdf'];
        }
    }

    private function conChromium(string $html): string
    {
        [$html, $imagenes] = $this->adjuntarImagenes($html);

        $peticion = Http::timeout((int) config('services.gotenberg.timeout', 30))
            ->asMultipart()
            ->attach('files', $html, 'index.html');

        foreach ($imagenes as $nombre => $ruta) {
            $peticion = $peticion->attach('files', file_get_contents($ruta), $nombre);
        }

        $respuesta = $peticion->post(
            rtrim((string) config('services.gotenberg.url'), '/') . '/forms/chromium/convert/html',
            [
                // Page size and margins come from the @page rule in the
                // template, the same 2cm the editor uses as padding.
                'preferCssPageSize' => 'true',
                'marginTop'         => '0',
                'marginBottom'      => '0',
                'marginLeft'        => '0',
                'marginRight'       => '0',
                'printBackground'   => 'true',
            ]
        );

        if (!$respuesta->successful()) {
            throw new \RuntimeException('Gotenberg answered ' . $respuesta->status() . ': ' . mb_substr($respuesta->body(), 0, 300));
        }

        return $respuesta->body();
    }

    /**
     * Images uploaded through the editor are saved as absolute URLs to this
     * application. The renderer is not allowed on the network, so each one is
     * sent along with the page and its src rewritten to the attached name.
     * Only files that resolve inside public/ are attached.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function adjuntarImagenes(string $html): array
    {
        $publico = realpath(public_path());
        $imagenes = [];

        $html = preg_replace_callback('/(<img\b[^>]*\bsrc=)(["\'])(.*?)\2/i', function ($m) use ($publico, &$imagenes) {
            $src = html_entity_decode($m[3]);

            if (str_starts_with($src, 'data:')) {
                return $m[0];
            }

            $ruta = parse_url($src, PHP_URL_PATH);
            $archivo = $ruta ? realpath(public_path(ltrim(rawurldecode($ruta), '/'))) : false;

            if (!$archivo || !$publico || !str_starts_with($archivo, $publico . DIRECTORY_SEPARATOR) || !is_file($archivo)) {
                return $m[0];
            }

            $nombre = 'img' . count($imagenes) . '.' . strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $imagenes[$nombre] = $archivo;

            return $m[1] . $m[2] . $nombre . $m[2];
        }, $html);

        return [$html, $imagenes];
    }
}
