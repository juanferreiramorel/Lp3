<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Looks a document up in the people database behind the private People API.
 *
 * The service holds two sets: personas.db, which maps a cédula to a name and
 * date of birth, and ruc.db, which maps a RUC to a trade name. It is an
 * identification lookup, not a tax roll.
 *
 * Two shapes come back from the service and both are normalised here, so the
 * rest of the app only ever sees the fields a Cliente actually has:
 *
 *   GET /personas?cedula=1234567  ->  cedula, apellidos, nombres, fecNac
 *   GET /?ruc=80012345-1          ->  ruc, razonsocial, tipo, dv, estado
 *
 * The JWT lives in config and is sent server side only. Nothing here is ever
 * exposed to the browser beyond the normalised result.
 */
class PeopleLookup
{
    /**
     * Resolve a document into whatever the people database knows about it.
     *
     * @return array{found: bool, reason?: string, data?: array}
     */
    public function find(string $documento): array
    {
        $documento = $this->normalizeDocument($documento);

        if ($documento === '') {
            return ['found' => false, 'reason' => 'empty'];
        }

        if (!$this->isConfigured()) {
            return ['found' => false, 'reason' => 'not_configured'];
        }

        // A RUC carries a check digit after a dash; a bare number is a cédula.
        // Try the matching endpoint first and fall back to the other one,
        // because people do type a RUC without its DV and a cédula with one.
        $esRuc = str_contains($documento, '-');

        $resultado = $esRuc
            ? $this->consultarRuc($documento)
            : $this->consultarCedula($documento);

        if ($resultado === null) {
            $resultado = $esRuc
                ? $this->consultarCedula(explode('-', $documento)[0])
                : $this->consultarRuc($documento);
        }

        if ($resultado !== null) {
            return ['found' => true, 'data' => $resultado];
        }

        return ['found' => false, 'reason' => $this->rejected ? 'rejected' : 'not_found'];
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.people.jwt')) !== ''
            && trim((string) config('services.people.base_url')) !== '';
    }

    private function consultarCedula(string $cedula): ?array
    {
        $payload = $this->get('/personas', ['cedula' => $cedula]);

        if (!is_array($payload) || empty($payload['cedula'])) {
            return null;
        }

        return [
            'documento' => (string) $payload['cedula'],
            'nombre' => $this->limpiar($payload['nombres'] ?? ''),
            'apellido' => $this->limpiar($payload['apellidos'] ?? ''),
            'fecha_nacimiento' => $this->fechaIso($payload['fecNac'] ?? null),
            'tipo' => 'persona',
        ];
    }

    private function consultarRuc(string $ruc): ?array
    {
        // The service indexes a RUC without its check digit and returns the dv
        // as its own field, so "80028545-0" has to be asked for as "80028545".
        $payload = $this->get('/', ['ruc' => explode('-', $ruc)[0]]);

        if (!is_array($payload)) {
            return null;
        }

        // The RUC endpoint may answer with a person record when the taxpayer
        // is an individual (tipo "F"), in which case the name is already split.
        if (!empty($payload['nombres']) || !empty($payload['apellidos'])) {
            return [
                'documento' => (string) ($payload['ruc'] ?? $payload['cedula'] ?? $ruc),
                'nombre' => $this->limpiar($payload['nombres'] ?? ''),
                'apellido' => $this->limpiar($payload['apellidos'] ?? ''),
                'fecha_nacimiento' => $this->fechaIso($payload['fecNac'] ?? null),
                'estado' => $payload['estado'] ?? null,
                'tipo' => 'persona',
            ];
        }

        if (empty($payload['razonsocial'])) {
            return null;
        }

        $documento = (string) ($payload['ruc'] ?? $ruc);

        // A check digit of "0" is falsy in PHP, so !empty() would silently drop
        // the dv of every RUC that ends in zero.
        $dv = trim((string) ($payload['dv'] ?? ''));

        if ($dv !== '' && !str_contains($documento, '-')) {
            $documento .= '-' . $dv;
        }

        return [
            'documento' => $documento,
            // A company has no surname. The whole trade name is the name.
            'nombre' => $this->limpiar($payload['razonsocial']),
            'apellido' => '',
            'fecha_nacimiento' => null,
            'estado' => $payload['estado'] ?? null,
            'tipo' => ($payload['tipo'] ?? '') === 'F' ? 'persona' : 'empresa',
        ];
    }

    /** True once the service has answered 401/403: a bad token, not a bad document. */
    private bool $rejected = false;

    private function get(string $path, array $query): ?array
    {
        try {
            $response = Http::withToken(config('services.people.jwt'))
                ->acceptJson()
                ->timeout(config('services.people.timeout', 8))
                ->get(rtrim(config('services.people.base_url'), '/') . $path, $query);
        } catch (\Throwable $e) {
            // The service being unreachable must never block loading a client
            // by hand, so this is logged and reported as "not found".
            Log::warning('People lookup failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        // A rejected token is a configuration fault, not an answer about the
        // document. Reporting it as "we could not find it" would hide a broken
        // integration behind an empty result, and nobody would go looking.
        if (in_array($response->status(), [401, 403], true)) {
            $this->rejected = true;

            Log::warning('People lookup rejected the token', [
                'path' => $path,
                'status' => $response->status(),
            ]);

            return null;
        }

        if ($response->status() === 404 || !$response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    private function normalizeDocument(string $documento): string
    {
        // Keep digits and the DV separator only: people paste "80.012.345-1".
        return trim(preg_replace('/[^0-9\-]/', '', $documento) ?? '');
    }

    private function limpiar($valor): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $valor) ?? '');
    }

    /**
     * The service returns dd/mm/yyyy; an <input type="date"> needs yyyy-mm-dd.
     */
    private function fechaIso($valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '' || strtolower($valor) === 'none') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $valor);

            if ($fecha !== false) {
                return $fecha->format('Y-m-d');
            }
        }

        return null;
    }
}
