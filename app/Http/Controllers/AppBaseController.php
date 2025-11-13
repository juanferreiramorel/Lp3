<?php

namespace App\Http\Controllers;

use InfyOm\Generator\Utils\ResponseUtil;

/**
 * @OA\Server(url="/api")
 * @OA\Info(
 *   title="InfyOm Laravel Generator APIs",
 *   version="1.0.0"
 * )
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $message)
    {
        return response()->json(ResponseUtil::makeResponse($message, $result));
    }

    public function sendError($error, $code = 404)
    {
        return response()->json(ResponseUtil::makeError($error), $code);
    }

    public function sendSuccess($message)
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    /**
     * Procesa la búsqueda detectando si es por ID exacto o búsqueda múltiple
     * Soporta sintaxis: id:123 o #123 para búsqueda exacta por ID
     *
     * @param string $buscar Término de búsqueda
     * @param string $searchMode Modo de búsqueda ('all' o 'id')
     * @return array ['mode' => 'id'|'all', 'term' => 'valor limpio']
     */
    protected function parseSearchMode($buscar, $searchMode = 'all')
    {
        // Si no hay búsqueda, retornar null
        if (empty($buscar)) {
            return null;
        }

        // Detectar sintaxis especial: id:123 o #123
        if (preg_match('/^(id:|#)(\d+)$/', $buscar, $matches)) {
            return [
                'mode' => 'id',
                'term' => $matches[2]
            ];
        }

        // Si el modo es 'id' y el término es numérico, es búsqueda por ID
        if ($searchMode === 'id' && is_numeric($buscar)) {
            return [
                'mode' => 'id',
                'term' => $buscar
            ];
        }

        // Búsqueda normal en todos los campos
        return [
            'mode' => 'all',
            'term' => $buscar
        ];
    }
}
