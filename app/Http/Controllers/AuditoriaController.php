<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditoriaController extends AppBaseController
{
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:auditoria index')->only(['index']);
    }
    public function index(Request $request)
    {
        $input = $request->all();
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($input['fecha_desde']) && !empty($input['fecha_hasta'])) {
            $fecha_desde = $input['fecha_desde'];
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND s.changed_at::date BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($input['fecha_desde'])) {
            $fecha_desde = $input['fecha_desde'];
            $sql_filtros .= " AND s.changed_at::date >= '{$fecha_desde}'";
        } elseif (!empty($input['fecha_hasta'])) {
            $fecha_hasta = $input['fecha_hasta'];
            $sql_filtros .= " AND s.changed_at::date <= '{$fecha_hasta}'";
        }

        // FILTRO POR USUARIO
        if (!empty($input['user_id'])) {
            $sql_filtros .= " AND s.user_id = " . (int)$input['user_id'];
        }

        // FILTRO POR OPERACIÓN
        if (!empty($input['operation'])) {
            $sql_filtros .= " AND s.operation = '" . $input['operation'] . "'";
        }

        // FILTRO POR TABLA
        if (!empty($input['table_name'])) {
            $sql_filtros .= " AND s.table_name = '" . $input['table_name'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($input['id_sucursal'])) {
            $sql_filtros .= " AND sc.id_sucursal = " . (int)$input['id_sucursal'];
        }

        // BÚSQUEDA POR TEXTO GENERAL CON MODO
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_filtros .= " AND s.id = " . (int)$searchConfig['term'];
            } else {
                // Búsqueda general en múltiples campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                $sql_filtros .= " AND (
                    cast(s.id as text) ILIKE '%{$buscarTerm}%'
                    OR sc.descripcion ILIKE '%{$buscarTerm}%'
                    OR u.name ILIKE '%{$buscarTerm}%'
                    OR s.operation ILIKE '%{$buscarTerm}%'
                    OR s.table_name ILIKE '%{$buscarTerm}%'
                    OR cast(s.changed_at as text) ILIKE '%{$buscarFecha}%'
                    OR TO_CHAR(s.changed_at, 'DD/MM/YYYY HH24:MI:SS') ILIKE '%{$buscarTerm}%'
                )";
            }
        }

        // Construir WHERE
        $where = "WHERE 1=1" . $sql_filtros;

        // Query base
        $queryBase = "FROM audit.log s
                      JOIN users u ON s.user_id = u.id
                      JOIN sucursales sc USING(id_sucursal)";

        // Totales por operación (consideran filtros activos)
        $statsQuery = "SELECT
                            COUNT(*) AS total,
                            SUM(CASE WHEN s.operation = 'AGREGA' THEN 1 ELSE 0 END) AS agrega,
                            SUM(CASE WHEN s.operation = 'MODIFICA' THEN 1 ELSE 0 END) AS modifica,
                            SUM(CASE WHEN s.operation = 'ELIMINA' THEN 1 ELSE 0 END) AS elimina
                       {$queryBase} {$where}";
        $statsRow = DB::selectOne($statsQuery);
        $statsTotals = [
            'total' => (int) ($statsRow->total ?? 0),
            'agrega' => (int) ($statsRow->agrega ?? 0),
            'modifica' => (int) ($statsRow->modifica ?? 0),
            'elimina' => (int) ($statsRow->elimina ?? 0),
        ];

        // Contar total (para la paginación) usando el resultado anterior
        $total = $statsTotals['total'];

        // Paginación
        $page = $request->input('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Obtener registros paginados
        $query = "SELECT s.id AS id,
                         sc.descripcion AS sucursal,
                         u.name AS usuario,
                         s.operation AS operacion,
                         s.table_name AS tabla,
                         s.changed_at AS fecha,
                         old_data AS anterior,
                         new_data AS nuevo
                  {$queryBase} {$where}
                  ORDER BY s.id DESC
                  LIMIT {$perPage} OFFSET {$offset}";

        $auditoria = DB::select($query);

        // Crear paginador manual
        $auditoria = new LengthAwarePaginator(
            $auditoria,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('auditoria.table')->with('auditoria', $auditoria);
        }

        // Obtener datos para los filtros
        $usuarios = DB::table('users')
            ->pluck('name', 'id');
        $sucursales = $this->getSucursalesPermitidas();
        $operaciones = [
            'AGREGA' => 'AGREGA',
            'MODIFICA' => 'MODIFICA',
            'ELIMINA' => 'ELIMINA'
        ];
        // Obtener tablas únicas del log de auditoría
        $tablas = DB::table('audit.log')
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name', 'table_name');

        return view('auditoria.index', compact('auditoria', 'usuarios', 'sucursales', 'operaciones', 'tablas', 'statsTotals'));
    }
}
