<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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
    /**
     * Obtiene los IDs de sucursales permitidos para el usuario autenticado.
     *
     * @param  \App\Models\User|null  $user
     * @return array<int, int>
     */
    protected function getSucursalesPermitidasIds($user = null)
    {
        $user = $user ?: auth()->user();

        if (!$user) {
            return [];
        }

        $ids = DB::table('sucursal_user')
            ->where('user_id', $user->id)
            ->pluck('id_sucursal')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($user->id_sucursal)) {
            $ids[] = (int) $user->id_sucursal;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Retorna un listado (id => descripcion) de las sucursales permitidas para el usuario autenticado.
     * Si el usuario no tiene restricciones retorna todas las sucursales.
     *
     * @param  \App\Models\User|null  $user
     * @return \Illuminate\Support\Collection
     */
    protected function getSucursalesPermitidas($user = null)
    {
        $ids = $this->getSucursalesPermitidasIds($user);

        $query = DB::table('sucursales')
            ->orderBy('descripcion');

        if (!empty($ids)) {
            $query->whereIn('id_sucursal', $ids);
        }

        return $query->pluck('descripcion', 'id_sucursal');
    }

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
