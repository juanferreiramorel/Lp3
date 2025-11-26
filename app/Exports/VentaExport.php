<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VentaExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $cliente;
    protected $desde;
    protected $hasta;

    public function __construct($cliente, $desde, $hasta)
    {
        $this->cliente = $cliente;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection()
    {
        // definir variables para filtros
        $filtro_cliente = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if (!empty($this->cliente)) {
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_cliente = " AND v.id_cliente = " . $this->cliente;
        }

        // filtros desde y concatena el sql and
        if (!empty($this->desde)) {
            $filtro_desde = " AND v.fecha_venta >= '" . $this->desde . "'";
        }

        // filtros hasta y concatena el sql correspondientes
        if (!empty($this->hasta)) {
            $filtro_hasta = " AND v.fecha_venta <= '" . $this->hasta . "'";
        }

        // concatenar todos los filtros en el where
        $ventas = DB::select("SELECT v.id_venta, concat(c.clie_nombre, ' ', c.clie_apellido) as cliente,
            v.fecha_venta, v.condicion_venta, v.total, v.estado, v.factura_nro, s.descripcion as sucursal, u.name as usuario
            FROM ventas v
            JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN users u ON v.user_id = u.id
            JOIN sucursales s ON v.id_sucursal = s.id_sucursal
            WHERE 1=1 " . $filtro_cliente . " " . $filtro_desde . " " . $filtro_hasta . "
            ORDER BY v.id_venta desc");

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $ventas = array_map(function($r) {
            return [
                $r->id_venta,
                $r->cliente,
                $r->fecha_venta,
                $r->condicion_venta,
                $r->total,
                $r->estado,
                $r->factura_nro,
                $r->sucursal,
                $r->usuario,
            ];
        }, $ventas);

        // Convertir el array a Collection
        return collect($ventas);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Cliente', 'Fecha', 'Condición', 'Total', 'Estado', 'Nro Factura', 'Sucursal', 'Usuario'];
    }
}
