<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodo_pagos';
    protected $primaryKey = 'id_metodo_pago';
    public $timestamps = false;

    protected $fillable = [
        'descripcion'
    ];
}
