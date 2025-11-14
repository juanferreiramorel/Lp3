<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';

    protected $fillable = [
        'factura_nro',
        'numero_factura_timbrado',
        'id_apertura',
        'id_cliente',
        'id_sucursal',
        'id_timbrado',
        'total',
        'fecha_venta',
        'condicion_venta',
        'intervalo',
        'cantidad_cuota',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'total' => 'decimal:2',
        'intervalo' => 'integer',
        'cantidad_cuota' => 'integer',
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
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Verifica si la venta tiene timbrado
     */
    public function tieneTimbrado()
    {
        return $this->id_timbrado !== null;
    }

    /**
     * Obtiene el número de factura para mostrar
     */
    public function getNumeroFacturaDisplayAttribute()
    {
        return $this->numero_factura_timbrado ?? $this->factura_nro;
    }
}
