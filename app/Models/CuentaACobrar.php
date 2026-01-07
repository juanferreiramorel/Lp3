<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaACobrar extends Model
{
    use HasFactory;

    protected $table = 'cuentas_a_cobrar';
    protected $primaryKey = 'id_cta';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_venta',
        'id_nota_debito',
        'importe',
        'saldo',
        'estado',
        'fecha_vencimiento',
        'created_at',
        'updated_at'
    ];

    public function notaDebito()
    {
        return $this->belongsTo(NotaDebito::class, 'id_nota_debito', 'id_nota_debito');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }
}
