<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Timbrado extends Model
{
    use HasFactory;

    protected $table = 'timbrados';
    protected $primaryKey = 'id_timbrado';

    protected $fillable = [
        'id_empresa',
        'id_tipo_timbrado',
        'numero_timbrado',
        'fecha_inicio',
        'fecha_vencimiento',
        'rango_inicio',
        'rango_fin',
        'numero_actual',
        'establecimiento',
        'punto_expedicion',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'rango_inicio' => 'integer',
        'rango_fin' => 'integer',
        'numero_actual' => 'integer',
    ];

    /**
     * Relación con Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    /**
     * Relación con Tipo Timbrado
     */
    public function tipotimbrado()
    {
        return $this->belongsTo(TipoTimbrado::class, 'id_tipo_timbrado', 'id_tipo_timbrado');
    }

    /**
     * Relación con Ventas
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_timbrado', 'id_timbrado');
    }

    /**
     * Scope para timbrados vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where('estado', 'VIGENTE')
                    ->whereDate('fecha_vencimiento', '>=', Carbon::now())
                    ->whereRaw('numero_actual < rango_fin');
    }

    /**
     * Scope para timbrados por empresa
     */
    public function scopePorEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    /**
     * Scope para facturas
     */
    public function scopeFacturas($query)
    {
        return $query->where('id_tipo_timbrado', TipoTimbrado::FACTURA);
    }

    /**
     * Scope para notas de debito
     */
    public function scopeNotasDebito($query)
    {
        return $query->where('id_tipo_timbrado', TipoTimbrado::NOTA_DEBITO);
    }

    /**
     * Scope para notas de credito
     */
    public function scopeNotasCredito($query)
    {
        return $query->where('id_tipo_timbrado', TipoTimbrado::NOTA_CREDITO);
    }

    /**
     * Verifica si el timbrado está vigente
     */
    public function estaVigente()
    {
        return $this->estado === 'VIGENTE'
            && $this->fecha_vencimiento >= Carbon::now()->startOfDay()
            && $this->numero_actual < $this->rango_fin;
    }

    /**
     * Verifica si el timbrado está vencido
     */
    public function estaVencido()
    {
        return $this->fecha_vencimiento < Carbon::now()->startOfDay();
    }

    /**
     * Verifica si el timbrado está agotado
     */
    public function estaAgotado()
    {
        return $this->numero_actual >= $this->rango_fin;
    }

    /**
     * Obtiene el siguiente número disponible
     */
    public function obtenerSiguienteNumero()
    {
        if (!$this->estaVigente()) {
            return null;
        }

        $siguiente = $this->rango_inicio + $this->numero_actual;

        if ($siguiente > $this->rango_fin) {
            return null;
        }

        return $siguiente;
    }

    /**
     * Genera el número de factura completo con formato
     */
    public function generarNumeroFactura()
    {
        $siguiente = $this->obtenerSiguienteNumero();

        if (!$siguiente) {
            return null;
        }

        return sprintf(
            '%s-%s-%07d',
            $this->establecimiento,
            $this->punto_expedicion,
            $siguiente
        );
    }

    /**
     * Incrementa el contador de uso
     */
    public function incrementarUso()
    {
        $this->numero_actual++;

        // Actualizar estado si se agotó
        if ($this->estaAgotado()) {
            $this->estado = 'AGOTADO';
        }

        $this->save();

        return $this;
    }

    /**
     * Actualiza el estado del timbrado según condiciones
     */
    public function actualizarEstado()
    {
        if ($this->estaVencido()) {
            $this->estado = 'VENCIDO';
        } elseif ($this->estaAgotado()) {
            $this->estado = 'AGOTADO';
        }

        $this->save();

        return $this;
    }

    /**
     * Obtiene el porcentaje de uso
     */
    public function getPorcentajeUsoAttribute()
    {
        $total = $this->rango_fin - $this->rango_inicio + 1;
        return ($this->numero_actual / $total) * 100;
    }

    /**
     * Obtiene facturas disponibles
     */
    public function getFacturasDisponiblesAttribute()
    {
        return $this->rango_fin - ($this->rango_inicio + $this->numero_actual) + 1;
    }

    /**
     * Obtiene facturas usadas
     */
    public function getFacturasUsadasAttribute()
    {
        return $this->numero_actual;
    }

    /**
     * Obtiene el total de facturas del rango
     */
    public function getTotalFacturasAttribute()
    {
        return $this->rango_fin - $this->rango_inicio + 1;
    }

    /**
     * Obtiene días hasta el vencimiento
     */
    public function getDiasHastaVencimientoAttribute()
    {
        return Carbon::now()->startOfDay()->diffInDays($this->fecha_vencimiento, false);
    }

    /**
     * Verifica si está próximo a vencer (menos de 30 días)
     */
    public function proximoAVencer()
    {
        return $this->dias_hasta_vencimiento > 0 && $this->dias_hasta_vencimiento <= 30;
    }

    /**
     * Verifica si está próximo a agotarse (menos del 10%)
     */
    public function proximoAAgotarse()
    {
        return $this->facturas_disponibles > 0 && $this->porcentaje_uso >= 90;
    }
}
