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
    protected $cliente;
    protected $estado;
    protected $desde;
    protected $hasta;

    public function __construct($cliente, $estado, $desde, $hasta)
    {
        $this->cliente = $cliente;
        $this->estado = $estado;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection()
    {
        // definir variables para filtros
        $filtro_cliente = "";
        $filtro_estado = "";
        $filtro_desde = "";
        $filtro_hasta = "";

        if (!empty($this->cliente)) {
            $filtro_cliente = " AND p.id_cliente = " . $this->cliente;
        }

        if (!empty($this->estado)) {
            $filtro_estado = " AND p.estado = '" . $this->estado . "'";
        }

        // filtros desde y concatena el sql and
        if (!empty($this->desde)) {
            $filtro_desde = " AND p.fecha_pedido >= '" . $this->desde . "'";
        }

        // filtros hasta y concatena el sql correspondientes
        if (!empty($this->hasta)) {
            $filtro_hasta = " AND p.fecha_pedido <= '" . $this->hasta . "'";
        }

        // concatenar todos los filtros en el where
        $pedidos = DB::select("SELECT p.id_pedido, p.fecha_pedido, CONCAT(c.clie_nombre, ' ', c.clie_apellido) as cliente,
            c.clie_ci, p.total_pedido, p.estado, s.descripcion as sucursal, u.name as usuario
            FROM pedidos p
            JOIN clientes c ON p.id_cliente = c.id_cliente
            LEFT JOIN users u ON p.id_usuario = u.id
            LEFT JOIN sucursales s ON p.id_sucursal = s.id_sucursal
            WHERE 1=1 " . $filtro_cliente . " " . $filtro_estado . " " . $filtro_desde . " " . $filtro_hasta . "
            ORDER BY p.id_pedido desc");

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
