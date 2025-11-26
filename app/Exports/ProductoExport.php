<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductoExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $desde;
    protected $hasta;

    public function __construct($desde, $hasta)
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection()
    {
        // Construir la consulta con join para mostrar la descripción de la marca
        $baseQuery = 'SELECT p.id_producto, p.descripcion, p.precio, p.tipo_iva, m.descripcion AS marca
                 FROM productos p
                 LEFT JOIN marcas m ON p.id_marca = m.id_marca';

        // verificar si los filtros no estan vacios para realizar el where
        if (!empty($this->desde) and !empty($this->hasta)) {
            $productos = DB::select($baseQuery . ' WHERE p.id_producto BETWEEN ? AND ? ORDER BY p.id_producto', [$this->desde, $this->hasta]);
        } else {
            // si no se reciben datos, se traen todos los registros
            $productos = DB::select($baseQuery . ' ORDER BY p.id_producto');
        }

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $productos = array_map(function($r) {
            return [
                $r->id_producto,
                $r->descripcion,
                $r->precio,
                $r->tipo_iva,
                $r->marca,
            ];
        }, $productos);

        // Convertir el array a Collection
        return collect($productos);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Descripción', 'Precio', 'IVA', 'Marca'];
    }
}
