<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleNotaDebito extends Model
{
    use HasFactory;

    protected $table = 'detalle_notas_debito';
    protected $primaryKey = 'id_detalle_nota';
    public $timestamps = false;

    protected $fillable = [
        'id_nota_debito',
        'id_producto',
        'cantidad',
        'precio',
        'id_tax',
        'precio_sin_impuesto',
        'monto_impuesto',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_sin_impuesto' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
    ];

    public function notaDebito()
    {
        return $this->belongsTo(NotaDebito::class, 'id_nota_debito', 'id_nota_debito');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'id_producto', 'id_producto');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
    }
}
