<?php

namespace App\Http\Controllers;

use App\Exports\CargoExport;
use App\Exports\ClienteExport;
use App\Exports\ComprasExport;
use App\Exports\OrdenCompraExport;
use App\Exports\PedidosExport;
use App\Exports\ProductoExport;
use App\Exports\ProveedorExport;
use App\Exports\SucursalExport;
use App\Exports\VentaExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        $this->middleware('permission:cargos index')->only(['rpt_cargos']);
        $this->middleware('permission:clientes index')->only(['rpt_clientes']);
        $this->middleware('permission:proveedores index')->only(['rpt_proveedores']);
        $this->middleware('permission:productos index')->only(['rpt_productos']);
        $this->middleware('permission:sucursales index')->only(['rpt_sucursales']);
        $this->middleware('permission:ventas index')->only(['rpt_ventas']);
        $this->middleware('permission:compras index')->only(['rpt_compras']);
        $this->middleware('permission:ordencompra index')->only(['rpt_ordenes_compra']);
        $this->middleware('permission:pedidos index')->only(['rpt_pedidos']);
    } 
    public function rpt_cargos(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // Construir WHERE clause
        $where = "WHERE 1=1";
        if(!empty($input['desde']) AND !empty($input['hasta'])){
            $where .= " AND id_cargo BETWEEN ".$input['desde']." AND ".$input['hasta'];
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total FROM cargos ".$where;
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT * FROM cargos ".$where." ORDER BY id_cargo LIMIT {$porPagina} OFFSET {$offset}";
        $cargos = DB::select($query);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            // Para PDF, obtener todos los registros sin paginación
            $cargosAll = DB::select("SELECT * FROM cargos ".$where." ORDER BY id_cargo");
            ##crear vista pdf con loadView y utilizar la misma vista reportes rpt_cargos para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_cargos',
                compact('cargosAll')
            )
            ->setPaper('a4', 'portrait');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            ##retornar pdf con una configuracion de pagina tipo de impresion y que se hara una descarga
            return $pdf->download("ReporteCargos.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){// si se exporta a excel
            // Verificar que se pasen los parámetros correctamente
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;
            
            // llamar la funcion exportar Excel
            return Excel::download(new CargoExport($desde, $hasta), 'cargos.xlsx');
        }

        return view('reportes.rpt_cargos')->with('cargos', $cargos)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_clientes(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // definir variables para filtros
        $filtro_ciudad = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if(!empty($input['ciudad'])){
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_ciudad = " AND c.id_ciudad = ".$input['ciudad'];
        }

        // filtros desde y concatena el sql and
        if(!empty($input['desde'])){
            $filtro_desde = " AND c.id_cliente >= ".$input['desde'];
        }

        // filtros hasta y concatena el sql correspondientes
        if(!empty($input['hasta'])){
            $filtro_hasta = " AND c.id_cliente <= ".$input['hasta'];
        }

        $whereClause = 'WHERE 1=1 '.$filtro_ciudad.' '.$filtro_desde.' '.$filtro_hasta;
        $queryBase = 'FROM clientes c 
            LEFT JOIN ciudades ciu ON c.id_ciudad = ciu.id_ciudad';

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$whereClause}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT c.*, ciu.descripcion as ciudad, extract(year from age(now(), c.clie_fecha_nac))as edad
            {$queryBase}
            {$whereClause}
            ORDER BY c.id_cliente
            LIMIT {$porPagina} OFFSET {$offset}";
        $clientes = DB::select($query);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // Para PDF, obtener todos los registros sin paginación
            $clientesAll = DB::select("SELECT c.*, ciu.descripcion as ciudad, extract(year from age(now(), c.clie_fecha_nac))as edad
                {$queryBase}
                {$whereClause}
                ORDER BY c.id_cliente");
            // crear pdf con loadView y utilizar la misma vista reportes rpt_clientes para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_clientes',
                compact('clientesAll')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReporteClientes.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;
            $ciudad = isset($input['ciudad']) ? $input['ciudad'] : null;
            
            // llamar la funcion exportar Excel
            return Excel::download(new ClienteExport($desde, $hasta, $ciudad), 'clientes.xlsx');
        }

        // consulta para llenar el select de ciudad en reporte cliente
        $ciudad_select = DB::table('ciudades')->pluck('descripcion', 'id_ciudad');

        return view('reportes.rpt_clientes')->with('clientes', $clientes)->with('ciudades', $ciudad_select)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_proveedores(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // Construir WHERE clause
        $where = "WHERE 1=1";
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $where .= " AND id_proveedor BETWEEN " . $input['desde'] . " AND " . $input['hasta'];
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total FROM proveedores ".$where;
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT * FROM proveedores ".$where." ORDER BY id_proveedor LIMIT {$porPagina} OFFSET {$offset}";
        $proveedores = DB::select($query);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            // Para PDF, obtener todos los registros sin paginación
            $proveedoresAll = DB::select("SELECT * FROM proveedores ".$where." ORDER BY id_proveedor");
            ##crear vista pdf con loadView y utilizar la misma vista reportes rpt_proveedores para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_proveedores',
                compact('proveedoresAll')
            )
                ->setPaper('a4', 'portrait'); ## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            ##retornar pdf con una configuracion de pagina tipo de impresion y que se hara una descarga
            return $pdf->download("ReporteProveedores.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new ProveedorExport($desde, $hasta), 'proveedores.xlsx');
        }

        return view('reportes.rpt_proveedores')->with('proveedores', $proveedores)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_productos(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // construir la consulta con join para mostrar la descripción de la marca
        $queryBase = 'FROM productos p
                 LEFT JOIN marcas m ON p.id_marca = m.id_marca';

        // Construir WHERE clause
        $where = "";
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $where = "WHERE p.id_producto BETWEEN " . $input['desde'] . " AND " . $input['hasta'];
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT p.id_producto, p.descripcion, p.precio, m.descripcion AS marca
                 {$queryBase}
                 {$where}
                 ORDER BY p.id_producto
                 LIMIT {$porPagina} OFFSET {$offset}";
        $productos = DB::select($query);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            // Para PDF, obtener todos los registros sin paginación
            $productosAll = DB::select("SELECT p.id_producto, p.descripcion, p.precio, m.descripcion AS marca
                 {$queryBase}
                 {$where}
                 ORDER BY p.id_producto");
            $pdf = Pdf::loadView(
                'reportes.pdf_productos',
                compact('productosAll')
            )
                ->setPaper('a4', 'portrait');

            return $pdf->download("ReporteProductos.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new ProductoExport($desde, $hasta), 'productos.xlsx');
        }

        return view('reportes.rpt_productos')->with('productos', $productos)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }
    public function rpt_sucursales(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // construir la consulta con join para mostrar la descripción de la ciudad
        $queryBase = 'FROM sucursales s 
                 INNER JOIN ciudades c ON s.id_ciudad = c.id_ciudad';

        // Construir WHERE clause
        $where = "";
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $where = "WHERE s.id_sucursal BETWEEN " . $input['desde'] . " AND " . $input['hasta'];
        }

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$where}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT s.id_sucursal, s.descripcion, s.direccion, s.telefono, c.descripcion AS ciudad 
                 {$queryBase}
                 {$where}
                 ORDER BY s.id_sucursal
                 LIMIT {$porPagina} OFFSET {$offset}";
        $sucursales = DB::select($query);

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            // Para PDF, obtener todos los registros sin paginación
            $sucursalesAll = DB::select("SELECT s.id_sucursal, s.descripcion, s.direccion, s.telefono, c.descripcion AS ciudad 
                 {$queryBase}
                 {$where}
                 ORDER BY s.id_sucursal");
            $pdf = Pdf::loadView(
                'reportes.pdf_sucursales',
                compact('sucursalesAll')
            )
                ->setPaper('a4', 'portrait');

            return $pdf->download("ReporteSucursales.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new SucursalExport($desde, $hasta), 'sucursales.xlsx');
        }

        return view('reportes.rpt_sucursales')->with('sucursales', $sucursales)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }
    public function rpt_ventas(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // definir variables para filtros
        $filtro_cliente = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if(!empty($input['cliente'])){
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_cliente = " AND v.id_cliente = ".$input['cliente'];
        }

        // filtros desde y concatena el sql and
        if(!empty($input['desde'])){
            $filtro_desde = " AND v.fecha_venta >= '".$input['desde']."'";
        }

        // filtros hasta y concatena el sql correspondientes
        if(!empty($input['hasta'])){
            $filtro_hasta = " AND v.fecha_venta <= '".$input['hasta']."'";
        }

        $whereClause = "WHERE 1=1 ".$filtro_cliente." ".$filtro_desde." ".$filtro_hasta;
        $queryBase = "FROM ventas v 
            JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN users u ON v.user_id = u.id
            JOIN sucursales s ON v.id_sucursal = s.id_sucursal";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$whereClause}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT v.*, concat(c.clie_nombre, ' ', c.clie_apellido) as cliente, u.name as usuario, s.descripcion as sucursal
            {$queryBase}
            {$whereClause}
            ORDER BY v.id_venta desc
            LIMIT {$porPagina} OFFSET {$offset}";
        $ventas = DB::select($query);

        // recuperar detalles de venta
        // definir array vacio para almacenar los detalles de venta
        $array_detalles = array();
        // validar que existan ventas
        if(count($ventas) > 0){
            // recorrer las ventas con foreach
            foreach($ventas as $ven){
                // consulta para recuperar los detalles de venta
                $detalles = DB::select('SELECT dv.*, p.descripcion as producto
                    FROM detalle_ventas dv
                    LEFT JOIN productos p ON dv.id_producto = p.id_producto
                    WHERE dv.id_venta = ?', [$ven->id_venta]);
                // almacenar los detalles en el array con la llave del id_venta
                $array_detalles[$ven->id_venta] = $detalles;
            }
        }

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // Para PDF, obtener todos los registros sin paginación
            $ventasAll = DB::select("SELECT v.*, concat(c.clie_nombre, ' ', c.clie_apellido) as cliente, u.name as usuario, s.descripcion as sucursal
                {$queryBase}
                {$whereClause}
                ORDER BY v.id_venta desc");
            // recuperar detalles para PDF
            $array_detalles_all = array();
            foreach($ventasAll as $ven){
                $detalles = DB::select('SELECT dv.*, p.descripcion as producto
                    FROM detalle_ventas dv
                    LEFT JOIN productos p ON dv.id_producto = p.id_producto
                    WHERE dv.id_venta = ?', [$ven->id_venta]);
                $array_detalles_all[$ven->id_venta] = $detalles;
            }
            // crear pdf con loadView y utilizar la misma vista reportes rpt_ventas para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_ventas',
                compact('ventasAll', 'array_detalles_all')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReporteVentas.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $cliente = isset($input['cliente']) ? $input['cliente'] : null;
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new VentaExport($cliente, $desde, $hasta), 'ventas.xlsx');
        }

        // consulta para llenar el select de ciudad en reporte cliente
        $clientes = DB::table('clientes')->selectRaw("concat(clie_nombre, ' ', clie_apellido) as cliente, id_cliente")->pluck('cliente', 'id_cliente');

        return view('reportes.rpt_ventas')->with('ventas', $ventas)->with('clientes', $clientes)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_compras(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // definir variables para filtros
        $filtro_proveedor = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if(!empty($input['proveedor'])){
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_proveedor = " AND c.id_proveedor = ".$input['proveedor'];
        }

        // filtros desde y concatena el sql and
        if(!empty($input['desde'])){
            $filtro_desde = " AND c.fecha_compra >= '".$input['desde']."'";
        }

        // filtros hasta y concatena el sql correspondientes
        if(!empty($input['hasta'])){
            $filtro_hasta = " AND c.fecha_compra <= '".$input['hasta']."'";
        }

        $whereClause = "WHERE 1=1 ".$filtro_proveedor." ".$filtro_desde." ".$filtro_hasta;
        $queryBase = "FROM compras c 
            JOIN proveedores p ON c.id_proveedor = p.id_proveedor
            JOIN users u ON c.user_id = u.id
            JOIN sucursales s ON c.id_sucursal = s.id_sucursal";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$whereClause}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT c.*, p.descripcion as proveedor, u.name as usuario, s.descripcion as sucursal
            {$queryBase}
            {$whereClause}
            ORDER BY c.id_compra desc
            LIMIT {$porPagina} OFFSET {$offset}";
        $compras = DB::select($query);

        // recuperar detalles de compra
        // definir array vacio para almacenar los detalles de compra
        $array_detalles = array();
        // validar que existan compras
        if(count($compras) > 0){
            // recorrer las compras con foreach
            foreach($compras as $comp){
                // consulta para recuperar los detalles de compra
                $detalles = DB::select('SELECT dc.*, pr.descripcion as producto
                    FROM detalle_compras dc
                    LEFT JOIN productos pr ON dc.id_producto = pr.id_producto
                    WHERE dc.id_compra = ?', [$comp->id_compra]);
                // almacenar los detalles en el array con la llave del id_compra
                $array_detalles[$comp->id_compra] = $detalles;
            }
        }

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // Para PDF, obtener todos los registros sin paginación
            $comprasAll = DB::select("SELECT c.*, p.descripcion as proveedor, u.name as usuario, s.descripcion as sucursal
                {$queryBase}
                {$whereClause}
                ORDER BY c.id_compra desc");
            // recuperar detalles para PDF
            $array_detalles_all = array();
            foreach($comprasAll as $comp){
                $detalles = DB::select('SELECT dc.*, pr.descripcion as producto
                    FROM detalle_compras dc
                    LEFT JOIN productos pr ON dc.id_producto = pr.id_producto
                    WHERE dc.id_compra = ?', [$comp->id_compra]);
                $array_detalles_all[$comp->id_compra] = $detalles;
            }
            // crear pdf con loadView y utilizar la misma vista reportes rpt_compras para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_compras',
                compact('comprasAll', 'array_detalles_all')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReporteCompras.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $proveedor = isset($input['proveedor']) ? $input['proveedor'] : null;
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new ComprasExport($proveedor, $desde, $hasta), 'compras.xlsx');
        }

        // consulta para llenar el select de proveedor en reporte compras
        $proveedores = DB::table('proveedores')->selectRaw("descripcion, id_proveedor")->pluck('descripcion', 'id_proveedor');

        return view('reportes.rpt_compras')->with('compras', $compras)->with('proveedores', $proveedores)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_ordenes_compra(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // definir variables para filtros
        $filtro_proveedor = "";
        $filtro_estado = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if(!empty($input['proveedor'])){
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_proveedor = " AND oc.id_proveedor = ".$input['proveedor'];
        }

        if(!empty($input['estado'])){
            $filtro_estado = " AND oc.estado = '".$input['estado']."'";
        }

        // filtros desde y concatena el sql and
        if(!empty($input['desde'])){
            $filtro_desde = " AND oc.fecha_orden >= '".$input['desde']."'";
        }

        // filtros hasta y concatena el sql correspondientes
        if(!empty($input['hasta'])){
            $filtro_hasta = " AND oc.fecha_orden <= '".$input['hasta']."'";
        }

        $whereClause = "WHERE 1=1 ".$filtro_proveedor." ".$filtro_estado." ".$filtro_desde." ".$filtro_hasta;
        $queryBase = "FROM ordenes_compra oc 
            JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
            LEFT JOIN users u ON oc.id_usuario = u.id
            LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$whereClause}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT oc.*, p.descripcion as proveedor, u.name as usuario, s.descripcion as sucursal
            {$queryBase}
            {$whereClause}
            ORDER BY oc.id_orden_compra desc
            LIMIT {$porPagina} OFFSET {$offset}";
        $ordenesCompra = DB::select($query);

        // recuperar detalles de orden de compra
        // definir array vacio para almacenar los detalles
        $array_detalles = array();
        // validar que existan ordenes
        if(count($ordenesCompra) > 0){
            // recorrer las ordenes con foreach
            foreach($ordenesCompra as $oc){
                // consulta para recuperar los detalles
                $detalles = DB::select('SELECT doc.*, pr.descripcion as producto
                    FROM detalle_orden_compra doc
                    LEFT JOIN productos pr ON doc.id_producto = pr.id_producto
                    WHERE doc.id_orden_compra = ?', [$oc->id_orden_compra]);
                // almacenar los detalles en el array con la llave del id_orden_compra
                $array_detalles[$oc->id_orden_compra] = $detalles;
            }
        }

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // Para PDF, obtener todos los registros sin paginación
            $ordenesCompraAll = DB::select("SELECT oc.*, p.descripcion as proveedor, u.name as usuario, s.descripcion as sucursal
                {$queryBase}
                {$whereClause}
                ORDER BY oc.id_orden_compra desc");
            // recuperar detalles para PDF
            $array_detalles_all = array();
            foreach($ordenesCompraAll as $oc){
                $detalles = DB::select('SELECT doc.*, pr.descripcion as producto
                    FROM detalle_orden_compra doc
                    LEFT JOIN productos pr ON doc.id_producto = pr.id_producto
                    WHERE doc.id_orden_compra = ?', [$oc->id_orden_compra]);
                $array_detalles_all[$oc->id_orden_compra] = $detalles;
            }
            // crear pdf con loadView y utilizar la misma vista reportes pdf_ordenes_compra para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_ordenes_compra',
                compact('ordenesCompraAll', 'array_detalles_all')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReporteOrdenesCompra.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $proveedor = isset($input['proveedor']) ? $input['proveedor'] : null;
            $estado = isset($input['estado']) ? $input['estado'] : null;
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new OrdenCompraExport($proveedor, $estado, $desde, $hasta), 'ordenes_compra.xlsx');
        }

        // consulta para llenar los selects en reporte ordenes compra
        $proveedores = DB::table('proveedores')->selectRaw("descripcion, id_proveedor")->pluck('descripcion', 'id_proveedor');
        $estados = [
            'PENDIENTE' => 'Pendiente',
            'PROCESADO' => 'Procesado',
            'CANCELADA' => 'Cancelada'
        ];

        return view('reportes.rpt_ordenes_compra')->with('ordenesCompra', $ordenesCompra)->with('proveedores', $proveedores)->with('estados', $estados)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }

    public function rpt_pedidos(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // Configuración de paginación
        $porPagina = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $porPagina;

        // definir variables para filtros
        $filtro_cliente = "";
        $filtro_estado = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if(!empty($input['cliente'])){
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_cliente = " AND p.id_cliente = ".$input['cliente'];
        }

        if(!empty($input['estado'])){
            $filtro_estado = " AND p.estado = '".$input['estado']."'";
        }

        // filtros desde y concatena el sql and
        if(!empty($input['desde'])){
            $filtro_desde = " AND p.fecha_pedido >= '".$input['desde']."'";
        }

        // filtros hasta y concatena el sql correspondientes
        if(!empty($input['hasta'])){
            $filtro_hasta = " AND p.fecha_pedido <= '".$input['hasta']."'";
        }

        $whereClause = "WHERE 1=1 ".$filtro_cliente." ".$filtro_estado." ".$filtro_desde." ".$filtro_hasta;
        $queryBase = "FROM pedidos p 
            JOIN clientes c ON p.id_cliente = c.id_cliente
            LEFT JOIN users u ON p.id_usuario = u.id
            LEFT JOIN sucursales s ON p.id_sucursal = s.id_sucursal";

        // Contar total de registros
        $totalQuery = "SELECT COUNT(*) as total {$queryBase} {$whereClause}";
        $totalResult = DB::select($totalQuery);
        $total = $totalResult[0]->total;

        // Obtener registros paginados
        $query = "SELECT p.*, CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, c.clie_ci, u.name as usuario, s.descripcion as sucursal
            {$queryBase}
            {$whereClause}
            ORDER BY p.id_pedido desc
            LIMIT {$porPagina} OFFSET {$offset}";
        $pedidos = DB::select($query);

        // recuperar detalles de pedido
        // definir array vacio para almacenar los detalles
        $array_detalles = array();
        // validar que existan pedidos
        if(count($pedidos) > 0){
            // recorrer los pedidos con foreach
            foreach($pedidos as $ped){
                // consulta para recuperar los detalles
                $detalles = DB::select('SELECT dp.*, pr.descripcion as producto
                    FROM detalle_pedido dp
                    LEFT JOIN productos pr ON dp.id_producto = pr.id_producto
                    WHERE dp.id_pedido = ?', [$ped->id_pedido]);
                // almacenar los detalles en el array con la llave del id_pedido
                $array_detalles[$ped->id_pedido] = $detalles;
            }
        }

        // Calcular paginación
        $totalPages = ceil($total / $porPagina);

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // Para PDF, obtener todos los registros sin paginación
            $pedidosAll = DB::select("SELECT p.*, CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente, c.clie_ci, u.name as usuario, s.descripcion as sucursal
                {$queryBase}
                {$whereClause}
                ORDER BY p.id_pedido desc");
            // recuperar detalles para PDF
            $array_detalles_all = array();
            foreach($pedidosAll as $ped){
                $detalles = DB::select('SELECT dp.*, pr.descripcion as producto
                    FROM detalle_pedido dp
                    LEFT JOIN productos pr ON dp.id_producto = pr.id_producto
                    WHERE dp.id_pedido = ?', [$ped->id_pedido]);
                $array_detalles_all[$ped->id_pedido] = $detalles;
            }
            // crear pdf con loadView y utilizar la misma vista reportes pdf_pedidos para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_pedidos',
                compact('pedidosAll', 'array_detalles_all')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReportePedidos.pdf");
        }elseif(isset($input['exportar']) && $input['exportar'] == 'excel'){
            // Verificar que se pasen los parámetros correctamente
            $cliente = isset($input['cliente']) ? $input['cliente'] : null;
            $estado = isset($input['estado']) ? $input['estado'] : null;
            $desde = isset($input['desde']) ? $input['desde'] : null;
            $hasta = isset($input['hasta']) ? $input['hasta'] : null;

            // llamar la funcion exportar Excel
            return Excel::download(new PedidosExport($cliente, $estado, $desde, $hasta), 'pedidos.xlsx');
        }

        // consulta para llenar los selects en reporte pedidos
        $clientes = DB::table('clientes')->selectRaw("concat(clie_nombre, ' ', clie_apellido) as cliente, id_cliente")->pluck('cliente', 'id_cliente');
        $estados = [
            'PENDIENTE' => 'Pendiente',
            'PROCESADO' => 'Procesado',
            'CANCELADO' => 'Cancelado'
        ];

        return view('reportes.rpt_pedidos')->with('pedidos', $pedidos)->with('clientes', $clientes)->with('estados', $estados)->with('page', $page)->with('totalPages', $totalPages)->with('total', $total);
    }
}
