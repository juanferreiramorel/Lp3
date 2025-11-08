<?php

namespace App\Http\Controllers;

use App\Exports\CargoExport;
use App\Exports\ClienteExport;
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
    } 
    public function rpt_cargos(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // verificar si los filtros no estan vacios para realizar el where en la consulta cargos
        if(!empty($input['desde']) AND !empty($input['hasta'])){
            $cargos = DB::select('SELECT * FROM cargos WHERE id_cargo 
            BETWEEN '.$input['desde'].' AND '.$input['hasta']);
        }else{
            // si no se reciben datos, se traen todos los registros (SELECT * FROM cargos)
            $cargos = DB::select('SELECT * FROM cargos');
        }

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            ##crear vista pdf con loadView y utilizar la misma vista reportes rpt_cargos para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_cargos',
                compact('cargos')
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


        return view('reportes.rpt_cargos')->with('cargos', $cargos);
    }

    public function rpt_clientes(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

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

        // concatenar todos los filtros en el where si llega a recibir algun valor, por defecto esta where 1=1 que siempre sera true es para evitar fallo en la consulta normal
        $clientes = DB::select('SELECT c.*, ciu.descripcion as ciudad, extract(year from age(now(), c.clie_fecha_nac))as edad
            FROM clientes c 
            LEFT JOIN ciudades ciu ON c.id_ciudad = ciu.id_ciudad
            WHERE 1=1 '.$filtro_ciudad.' '.$filtro_desde.' '.$filtro_hasta.'
            ORDER BY c.id_cliente');

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // crear pdf con loadView y utilizar la misma vista reportes rpt_clientes para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_clientes',
                compact('clientes')
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

        return view('reportes.rpt_clientes')->with('clientes', $clientes)->with('ciudades', $ciudad_select);
    }

    public function rpt_proveedores(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // verificar si los filtros no estan vacios para realizar el where en la consulta proveedores
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $proveedores = DB::select('SELECT * FROM proveedores WHERE id_proveedor 
        BETWEEN ' . $input['desde'] . ' AND ' . $input['hasta']);
        } else {
            // si no se reciben datos, se traen todos los registros (SELECT * FROM proveedores)
            $proveedores = DB::select('SELECT * FROM proveedores');
        }

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            ##crear vista pdf con loadView y utilizar la misma vista reportes rpt_proveedores para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_proveedores',
                compact('proveedores')
            )
                ->setPaper('a4', 'portrait'); ## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            ##retornar pdf con una configuracion de pagina tipo de impresion y que se hara una descarga
            return $pdf->download("ReporteProveedores.pdf");
        }

        return view('reportes.rpt_proveedores')->with('proveedores', $proveedores);
    }

    public function rpt_productos(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // construir la consulta con join para mostrar la descripción de la marca
        $baseQuery = 'SELECT p.id_producto, p.descripcion, p.precio, p.tipo_iva, m.descripcion AS marca 
                 FROM productos p 
                 LEFT JOIN marcas m ON p.id_marca = m.id_marca';

        // verificar si los filtros no estan vacios para realizar el where
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $productos = DB::select($baseQuery . ' WHERE p.id_producto BETWEEN ' . $input['desde'] . ' AND ' . $input['hasta']);
        } else {
            // si no se reciben datos, se traen todos los registros
            $productos = DB::select($baseQuery);
        }

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            $pdf = Pdf::loadView(
                'reportes.pdf_productos',
                compact('productos')
            )
                ->setPaper('a4', 'portrait');

            return $pdf->download("ReporteProductos.pdf");
        }

        return view('reportes.rpt_productos')->with('productos', $productos);
    }
    public function rpt_sucursales(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

        // construir la consulta con join para mostrar la descripción de la ciudad
        $baseQuery = 'SELECT s.id_sucursal, s.descripcion, s.direccion, s.telefono, c.descripcion AS ciudad 
                 FROM sucursales s 
                 INNER JOIN ciudades c ON s.id_ciudad = c.id_ciudad';

        // verificar si los filtros no estan vacios para realizar el where
        if (!empty($input['desde']) and !empty($input['hasta'])) {
            $sucursales = DB::select($baseQuery . ' WHERE s.id_sucursal BETWEEN ' . $input['desde'] . ' AND ' . $input['hasta']);
        } else {
            // si no se reciben datos, se traen todos los registros
            $sucursales = DB::select($baseQuery);
        }

        if (isset($input['exportar']) && $input['exportar'] == 'pdf') {
            $pdf = Pdf::loadView(
                'reportes.pdf_sucursales',
                compact('sucursales')
            )
                ->setPaper('a4', 'portrait');

            return $pdf->download("ReporteSucursales.pdf");
        }

        return view('reportes.rpt_sucursales')->with('sucursales', $sucursales);
    }
    public function rpt_ventas(Request $request)
    {
        // recibir datos del formulario a partir de la url get
        $input = $request->all();

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

        // concatenar todos los filtros en el where si llega a recibir algun valor, por defecto esta where 1=1 que siempre sera true es para evitar fallo en la consulta normal
        $ventas = DB::select("SELECT v.*, concat(c.clie_nombre, ' ', c.clie_apellido) as cliente, u.name as usuario, s.descripcion as sucursal
            FROM ventas v 
            JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN users u ON v.user_id = u.id
            JOIN sucursales s ON v.id_sucursal = s.id_sucursal
            WHERE 1=1 ".$filtro_cliente." ".$filtro_desde." ".$filtro_hasta."
            ORDER BY v.id_venta desc");

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

        // validar que exista la variable input['exportar']
        if(isset($input['exportar']) && $input['exportar'] == 'pdf'){
            // crear pdf con loadView y utilizar la misma vista reportes rpt_ventas para convertir en pdf
            $pdf = Pdf::loadView(
                'reportes.pdf_ventas',
                compact('ventas', 'array_detalles')
            )
            ->setPaper('a4', 'landscape');## especificar tamaño de hoja y disposición
            # de hoja landscape=horizontal, portrait=vertical

            // retornar la descarga del archivo
            return $pdf->download("ReporteVentas.pdf");
        }

        // consulta para llenar el select de ciudad en reporte cliente
        $clientes = DB::table('clientes')->selectRaw("concat(clie_nombre, ' ', clie_apellido) as cliente, id_cliente")->pluck('cliente', 'id_cliente');

        return view('reportes.rpt_ventas')->with('ventas', $ventas)->with('clientes', $clientes);
    }
}
