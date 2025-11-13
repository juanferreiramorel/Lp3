<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'clientes';

    /**
     * Clave primaria de la tabla
     */
    protected $primaryKey = 'id_cliente';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'clie_nombre',
        'clie_apellido',
        'clie_ci',
        'clie_telefono',
        'clie_direccion',
        'id_ciudad',
        'clie_fecha_nac',
        'id_departamento'
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos
     */
    protected $casts = [
        'clie_fecha_nac' => 'date',
    ];

    /**
     * Relación con contratos generados
     */
    public function contratosGenerados()
    {
        return $this->hasMany(ContratoGenerado::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Obtiene el nombre completo del cliente
     */
    public function getNombreCompletoAttribute()
    {
        return trim($this->clie_nombre . ' ' . $this->clie_apellido);
    }
}
