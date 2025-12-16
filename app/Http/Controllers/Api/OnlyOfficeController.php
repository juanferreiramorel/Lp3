<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnlyOfficeService;
use App\Models\ContratoGenerado;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OnlyOfficeController extends Controller
{
    protected OnlyOfficeService $onlyOfficeService;

    public function __construct(OnlyOfficeService $onlyOfficeService)
    {
        $this->onlyOfficeService = $onlyOfficeService;
    }

    /**
     * Descarga el documento para OnlyOffice
     */
    public function download(string $documentId, string $filename)
    {
        try {
            $filePath = $this->onlyOfficeService->getDocumentPath($documentId);

            if (!file_exists($filePath)) {
                // Si no existe el archivo DOCX, intentar crearlo desde el contrato HTML
                $contrato = ContratoGenerado::find($documentId);
                
                if (!$contrato) {
                    return response()->json(['error' => 'Contrato no encontrado'], 404);
                }

                // Crear DOCX desde HTML con título simple
                $filePath = $this->onlyOfficeService->createDocxFromHtml(
                    $documentId,
                    $contrato->contenido_generado ?? '<p>Contenido vacío</p>',
                    "Contrato {$documentId}"
                );
            }

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error("OnlyOffice download error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Callback de OnlyOffice cuando se guarda el documento
     */
    public function callback(Request $request, string $documentId)
    {
        $data = $request->all();
        
        Log::info("OnlyOffice callback para documento {$documentId}", $data);

        // Estados de OnlyOffice:
        // 0 - No se ha encontrado el documento
        // 1 - El documento está siendo editado
        // 2 - El documento está listo para guardar
        // 3 - Error al guardar
        // 4 - El documento no ha cambiado
        // 6 - El documento está siendo editado y se debe guardar forzadamente
        // 7 - Error al forzar guardado

        $status = $data['status'] ?? 0;

        switch ($status) {
            case 2: // Documento listo para guardar (el usuario cerró el editor)
            case 6: // Forzar guardado (autosave)
                if (isset($data['url'])) {
                    $saved = $this->onlyOfficeService->saveDocument($documentId, $data['url']);
                    
                    if ($saved) {
                        // Actualizar el campo archivo_docx en el contrato
                        $contrato = ContratoGenerado::find($documentId);
                        if ($contrato) {
                            $contrato->archivo_docx = $documentId . '.docx';
                            $contrato->save();
                        }
                    }
                    
                    return response()->json(['error' => $saved ? 0 : 1]);
                }
                break;
                
            case 1: // Documento siendo editado
            case 4: // Sin cambios
                return response()->json(['error' => 0]);
                
            default:
                Log::warning("OnlyOffice callback con status desconocido: {$status}");
                break;
        }

        return response()->json(['error' => 0]);
    }

    /**
     * Obtiene la configuración del editor para un contrato
     */
    public function editorConfig(Request $request, string $documentId)
    {
        $contrato = ContratoGenerado::find($documentId);
        
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        // Intentar obtener usuario de sesión web o API
        $user = auth()->user() ?? auth('web')->user();
        
        // Si no hay usuario autenticado, usar valores por defecto
        $userId = $user ? (string) $user->id : 'guest';
        $userName = $user ? ($user->name ?? $user->email) : 'Invitado';
        
        $canEdit = $request->get('mode', 'edit') === 'edit';
        
        $filename = "contrato_{$documentId}.docx";

        $config = $this->onlyOfficeService->getEditorConfig(
            $documentId,
            $filename,
            $userId,
            $userName,
            $canEdit
        );

        return response()->json([
            'config' => $config,
            'serverUrl' => $this->onlyOfficeService->getServerUrl(),
            'apiUrl' => $this->onlyOfficeService->getApiUrl(),
        ]);
    }

    /**
     * Verifica el estado del servidor de OnlyOffice
     */
    public function healthCheck()
    {
        try {
            // Usar URL interna para comunicación entre contenedores Docker
            $internalUrl = config('onlyoffice.document_server_internal_url', 'http://onlyoffice');
            $publicUrl = $this->onlyOfficeService->getServerUrl();
            
            $response = @file_get_contents($internalUrl . '/healthcheck', false, stream_context_create([
                'http' => ['timeout' => 5]
            ]));
            
            $isHealthy = $response === 'true';
            
            return response()->json([
                'status' => $isHealthy ? 'ok' : 'error',
                'serverUrl' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
