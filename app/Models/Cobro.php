<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\NotaDebito;
use App\Models\Venta;
use App\Models\MetodoPago;
use App\Models\CuentaACobrar;

class Cobro extends Model
{
    use HasFactory;

    protected $table = 'cobros';
    protected $primaryKey = 'id_cobro';
    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'id_nota_debito',
        'user_id',
        'id_metodo_pago',
        'cobro_fecha',
        'cobro_importe',
        'cobro_estado',
        'nro_voucher',
        'fechahora_anulado',
        'id_useranulado',
        'id_cta',
        'id_apertura',
    ];

    public function notaCredito()
    {
        return $this->belongsTo(NotaCredito::class, 'id_nota_credito', 'id_nota_credito');
    }

    public function notaDebito()
    {
        return $this->belongsTo(NotaDebito::class, 'id_nota_debito', 'id_nota_debito');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    public function cuentaACobrar()
    {
        return $this->belongsTo(CuentaACobrar::class, 'id_cta', 'id_cta');
    }
}
