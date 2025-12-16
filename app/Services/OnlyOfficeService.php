<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OnlyOfficeService
{
    protected string $serverUrl;
    protected string $internalUrl;
    protected string $callbackUrl;
    protected bool $jwtEnabled;
    protected string $jwtSecret;
    protected string $storagePath;

    public function __construct()
    {
        $this->serverUrl = config('onlyoffice.document_server_url');
        $this->internalUrl = config('onlyoffice.document_server_internal_url');
        $this->callbackUrl = config('onlyoffice.callback_url');
        $this->jwtEnabled = config('onlyoffice.jwt_enabled');
        $this->jwtSecret = config('onlyoffice.jwt_secret');
        $this->storagePath = config('onlyoffice.storage_path');
    }

    /**
     * Genera la configuración del editor para un documento
     */
    public function getEditorConfig(string $documentId, string $filename, string $userId, string $userName, bool $canEdit = true): array
    {
        $documentUrl = $this->getDocumentUrl($documentId, $filename);
        $callbackUrl = $this->getCallbackUrl($documentId);
        
        $config = [
            'document' => [
                'fileType' => pathinfo($filename, PATHINFO_EXTENSION),
                'key' => $this->generateDocumentKey($documentId),
                'title' => $filename,
                'url' => $documentUrl,
            ],
            'documentType' => 'word',
            'editorConfig' => [
                'callbackUrl' => $callbackUrl,
                'lang' => 'es',
                'mode' => $canEdit ? 'edit' : 'view',
                'user' => [
                    'id' => $userId,
                    'name' => $userName,
                ],
                'customization' => [
                    'autosave' => true,
                    'chat' => false,
                    'comments' => true,
                    'compactHeader' => false,
                    'compactToolbar' => false,
                    'feedback' => false,
                    'forcesave' => true,
                    'help' => false,
                    'hideRightMenu' => false,
                    'hideRulers' => false,
                    'logo' => [
                        'image' => url('/img/logo.png'),
                    ],
                    'zoom' => 100,
                ],
            ],
            'height' => '100%',
            'width' => '100%',
            'type' => 'desktop',
        ];

        if ($this->jwtEnabled && !empty($this->jwtSecret)) {
            $config['token'] = $this->generateToken($config);
        }

        return $config;
    }

    /**
     * Genera una clave única para el documento (cambia con cada edición)
     */
    public function generateDocumentKey(string $documentId): string
    {
        $filePath = $this->getDocumentPath($documentId);
        
        if (file_exists($filePath)) {
            return $documentId . '_' . filemtime($filePath);
        }
        
        return $documentId . '_' . time();
    }

    /**
     * Obtiene la URL pública del documento
     * OnlyOffice necesita acceder a esta URL desde su contenedor,
     * por lo que usamos la URL interna de Docker (http://app)
     */
    public function getDocumentUrl(string $documentId, string $filename): string
    {
        // Usar la URL interna del contenedor Laravel para que OnlyOffice pueda acceder
        $baseUrl = config('onlyoffice.callback_url', 'http://app');
        return "{$baseUrl}/api/onlyoffice/download/{$documentId}/{$filename}";
    }

    /**
     * Obtiene la URL de callback para guardar
     * OnlyOffice llamará a esta URL cuando el usuario guarde el documento
     */
    public function getCallbackUrl(string $documentId): string
    {
        // Usar la URL interna del contenedor Laravel
        $baseUrl = config('onlyoffice.callback_url', 'http://app');
        return "{$baseUrl}/api/onlyoffice/callback/{$documentId}";
    }

    /**
     * Obtiene el path del documento en el sistema de archivos
     */
    public function getDocumentPath(string $documentId): string
    {
        return $this->storagePath . '/' . $documentId . '.docx';
    }

    /**
     * Crea un documento DOCX desde contenido HTML
     */
    public function createDocxFromHtml(string $documentId, string $htmlContent, string $title = 'Contrato'): string
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Configuración del documento
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ES_ES));
        
        // Estilos por defecto
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        
        // Añadir sección con márgenes A4
        $section = $phpWord->addSection([
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
        ]);

        // Limpiar HTML para que sea XHTML válido (PHPWord requiere XML válido)
        $cleanHtml = $this->cleanHtmlForXml($htmlContent);

        // Convertir HTML a elementos de Word (con fallback a texto plano)
        $this->addHtmlToSection($section, $cleanHtml);

        // Guardar como DOCX
        $filePath = $this->getDocumentPath($documentId);
        $this->ensureDirectoryExists(dirname($filePath));
        
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Limpia HTML para convertirlo a XHTML válido que PHPWord pueda parsear
     */
    protected function cleanHtmlForXml(string $html): string
    {
        // Remover contenido problemático
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Convertir entidades HTML a UTF-8
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        // Convertir solo los tags auto-cerrables más comunes
        $html = preg_replace('/<br\s*\/?>/i', '<br/>', $html);
        $html = preg_replace('/<hr\s*\/?>/i', '<hr/>', $html);
        
        // Eliminar atributos style que pueden causar problemas
        $html = preg_replace('/\s+style\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+style\s*=\s*'[^']*'/i", '', $html);
        
        // Eliminar clases CSS
        $html = preg_replace('/\s+class\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+class\s*=\s*'[^']*'/i", '', $html);
        
        return $html;
    }
    
    /**
     * Añade contenido HTML a una sección de PHPWord de forma segura
     */
    protected function addHtmlToSection($section, string $html): void
    {
        try {
            // Intentar añadir como HTML
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
        } catch (\Exception $e) {
            // Si falla, añadir como texto plano
            \Log::warning("PHPWord HTML parse failed, using plain text: " . $e->getMessage());
            
            // Convertir HTML a texto plano preservando estructura básica
            $text = $this->htmlToPlainText($html);
            
            // Dividir en párrafos y añadir cada uno
            $paragraphs = preg_split('/\n\s*\n/', $text);
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $section->addText($paragraph);
                }
            }
        }
    }
    
    /**
     * Convierte HTML a texto plano preservando estructura básica
     */
    protected function htmlToPlainText(string $html): string
    {
        // Reemplazar saltos de línea HTML
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $html = preg_replace('/<\/div>/i', "\n", $html);
        $html = preg_replace('/<\/h[1-6]>/i', "\n\n", $html);
        $html = preg_replace('/<\/li>/i', "\n", $html);
        
        // Eliminar todos los tags HTML
        $text = strip_tags($html);
        
        // Limpiar espacios múltiples
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Guarda el documento desde el callback de OnlyOffice
     */
    public function saveDocument(string $documentId, string $downloadUrl): bool
    {
        try {
            $content = file_get_contents($downloadUrl);
            
            if ($content === false) {
                \Log::error("OnlyOffice: No se pudo descargar el documento desde {$downloadUrl}");
                return false;
            }

            $filePath = $this->getDocumentPath($documentId);
            $this->ensureDirectoryExists(dirname($filePath));
            
            file_put_contents($filePath, $content);
            
            \Log::info("OnlyOffice: Documento {$documentId} guardado correctamente");
            return true;
        } catch (\Exception $e) {
            \Log::error("OnlyOffice: Error al guardar documento - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un documento
     */
    public function deleteDocument(string $documentId): bool
    {
        $filePath = $this->getDocumentPath($documentId);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }

    /**
     * Verifica si un documento existe
     */
    public function documentExists(string $documentId): bool
    {
        return file_exists($this->getDocumentPath($documentId));
    }

    /**
     * Genera un token JWT para la configuración
     */
    protected function generateToken(array $payload): string
    {
        if (empty($this->jwtSecret)) {
            return '';
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $this->jwtSecret, true));

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Codifica en base64 URL-safe
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Asegura que el directorio existe
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Obtiene la URL del API de OnlyOffice
     */
    public function getApiUrl(): string
    {
        return $this->serverUrl . '/web-apps/apps/api/documents/api.js';
    }

    /**
     * Obtiene la URL del servidor
     */
    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }
}
