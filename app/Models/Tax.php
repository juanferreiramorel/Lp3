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
     * Relación con productos (un impuesto puede estar en múltiples productos)
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'id_tax', 'id_tax');
    }

    /**
     * Scope para obtener solo impuestos activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Accessor para obtener el nombre con el porcentaje
     */
    public function getNombreConPorcentajeAttribute()
    {
        return $this->nombre . ' (' . number_format($this->porcentaje, 2) . '%)';
    }
}
