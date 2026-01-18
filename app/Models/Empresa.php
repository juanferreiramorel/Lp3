<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $primaryKey = 'id_empresa';

    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'logo',
        'estado',
    ];

    /**
     * Scope a query to only include active companies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

    /**
     * Relación con Timbrados
     */
    public function timbrados()
    {
        return $this->hasMany(Timbrado::class, 'id_empresa', 'id_empresa');
    }

    /**
     * Obtiene timbrados vigentes de la empresa
     */
    public function timbradosVigentes()
    {
        return $this->timbrados()->vigentes();
    }

    /**
     * Obtiene el timbrado vigente más reciente
     */
    public function timbradoVigente()
    {
        return $this->timbrados()
                    ->vigentes()
                    ->orderBy('fecha_vencimiento', 'desc')
                    ->first();
    }

    /**
     * Verifica si tiene al menos un timbrado vigente
     */
    public function tieneTimbradoVigente()
    {
        return $this->timbradoVigente() !== null;
    }
}
