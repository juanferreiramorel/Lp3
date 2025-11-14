<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Procesar el request
        $response = $next($request);

        // Calcular tiempo de ejecución
        $executionTime = microtime(true) - $startTime;

        // Solo loguear si no es una ruta de assets estáticos
        if (!$this->shouldSkipLogging($request)) {
            Log::channel('requests')->info('Request procesado', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'execution_time_ms' => round($executionTime * 1000, 2),
                'user_id' => auth()->id() ?? 'guest',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]);
        }

        return $response;
    }

    /**
     * Determina si se debe omitir el logging para este request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $path = $request->path();

        // Skip logging para assets estáticos y rutas de health check
        $skipPatterns = [
            'css/*',
            'js/*',
            'images/*',
            'fonts/*',
            'storage/*',
            'favicon.ico',
            'health',
            'up',
        ];

        foreach ($skipPatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
