<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMetodoPago extends Model
{
    use HasFactory;

    protected $table = 'tipo_metodo_pagos';
    protected $primaryKey = 'id_tipo_metodo_pago';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'id_transaccion_entrada',
        'id_transaccion_salida',
        'estado'
    ];
}
