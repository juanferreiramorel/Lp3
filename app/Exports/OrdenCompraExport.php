<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdenCompraExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $proveedor;
    protected $estado;
    protected $desde;
    protected $hasta;

    public function __construct($proveedor, $estado, $desde, $hasta)
    {
        $this->proveedor = $proveedor;
        $this->estado = $estado;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection()
    {
        // definir variables para filtros
        $filtro_proveedor = "";
        $filtro_estado = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if (!empty($this->proveedor)) {
            $filtro_proveedor = " AND oc.id_proveedor = " . $this->proveedor;
        }

        if (!empty($this->estado)) {
            $filtro_estado = " AND oc.estado = '" . $this->estado . "'";
        }

        // filtros desde y concatena el sql and
        if (!empty($this->desde)) {
            $filtro_desde = " AND oc.fecha_orden >= '" . $this->desde . "'";
        }

        // filtros hasta y concatena el sql correspondientes
        if (!empty($this->hasta)) {
            $filtro_hasta = " AND oc.fecha_orden <= '" . $this->hasta . "'";
        }

        // concatenar todos los filtros en el where
        $ordenesCompra = DB::select("SELECT oc.id_orden_compra, oc.fecha_orden, p.descripcion as proveedor,
            oc.total, oc.estado, s.descripcion as sucursal, u.name as usuario
            FROM ordenes_compra oc
            JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
            LEFT JOIN users u ON oc.id_usuario = u.id
            LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal
            WHERE 1=1 " . $filtro_proveedor . " " . $filtro_estado . " " . $filtro_desde . " " . $filtro_hasta . "
            ORDER BY oc.id_orden_compra desc");

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $ordenesCompra = array_map(function($r) {
            return [
                $r->id_orden_compra,
                $r->fecha_orden,
                $r->proveedor,
                $r->total,
                $r->estado,
                $r->sucursal,
                $r->usuario,
            ];
        }, $ordenesCompra);

        // Convertir el array a Collection
        return collect($ordenesCompra);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Fecha Orden', 'Proveedor', 'Total', 'Estado', 'Sucursal', 'Usuario'];
    }
}
