<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleNotaCredito extends Model
{
    use HasFactory;

    protected $table = 'detalle_notas_credito';
    protected $primaryKey = 'id_detalle_nota_credito';
    public $timestamps = false;

    protected $fillable = [
        'id_nota_credito',
        'id_producto',
        'cantidad',
        'precio',
        'id_tax',
        'precio_sin_impuesto',
        'monto_impuesto'
    ];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'id_producto', 'id_producto');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
    }
}
