<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class StockController extends AppBaseController
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:stock index')->only(['index']);
    }
    public function index(Request $request)
    {
        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);
        $input = $request->all();
        $filtro_sucursales = "";
        $filtro_productos = "";
        // validar que contenga un valor el buscar
        $sql = '';// definir una variable sql vacia

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql = "AND s.id_stock = " . (int)$searchConfig['term'];
            } else {
                // Búsqueda general en múltiples campos
                $buscarTerm = $searchConfig['term'];
                $sql = "AND (p.descripcion iLIKE '%" . $buscarTerm . "%'
                or m.descripcion iLIKE '%" . $buscarTerm . "%'
                or sc.descripcion iLIKE '%" . $buscarTerm . "%'
                or cast(s.cantidad as text)iLIKE '%" . $buscarTerm . "%')";
            }
        }
        
        if (!empty($input['sucursales'])) {
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_sucursales = " AND sc.id_sucursal = " . $input['sucursales'];
        }
        if (!empty($input['productos'])) {
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_productos = " AND p.id_producto = " . $input['productos'];
        }
        $idsPermitidos = $this->getSucursalesPermitidasIds();
        $filtroSucursalesPermitidas = '';
        if (!empty($idsPermitidos)) {
            $filtroSucursalesPermitidas = ' AND sc.id_sucursal IN (' . implode(',', $idsPermitidos) . ')';
        }
        
        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 's.id_stock', 'first' => 'desc'],
            'sucursal' => ['label' => 'Sucursal', 'sort' => 'sc.descripcion'],
            'producto' => ['label' => 'Producto', 'sort' => 'p.descripcion'],
            'marca' => ['label' => 'Marca', 'sort' => 'm.descripcion'],
            'cantidad' => ['label' => 'Cantidad', 'sort' => 's.cantidad', 'format' => 'number', 'first' => 'desc'],
        ];

        // This listing's natural order is by product, which the grid does not
        // show as a column, so it is passed as the default expression instead
        // of being approximated with s.id_stock.
        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request, 'p.id_producto');

        // Consulta para obtener los productos con la marca asociada y si posee filtros
        // Solo mostrar productos que controlan stock, están activos y tienen marcas activas
        $seleccion = 'SELECT s.cantidad, m.descripcion as marca, p.descripcion AS producto, sc.descripcion as sucursal,
            s.id_stock AS id, p.descripcion
             FROM stocks s
             JOIN sucursales sc USING(id_sucursal)
             JOIN productos p USING(id_producto)
             JOIN marcas m USING(id_marca)
             WHERE p.controlar_stock = true
             AND p.activo = true
             AND m.activo = true
             ' . $sql . ' ' . $filtro_sucursales . ' ' . $filtro_productos . ' ' . $filtroSucursalesPermitidas . '
             ORDER BY ' . $sort->orderBy();

        // Exports run the same query without the page window, so the file
        // carries every row the current filters match, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => DB::select($seleccion . ' LIMIT ' . \App\Support\TableExport::MAX_ROWS), 'stock');
        }

        $stock = DB::select($seleccion);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($stock);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($stock, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $stock = new LengthAwarePaginator(
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
        $sucursales = $this->getSucursalesPermitidas();
        // Solo mostrar productos activos que controlan stock en el combo de filtro
        $productos = DB::table('productos')
            ->where('controlar_stock', true)
            ->where('activo', true)
            ->pluck('descripcion', 'id_producto');

         // Verificamos si la petición es AJAX
        if ($request->ajax()) { //devuelve true o false si es ajax o no
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('stock.table')
                ->with('stock', $stock)
                ->with('sucursales', $sucursales)
                ->with('productos', $productos)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }


        return view('stock.index')
            ->with('stock', $stock)
            ->with('sucursales', $sucursales)
            ->with('productos', $productos)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }
}
