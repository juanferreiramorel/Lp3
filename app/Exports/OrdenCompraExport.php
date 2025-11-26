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
            $sql_filtros .= " AND oc.fecha_orden BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($this->filtros['fecha_desde'])) {
            $fecha_desde = $this->filtros['fecha_desde'];
            $sql_filtros .= " AND oc.fecha_orden >= '{$fecha_desde}'";
        } elseif (!empty($this->filtros['fecha_hasta'])) {
            $fecha_hasta = $this->filtros['fecha_hasta'];
            $sql_filtros .= " AND oc.fecha_orden <= '{$fecha_hasta}'";
        }

        // FILTRO POR PROVEEDOR
        if (!empty($this->filtros['id_proveedor'])) {
            $sql_filtros .= " AND oc.id_proveedor = " . (int)$this->filtros['id_proveedor'];
        }

        // FILTRO POR ESTADO
        if (!empty($this->filtros['estado'])) {
            $sql_filtros .= " AND oc.estado = '" . $this->filtros['estado'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($this->filtros['id_sucursal'])) {
            $sql_filtros .= " AND oc.id_sucursal = " . (int)$this->filtros['id_sucursal'];
        }

        // Query base
        $queryBase = "FROM ordenes_compra oc
                      JOIN proveedores p ON oc.id_proveedor = p.id_proveedor
                      LEFT JOIN users u ON oc.id_usuario = u.id
                      LEFT JOIN sucursales s ON oc.id_sucursal = s.id_sucursal";

        // Condiciones de búsqueda
        $where = "WHERE 1=1" . $sql_filtros;

        if (!empty($this->idsPermitidos)) {
            $where .= ' AND oc.id_sucursal IN (' . implode(',', $this->idsPermitidos) . ')';
        }

        // Obtener todos los registros
        $query = "SELECT oc.id_orden_compra, oc.fecha_orden, p.descripcion as proveedor,
                         oc.total_orden, oc.estado, oc.fecha_entrega_esperada,
                         s.descripcion as sucursal, u.name as usuario
                  {$queryBase} {$where}
                  ORDER BY oc.id_orden_compra DESC";

        $ordenesCompra = DB::select($query);

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $ordenesCompra = array_map(function($r) {
            return [
                $r->id_orden_compra,
                $r->fecha_orden,
                $r->proveedor,
                $r->total_orden,
                $r->estado,
                $r->fecha_entrega_esperada,
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
        return ['ID', 'Fecha Orden', 'Proveedor', 'Total', 'Estado', 'Fecha Entrega Esperada', 'Sucursal', 'Usuario'];
    }
}
