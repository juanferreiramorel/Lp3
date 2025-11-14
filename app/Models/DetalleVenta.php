<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';
    protected $primaryKey = 'id_detalle_venta';

    protected $fillable = [
        'id_venta',
        'id_producto',
        'cantidad',
        'precio',
        'id_tax',
        'precio_sin_impuesto',
        'monto_impuesto',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio' => 'decimal:2',
        'precio_sin_impuesto' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
    ];

    /**
     * Relación con Venta
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    /**
     * Relación con Producto
     */
    public function producto()
    {
        return $this->belongsTo(Product::class, 'id_producto', 'id_producto');
    }

    /**
     * Relación con Tax (Impuesto)
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
    }

    /**
     * Calcula el subtotal del item (precio * cantidad)
     */
    public function getSubtotalAttribute()
    {
        return $this->precio * $this->cantidad;
    }

    /**
     * Calcula el subtotal sin impuesto (precio_sin_impuesto * cantidad)
     */
    public function getSubtotalSinImpuestoAttribute()
    {
        return $this->precio_sin_impuesto * $this->cantidad;
    }

    /**
     * Calcula el monto total del impuesto (monto_impuesto * cantidad)
     */
    public function getMontoImpuestoTotalAttribute()
    {
        return $this->monto_impuesto * $this->cantidad;
    }
}
