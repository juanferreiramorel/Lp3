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
    protected $filtros;
    protected $buscar;
    protected $idsPermitidos;

    public function __construct($filtros = [], $buscar = null, $idsPermitidos = [])
    {
        $this->filtros = $filtros;
        $this->buscar = $buscar;
        $this->idsPermitidos = $idsPermitidos;
    }

    public function collection()
    {
        $sql_filtros = '';

        // FILTRO POR RANGO DE FECHAS
        if (!empty($this->filtros['fecha_desde']) && !empty($this->filtros['fecha_hasta'])) {
            $fecha_desde = $this->filtros['fecha_desde'];
            $fecha_hasta = $this->filtros['fecha_hasta'];
            $sql_filtros .= " AND c.fecha_compra BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($this->filtros['fecha_desde'])) {
            $fecha_desde = $this->filtros['fecha_desde'];
            $sql_filtros .= " AND c.fecha_compra >= '{$fecha_desde}'";
        } elseif (!empty($this->filtros['fecha_hasta'])) {
            $fecha_hasta = $this->filtros['fecha_hasta'];
            $sql_filtros .= " AND c.fecha_compra <= '{$fecha_hasta}'";
        }

        // FILTRO POR PROVEEDOR
        if (!empty($this->filtros['id_proveedor'])) {
            $sql_filtros .= " AND c.id_proveedor = " . (int)$this->filtros['id_proveedor'];
        }

        // FILTRO POR CONDICIÓN DE COMPRA
        if (!empty($this->filtros['condicion_compra'])) {
            $sql_filtros .= " AND c.condicion_compra = '" . $this->filtros['condicion_compra'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($this->filtros['id_sucursal'])) {
            $sql_filtros .= " AND c.id_sucursal = " . (int)$this->filtros['id_sucursal'];
        }

        $sql_busqueda = 'WHERE 1=1';

        // Filtros de sucursales permitidas
        $sqlSucursalesPermitidas = '';
        if (!empty($this->idsPermitidos)) {
            $sqlSucursalesPermitidas = ' AND c.id_sucursal IN (' . implode(',', $this->idsPermitidos) . ')';
        }

        $compras = DB::select(
            "SELECT c.id_compra, c.fecha_compra, p.descripcion as proveedor,
            c.condicion_compra, c.total, c.estado, c.factura, s.descripcion as sucursal, u.name as usuario
            FROM compras c
                JOIN proveedores p ON c.id_proveedor = p.id_proveedor
                JOIN users u ON c.user_id = u.id
                JOIN sucursales s ON c.id_sucursal = s.id_sucursal
            {$sql_busqueda} {$sql_filtros} {$sqlSucursalesPermitidas}
            ORDER BY c.fecha_compra DESC"
        );

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
