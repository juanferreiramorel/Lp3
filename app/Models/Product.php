<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';

    protected $fillable = [
        'descripcion',
        'precio',
        'id_marca',
        'id_tax',
        'imagen_producto',
        'controlar_stock'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'controlar_stock' => 'boolean'
    ];

    /**
     * Relación con impuesto (un producto tiene un solo impuesto)
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
    }

    /**
     * Relación con marca
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }

    /**
     * Calcula el precio sin impuesto (precio base)
     * El precio almacenado incluye IVA, según sistema paraguayo
     * Fórmula: Precio sin IVA = Precio / (1 + porcentaje/100)
     *
     * @return float
     */
    public function getPrecioSinImpuesto()
    {
        if (!$this->tax || $this->tax->porcentaje == 0) {
            return $this->precio;
        }

        return $this->precio / (1 + ($this->tax->porcentaje / 100));
    }

    /**
     * Calcula el monto del impuesto
     * Fórmula: Monto IVA = Precio - Precio sin IVA
     *
     * @return float
     */
    public function getMontoImpuesto()
    {
        return $this->precio - $this->getPrecioSinImpuesto();
    }

    /**
     * Obtiene el nombre del impuesto aplicado
     *
     * @return string
     */
    public function getNombreImpuesto()
    {
        return $this->tax ? $this->tax->nombre : 'Sin impuesto';
    }
}
