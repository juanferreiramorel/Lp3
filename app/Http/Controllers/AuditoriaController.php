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
        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');

        // Construir consulta con filtros de búsqueda por todos los campos visibles
        if (!empty($buscar)) {
            // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
            $buscarFecha = $buscar;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscar, $matches)) {
                $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }

            $auditoria = DB::select(
                'SELECT
                    s.id AS id,
                    sc.descripcion AS sucursal,
                    u.name AS usuario,
                    s.operation AS operacion,
                    s.table_name AS tabla,
                    s.changed_at AS fecha,
                    old_data AS anterior,
                    new_data AS nuevo
                FROM audit.log s
                JOIN users u ON s.user_id = u.id
                JOIN sucursales sc USING(id_sucursal)
                WHERE cast(s.id as text) ILIKE ?
                OR sc.descripcion ILIKE ?
                OR u.name ILIKE ?
                OR s.operation ILIKE ?
                OR s.table_name ILIKE ?
                OR cast(s.changed_at as text) ILIKE ?
                OR TO_CHAR(s.changed_at, \'DD/MM/YYYY HH24:MI:SS\') ILIKE ?
                ORDER BY s.id DESC',
                [
                    '%' . $buscar . '%',      // id
                    '%' . $buscar . '%',      // sucursal
                    '%' . $buscar . '%',      // usuario
                    '%' . $buscar . '%',      // operacion
                    '%' . $buscar . '%',      // tabla
                    '%' . $buscarFecha . '%', // fecha (formato BD)
                    '%' . $buscar . '%'       // fecha (formato DD/MM/YYYY HH24:MI:SS)
                ]
            );
        } else {
            $auditoria = DB::select(
                'SELECT
                    s.id AS id,
                    sc.descripcion AS sucursal,
                    u.name AS usuario,
                    s.operation AS operacion,
                    s.table_name AS tabla,
                    s.changed_at AS fecha,
                    old_data AS anterior,
                    new_data AS nuevo
                FROM audit.log s
                JOIN users u ON s.user_id = u.id
                JOIN sucursales sc USING(id_sucursal)
                ORDER BY s.id DESC'
            );
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($auditoria);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($auditoria, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $auditoria = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // si la accion es buscardor entonces significa que se debe recargar mediante ajax la tabla
        if ($request->ajax()) { //devuelve true o false si es ajax o no
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('auditoria.table')->with('auditoria', $auditoria);
        }


        return view('auditoria.index')->with('auditoria', $auditoria);
    }
}
