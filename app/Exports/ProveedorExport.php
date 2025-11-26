<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProveedorExport implements FromCollection, WithHeadings
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
        // verificar si los filtros no estan vacios para realizar el where en la consulta proveedores
        if (!empty($this->desde) and !empty($this->hasta)) {
            $proveedores = DB::select('SELECT * FROM proveedores WHERE id_proveedor
            BETWEEN ? AND ? ORDER BY id_proveedor', [$this->desde, $this->hasta]);
        } else {
            // si no se reciben datos, se traen todos los registros
            $proveedores = DB::select('SELECT * FROM proveedores ORDER BY id_proveedor');
        }

        // Asegurar que cada fila sea un array indexado en el mismo orden que los headings
        $proveedores = array_map(function($r) {
            return [
                $r->id_proveedor,
                $r->descripcion,
                $r->direccion,
                $r->telefono,
            ];
        }, $proveedores);

        // Convertir el array a Collection
        return collect($proveedores);
    }

    /**
     * Define los encabezados de las columnas.
     */
    public function headings(): array
    {
        return ['ID', 'Descripción', 'Dirección', 'Teléfono'];
    }
}
