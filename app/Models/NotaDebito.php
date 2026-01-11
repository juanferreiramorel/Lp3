<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Timbrado;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleNotaDebito;
use App\Models\Cobro;

class NotaDebito extends Model
{
    use HasFactory;

    protected $table = 'notas_debito';
    protected $primaryKey = 'id_nota_debito';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'id_sucursal',
        'id_timbrado',
        'fecha_emision',
        'nro_nota',
        'total',
        'total_exento',
        'total_gravado_5',
        'total_gravado_10',
        'total_iva_5',
        'total_iva_10',
        'total_iva',
        'estado',
        'user_id',
        'motivo',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'total' => 'decimal:2',
        'total_exento' => 'decimal:2',
        'total_gravado_5' => 'decimal:2',
        'total_gravado_10' => 'decimal:2',
        'total_iva_5' => 'decimal:2',
        'total_iva_10' => 'decimal:2',
        'total_iva' => 'decimal:2',
    ];

    /**
     * Relación con Timbrado
     */
    public function timbrado()
    {
        return $this->belongsTo(Timbrado::class, 'id_timbrado', 'id_timbrado');
    }

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    /**
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con Venta Original
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }

    /**
     * Relación con Detalle
     */
    public function detalles()
    {
        return $this->hasMany(DetalleNotaDebito::class, 'id_nota_debito', 'id_nota_debito');
    }

    /**
     * Relación con Cobros
     */
    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'id_nota_debito', 'id_nota_debito');
    }
}
