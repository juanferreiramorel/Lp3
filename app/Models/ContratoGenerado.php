<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoGenerado extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'contratos_generados';

    /**
     * Clave primaria de la tabla
     */
    protected $primaryKey = 'id_contrato_generado';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'id_plantilla_contrato',
        'id_cliente',
        'contenido_generado',
        'observaciones',
        'user_id'
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos
     */
    protected $casts = [
        'fecha_generacion' => 'datetime',
    ];

    /**
     * Relación con la plantilla de contrato
     */
    public function plantilla()
    {
        return $this->belongsTo(PlantillaContrato::class, 'id_plantilla_contrato', 'id_plantilla_contrato');
    }

    /**
     * Relación con el cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Relación con el usuario que generó el contrato
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
