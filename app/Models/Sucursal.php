<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $primaryKey = 'id_sucursal';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'direccion',
        'telefono',
        'id_ciudad',
        'id_empresa',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }
}
