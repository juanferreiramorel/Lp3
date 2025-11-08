<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditoriaController extends Controller
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

        // BÚSQUEDA POR TEXTO GENERAL
        $buscar = $request->get('buscar');
        if ($buscar) {
            // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
            $buscarFecha = $buscar;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscar, $matches)) {
                $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }

            $sql_filtros .= " AND (
                cast(s.id as text) ILIKE '%{$buscar}%'
                OR sc.descripcion ILIKE '%{$buscar}%'
                OR u.name ILIKE '%{$buscar}%'
                OR s.operation ILIKE '%{$buscar}%'
                OR s.table_name ILIKE '%{$buscar}%'
                OR cast(s.changed_at as text) ILIKE '%{$buscarFecha}%'
                OR TO_CHAR(s.changed_at, 'DD/MM/YYYY HH24:MI:SS') ILIKE '%{$buscar}%'
            )";
        }

        // Construir WHERE
        $where = "WHERE 1=1" . $sql_filtros;

        // Query base
        $queryBase = "FROM audit.log s
                      JOIN users u ON s.user_id = u.id
                      JOIN sucursales sc USING(id_sucursal)";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

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
        $sucursales = DB::table('sucursales')
            ->pluck('descripcion', 'id_sucursal');
        $operaciones = [
            'INSERT' => 'INSERT',
            'UPDATE' => 'UPDATE',
            'DELETE' => 'DELETE'
        ];
        // Obtener tablas únicas del log de auditoría
        $tablas = DB::table('audit.log')
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name', 'table_name');

        return view('auditoria.index', compact('auditoria', 'usuarios', 'sucursales', 'operaciones', 'tablas'));
    }
}
