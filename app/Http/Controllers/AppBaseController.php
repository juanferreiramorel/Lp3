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
    /**
     * Retorna el query base para sucursales activas.
     * Este es el único lugar donde se debe definir el filtro de estado.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getSucursalesQuery($includeId = null)
    {
        return DB::table('sucursales')
            ->where(function ($query) use ($includeId) {
                $query->where('estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('id_sucursal', $includeId);
                    } else {
                        $query->orWhere('id_sucursal', $includeId);
                    }
                }
            })
            ->orderBy('descripcion');
    }

    /**
     * Retorna todas las sucursales activas.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getTodasSucursalesActivas($includeId = null)
    {
        return $this->getSucursalesQuery($includeId)->pluck('descripcion', 'id_sucursal');
    }

    /**
     * Retorna un listado (id => descripcion) de las sucursales permitidas para el usuario autenticado.
     * Si el usuario no tiene restricciones retorna todas las sucursales.
     *
     * @param  \App\Models\User|null  $user
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getSucursalesPermitidas($user = null, $includeId = null)
    {
        $ids = $this->getSucursalesPermitidasIds($user);

        $query = $this->getSucursalesQuery($includeId);

        if (!empty($ids)) {
            $query->whereIn('id_sucursal', $ids);
        }

        return $query->pluck('descripcion', 'id_sucursal');
    }

    /**
     * Retorna un listado (id => descripcion) de los métodos de pago activos.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getMetodosPago($includeId = null)
    {
        return DB::table('metodo_pagos')
            ->where(function ($query) use ($includeId) {
                $query->where('estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('id_metodo_pago', $includeId);
                    } else {
                        $query->orWhere('id_metodo_pago', $includeId);
                    }
                }
            })
            ->pluck('descripcion', 'id_metodo_pago');
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
    /**
     * Retorna un listado (id => razon_social) de las empresas activas.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getEmpresasActivas()
    {
        return \App\Models\Empresa::active()
            ->select(DB::raw("id_empresa, COALESCE(nombre_comercial, razon_social) as nombre"))
            ->orderBy('nombre')
            ->pluck('nombre', 'id_empresa');
    }

    /**
     * Retorna el query base para departamentos activos.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getDepartamentosQuery($includeId = null)
    {
        return DB::table('departamentos')
            ->where(function ($query) use ($includeId) {
                $query->where('estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('id_departamento', $includeId);
                    } else {
                        $query->orWhere('id_departamento', $includeId);
                    }
                }
            })
            ->orderBy('descripcion');
    }

    /**
     * Retorna un listado (id => descripcion) de los departamentos activos.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getDepartamentosDropdown($includeId = null)
    {
        return $this->getDepartamentosQuery($includeId)->pluck('descripcion', 'id_departamento');
    }

    /**
     * Retorna el query base para cargos activos.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getCargosQuery($includeId = null)
    {
        return DB::table('cargos')
            ->where(function ($query) use ($includeId) {
                $query->where('estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('id_cargo', $includeId);
                    } else {
                        $query->orWhere('id_cargo', $includeId);
                    }
                }
            })
            ->orderBy('descripcion');
    }

    /**
     * Retorna un listado (id => descripcion) de los cargos activos.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getCargosDropdown($includeId = null)
    {
        return $this->getCargosQuery($includeId)->pluck('descripcion', 'id_cargo');
    }

    /**
     * Retorna el query base para ciudades activas.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getCiudadesQuery($includeId = null)
    {
        return DB::table('ciudades')
            ->where(function ($query) use ($includeId) {
                $query->where('estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('id_ciudad', $includeId);
                    } else {
                        $query->orWhere('id_ciudad', $includeId);
                    }
                }
            })
            ->orderBy('descripcion');
    }

    /**
     * Retorna un listado (id => descripcion) de las ciudades activas.
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getCiudadesDropdown($includeId = null)
    {
        return $this->getCiudadesQuery($includeId)->pluck('descripcion', 'id_ciudad');
    }

    /**
     * Retorna un listado de ciudades activas con su departamento.
     * Utilizado en vistas que requieren datos adicionales (ej: data-attributes o filtrado js).
     *
     * @param int|array|null $includeId ID(s) a incluir incluso si no están activos
     * @return \Illuminate\Support\Collection
     */
    protected function getCiudadesConDepartamento($includeId = null)
    {
        // Reconstruimos la query para incluir el join con departamentos
        return DB::table('ciudades')
            ->join('departamentos', 'ciudades.id_departamento', '=', 'departamentos.id_departamento')
            ->select('ciudades.*', 'departamentos.descripcion as departamento')
            ->where(function ($query) use ($includeId) {
                $query->where('ciudades.estado', true);
                if ($includeId) {
                    if (is_array($includeId)) {
                        $query->orWhereIn('ciudades.id_ciudad', $includeId);
                    } else {
                        $query->orWhere('ciudades.id_ciudad', $includeId);
                    }
                }
            })
            ->orderBy('ciudades.descripcion')
            ->get();
    }
}
