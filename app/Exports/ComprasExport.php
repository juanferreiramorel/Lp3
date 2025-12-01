<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComprasExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $proveedor;
    protected $desde;
    protected $hasta;

    public function __construct($proveedor, $desde, $hasta)
    {
        $this->proveedor = $proveedor;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection()
    {
        // definir variables para filtros
        $filtro_proveedor = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if (!empty($this->proveedor)) {
            // concatenar los filtros y siempre dejar un espacio en blanco al comienzo del string " "
            $filtro_proveedor = " AND c.id_proveedor = " . $this->proveedor;
        }

        // filtros desde y concatena el sql and
        if (!empty($this->desde)) {
            $filtro_desde = " AND c.fecha_compra >= '" . $this->desde . "'";
        }

        // filtros hasta y concatena el sql correspondientes
        if (!empty($this->hasta)) {
            $filtro_hasta = " AND c.fecha_compra <= '" . $this->hasta . "'";
        }

        // concatenar todos los filtros en el where
        $compras = DB::select("SELECT c.id_compra, c.fecha_compra, p.descripcion as proveedor,
            c.condicion_compra, c.total, c.estado, c.factura, s.descripcion as sucursal, u.name as usuario
            FROM compras c
            JOIN proveedores p ON c.id_proveedor = p.id_proveedor
            JOIN users u ON c.user_id = u.id
            JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            WHERE 1=1 " . $filtro_proveedor . " " . $filtro_desde . " " . $filtro_hasta . "
            ORDER BY c.id_compra desc");

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $compras = array_map(function($r) {
            return [
                $r->id_compra,
                $r->fecha_compra,
                $r->proveedor,
                $r->condicion_compra,
                $r->total,
                $r->estado,
                $r->factura,
                $r->sucursal,
                $r->usuario,
            ];
        }, $compras);

        // Convertir el array a Collection
        return collect($compras);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Fecha', 'Proveedor', 'Condición', 'Total', 'Estado', 'Factura', 'Sucursal', 'Usuario'];
    }
}
