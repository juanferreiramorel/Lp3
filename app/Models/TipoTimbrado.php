<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTimbrado extends Model
{
    use HasFactory;

    protected $table = 'tipos_timbrados';
    protected $primaryKey = 'id_tipo_timbrado';

    protected $fillable = [
        'descripcion'
    ];

    // Constantes para uso fácil en código
    const FACTURA = 1;
    const NOTA_CREDITO = 2;
    const NOTA_DEBITO = 3;

    public function timbrados()
    {
        return $this->hasMany(Timbrado::class, 'id_tipo_timbrado', 'id_tipo_timbrado');
    }
}
