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
        'imagen_producto'
    ];

    protected $casts = [
        'precio' => 'decimal:2'
    ];

    /**
     * Relación many-to-many con impuestos
     */
    public function taxes()
    {
        return $this->belongsToMany(
            Tax::class,
            'product_tax',
            'id_producto',
            'id_tax'
        );
    }

    /**
     * Relación con marca
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }
}
