<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PedidosExport implements FromCollection, WithHeadings
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
            $sql_filtros .= " AND p.fecha_pedido BETWEEN '{$fecha_desde}' AND '{$fecha_hasta}'";
        } elseif (!empty($this->filtros['fecha_desde'])) {
            $fecha_desde = $this->filtros['fecha_desde'];
            $sql_filtros .= " AND p.fecha_pedido >= '{$fecha_desde}'";
        } elseif (!empty($this->filtros['fecha_hasta'])) {
            $fecha_hasta = $this->filtros['fecha_hasta'];
            $sql_filtros .= " AND p.fecha_pedido <= '{$fecha_hasta}'";
        }

        // FILTRO POR CLIENTE
        if (!empty($this->filtros['id_cliente'])) {
            $sql_filtros .= " AND p.id_cliente = " . (int)$this->filtros['id_cliente'];
        }

        // FILTRO POR ESTADO
        if (!empty($this->filtros['estado'])) {
            $sql_filtros .= " AND p.estado = '" . $this->filtros['estado'] . "'";
        }

        // FILTRO POR SUCURSAL
        if (!empty($this->filtros['id_sucursal'])) {
            $sql_filtros .= " AND p.id_sucursal = " . (int)$this->filtros['id_sucursal'];
        }

        // Construir WHERE
        $where = "WHERE 1=1" . $sql_filtros;

        if (!empty($this->idsPermitidos)) {
            $where .= ' AND p.id_sucursal IN (' . implode(',', $this->idsPermitidos) . ')';
        }

        // Query base
        $queryBase = "FROM pedidos p
                      JOIN clientes c ON p.id_cliente = c.id_cliente
                      JOIN users u ON p.id_usuario = u.id
                      JOIN sucursales s ON p.id_sucursal = s.id_sucursal";

        // Obtener todos los registros
        $query = "SELECT p.id_pedido, p.fecha_pedido, CONCAT(c.clie_nombre,' ', c.clie_apellido) AS cliente,
                         c.clie_ci, p.total_pedido, p.estado, s.descripcion as sucursal, u.name AS usuario
                  {$queryBase} {$where}
                  ORDER BY p.fecha_pedido DESC";

        $pedidos = DB::select($query);

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $pedidos = array_map(function($r) {
            return [
                $r->id_pedido,
                $r->fecha_pedido,
                $r->cliente,
                $r->clie_ci,
                $r->total_pedido,
                $r->estado,
                $r->sucursal,
                $r->usuario,
            ];
        }, $pedidos);

        // Convertir el array a Collection
        return collect($pedidos);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Fecha', 'Cliente', 'CI', 'Total', 'Estado', 'Sucursal', 'Usuario'];
    }
}
