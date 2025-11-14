<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'taxes';
    protected $primaryKey = 'id_tax';

    protected $fillable = [
        'nombre',
        'descripcion',
        'porcentaje',
        'activo'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean'
    ];

    /**
     * Relación many-to-many con productos
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_tax',
            'id_tax',
            'id_producto'
        );
    }

    /**
     * Scope para obtener solo impuestos activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
