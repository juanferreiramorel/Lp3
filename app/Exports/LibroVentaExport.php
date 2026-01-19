<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LibroVentaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $desde;
    protected $hasta;
    protected $cliente;
    protected $sucursal;
    protected $search;

    public function __construct($desde, $hasta, $cliente = null, $sucursal = null, $search = null)
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->cliente = $cliente;
        $this->sucursal = $sucursal;
        $this->search = $search;
    }

    public function collection()
    {
        // filtros
        $filtro_desde = "";
        $filtro_hasta = "";
        $filtro_cliente = "";
        $filtro_sucursal = "";
        $filtro_search = "";

        if(!empty($this->desde)){
            $filtro_desde = " AND v.fecha_venta >= '".$this->desde."'";
        }

        if(!empty($this->hasta)){
            $filtro_hasta = " AND v.fecha_venta <= '".$this->hasta."'";
        }

        if(!empty($this->cliente)){
            $filtro_cliente = " AND v.id_cliente = ".$this->cliente;
        }

        if(!empty($this->sucursal)){
            $filtro_sucursal = " AND v.id_sucursal = ".$this->sucursal;
        }

        if(!empty($this->search)){
            // Buscar por Nro Factura o Nombre Cliente / RUC
            // Asumiendo que $this->search es seguro o se debe escapar variables si fuera raw SQL puro sin parameters
            // Para simplicidad en este contexto:
            $term = $this->search;
            $filtro_search = " AND (v.factura_nro LIKE '%$term%' OR c.clie_nombre LIKE '%$term%' OR c.clie_apellido LIKE '%$term%' OR c.clie_ci LIKE '%$term%')";
        }

        $whereClause = "WHERE 1=1 ".$filtro_desde." ".$filtro_hasta." ".$filtro_cliente." ".$filtro_sucursal." ".$filtro_search;

        $query = "SELECT 
                to_char(v.fecha_venta, 'DD/MM/YYYY') as fecha,
                v.factura_nro,
                c.clie_ci as ruc,
                concat(c.clie_nombre, ' ', c.clie_apellido) as cliente,
                v.total_exento,
                v.total_gravado_5,
                v.total_gravado_10,
                v.total_iva_5,
                v.total_iva_10,
                v.total
            FROM ventas v 
            JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN sucursales s ON v.id_sucursal = s.id_sucursal
            {$whereClause}
            ORDER BY v.fecha_venta ASC, v.factura_nro ASC";

        $ventas = DB::select($query);

        return collect($ventas);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Nro Factura',
            'RUC/CI',
            'Cliente',
            'Exenta',
            'Gravada 5%',
            'Gravada 10%',
            'IVA 5%',
            'IVA 10%',
            'Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
