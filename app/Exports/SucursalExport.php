<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SucursalExport implements FromCollection, WithHeadings
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
        // construir la consulta con join para mostrar la descripción de la ciudad
        $baseQuery = 'SELECT s.id_sucursal, s.descripcion, s.direccion, s.telefono, c.descripcion AS ciudad
                 FROM sucursales s
                 INNER JOIN ciudades c ON s.id_ciudad = c.id_ciudad';

        // verificar si los filtros no estan vacios para realizar el where
        if (!empty($this->desde) and !empty($this->hasta)) {
            $sucursales = DB::select($baseQuery . ' WHERE s.id_sucursal BETWEEN ? AND ? ORDER BY s.id_sucursal', [$this->desde, $this->hasta]);
        } else {
            // si no se reciben datos, se traen todos los registros
            $sucursales = DB::select($baseQuery . ' ORDER BY s.id_sucursal');
        }

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $sucursales = array_map(function($r) {
            return [
                $r->id_sucursal,
                $r->descripcion,
                $r->direccion,
                $r->telefono,
                $r->ciudad,
            ];
        }, $sucursales);

        // Convertir el array a Collection
        return collect($sucursales);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Descripción', 'Dirección', 'Teléfono', 'Ciudad'];
    }
}
