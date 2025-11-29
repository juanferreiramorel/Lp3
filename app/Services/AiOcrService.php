<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiOcrService
{
    protected string $apiBase;
    protected string $jwtToken;

    public function __construct()
    {
        $this->apiBase = config('services.ai_ocr.base_url');
        $this->jwtToken = config('services.ai_ocr.jwt');
    }

    /**
     * Procesar PDF y extraer datos de orden de compra
     *
     * NOTA: El servidor Node.js actual usa WebSockets para procesamiento asíncrono.
     * Esta implementación es una versión simplificada que intenta usar el endpoint HTTP.
     * Para funcionalidad completa, se recomienda agregar un endpoint síncrono al servidor Node.js
     * o implementar cliente WebSocket en PHP.
     */
    public function processPurchaseOrderPdf(string $pdfPath): array
    {
        try {
            // Generar un jobId único (UUID simulado)
            $jobId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Llamar al endpoint síncrono /upload-oc-sync
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->jwtToken,
                'Accept' => 'application/json',
            ])
            ->timeout(120) // 2 minutos para procesamiento IA
            ->attach(
                'file',
                file_get_contents($pdfPath),
                basename($pdfPath)
            )
            ->post($this->apiBase . '/upload-oc-sync');

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('AI OCR Response', ['response' => $responseData]);

                // La respuesta debería contener los datos parseados
                // Si solo devuelve {ok: true, jobId: ...}, necesitamos WebSockets
                if (isset($responseData['cabecera']) && isset($responseData['detalle'])) {
                    return [
                        'success' => true,
                        'data' => $responseData
                    ];
                }

                // Si solo confirmó recepción, informar que se requiere WebSockets
                return [
                    'success' => false,
                    'error' => 'El servidor requiere WebSockets para procesamiento. Contacta al administrador para habilitar modo síncrono.'
                ];
            }

            Log::error('AI OCR API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return [
                'success' => false,
                'error' => 'Error en el servidor de IA: ' . $response->status() . ' - ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('AI OCR Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar el PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar estructura de respuesta de IA
     */
    public function validateResponse(array $data): bool
    {
        return isset($data['cabecera'])
            && isset($data['detalle'])
            && isset($data['controles'])
            && is_array($data['detalle']);
    }
}
