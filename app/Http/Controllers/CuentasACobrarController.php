<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CuentasACobrarController extends Controller
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cuentasacobrar index')->only(['index']);
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

            // Limpiar separadores de miles para búsqueda de montos
            $buscarMonto = str_replace('.', '', $buscar);

            $cuentasacobrar = DB::select(
                'SELECT id_cta AS nro_cuenta, CONCAT(clie_nombre,\' \',clie_apellido) AS cliente, factura_nro, fecha_venta,
                importe, ca.estado, vencimiento, nro_cuota AS nro_cuotas
                FROM cuentas_a_cobrar ca
                JOIN clientes USING(id_cliente)
                JOIN ventas USING(id_venta)
                WHERE cast(id_cta as text) ILIKE ?
                OR CONCAT(clie_nombre,\' \',clie_apellido) ILIKE ?
                OR clie_ci ILIKE ?
                OR factura_nro ILIKE ?
                OR cast(fecha_venta as text) ILIKE ?
                OR TO_CHAR(fecha_venta, \'DD/MM/YYYY\') ILIKE ?
                OR cast(importe as text) ILIKE ?
                OR TO_CHAR(importe, \'FM999G999G999G999G999\') ILIKE ?
                OR ca.estado ILIKE ?
                OR cast(vencimiento as text) ILIKE ?
                OR TO_CHAR(vencimiento, \'DD/MM/YYYY\') ILIKE ?
                OR cast(nro_cuota as text) ILIKE ?
                ORDER BY vencimiento DESC',
                [
                    '%' . $buscar . '%',      // nro_cuenta
                    '%' . $buscar . '%',      // cliente
                    '%' . $buscar . '%',      // clie_ci
                    '%' . $buscar . '%',      // factura_nro
                    '%' . $buscarFecha . '%', // fecha_venta (formato BD)
                    '%' . $buscar . '%',      // fecha_venta (formato DD/MM/YYYY)
                    '%' . $buscarMonto . '%', // importe (sin separadores)
                    '%' . $buscar . '%',      // importe (con separadores)
                    '%' . $buscar . '%',      // estado
                    '%' . $buscarFecha . '%', // vencimiento (formato BD)
                    '%' . $buscar . '%',      // vencimiento (formato DD/MM/YYYY)
                    '%' . $buscar . '%'       // nro_cuotas
                ]
            );
        } else {
            $cuentasacobrar = DB::select(
                'SELECT id_cta AS nro_cuenta, CONCAT(clie_nombre,\' \',clie_apellido) AS cliente, factura_nro, fecha_venta,
                importe, ca.estado, vencimiento, nro_cuota AS nro_cuotas
                FROM cuentas_a_cobrar ca
                JOIN clientes USING(id_cliente)
                JOIN ventas USING(id_venta)
                ORDER BY vencimiento DESC'
            );
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                   // cantidad de registros por página
        $total = count($cuentasacobrar);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($cuentasacobrar, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $cuentasacobrar = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

         // Verificamos si la petición es AJAX
        if ($request->ajax()) { //devuelve true o false si es ajax o no
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('cuentasacobrar.table')->with('cuentasacobrar', $cuentasacobrar);
        }


        return view('cuentasacobrar.index')->with('cuentasacobrar', $cuentasacobrar);
    }
}
