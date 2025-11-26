<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VentasExport implements FromCollection, WithHeadings
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
            $sql_filtros .= " AND v.fecha_venta BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($this->filtros['fecha_desde'])) {
            $fecha_desde = $this->filtros['fecha_desde'];
            $sql_filtros .= " AND v.fecha_venta >= '{$fecha_desde}'";
        } elseif (!empty($this->filtros['fecha_hasta'])) {
            $fecha_hasta = $this->filtros['fecha_hasta'];
            $sql_filtros .= " AND v.fecha_venta <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($this->filtros['id_cliente'])) {
            $sql_filtros .= " AND v.id_cliente = " . (int)$this->filtros['id_cliente'];
        }

        // FILTRO POR CONDICIÓN DE VENTA
        if (!empty($this->filtros['condicion_venta'])) {
            $sql_filtros .= " AND v.condicion_venta = '" . $this->filtros['condicion_venta'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($this->filtros['id_sucursal'])) {
            $sql_filtros .= " AND v.id_sucursal = " . (int)$this->filtros['id_sucursal'];
        }

        $sql_busqueda = 'WHERE 1=1';

        // Filtros de sucursales permitidas
        $sqlSucursales = '';
        if (!empty($this->idsPermitidos)) {
            $sqlSucursales = ' AND v.id_sucursal IN (' . implode(',', $this->idsPermitidos) . ')';
        }

        $ventas = DB::select(
            "SELECT v.id_venta, v.fecha_venta, concat(c.clie_nombre,' ', c.clie_apellido) as cliente,
            c.clie_ci, v.condicion_venta, v.total, v.estado, v.factura_nro, users.name as usuario,
            s.descripcion as sucursal
            FROM ventas v
                JOIN clientes c ON v.id_cliente = c.id_cliente
                JOIN users ON v.user_id = users.id
                JOIN sucursales s ON v.id_sucursal = s.id_sucursal
            {$sql_busqueda} {$sql_filtros} {$sqlSucursales}
            ORDER BY v.fecha_venta DESC"
        );

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $ventas = array_map(function($r) {
            return [
                $r->id_venta,
                $r->fecha_venta,
                $r->cliente,
                $r->clie_ci,
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
        return ['ID', 'Fecha', 'Cliente', 'CI', 'Condición', 'Total', 'Estado', 'Nro Factura', 'Sucursal', 'Usuario'];
    }
}
