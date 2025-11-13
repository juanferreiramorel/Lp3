<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlantillaContrato extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'plantillas_contratos';

    /**
     * Clave primaria de la tabla
     */
    protected $primaryKey = 'id_plantilla_contrato';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'nombre_plantilla',
        'descripcion',
        'contenido_plantilla',
        'activo',
        'user_id'
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos
     */
    protected $casts = [
        'activo' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    /**
     * Relación con el usuario que creó la plantilla
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con contratos generados
     */
    public function contratosGenerados()
    {
        return $this->hasMany(ContratoGenerado::class, 'id_plantilla_contrato', 'id_plantilla_contrato');
    }

    /**
     * Reemplaza las palabras clave de la plantilla con los datos del cliente
     *
     * @param int $idCliente ID del cliente
     * @param array $datosAdicionales Datos adicionales opcionales para reemplazar
     * @return string Contenido de la plantilla con las palabras clave reemplazadas
     */
    public function generarContrato($idCliente, $datosAdicionales = [])
    {
        // Obtener datos del cliente con ciudad y departamento
        $cliente = DB::selectOne("
            SELECT
                c.id_cliente,
                c.clie_nombre,
                c.clie_apellido,
                c.clie_ci,
                c.clie_telefono,
                c.clie_direccion,
                c.clie_fecha_nac,
                ciu.descripcion as ciudad_nombre,
                dep.descripcion as departamento_nombre
            FROM clientes c
            LEFT JOIN ciudades ciu ON c.id_ciudad = ciu.id_ciudad
            LEFT JOIN departamentos dep ON c.id_departamento = dep.id_departamento
            WHERE c.id_cliente = ?
        ", [$idCliente]);

        if (!$cliente) {
            throw new \Exception('Cliente no encontrado');
        }

        // Calcular la edad del cliente
        $edad = null;
        if ($cliente->clie_fecha_nac) {
            $fechaNacimiento = Carbon::parse($cliente->clie_fecha_nac);
            $edad = $fechaNacimiento->age;
        }

        // Mapeo de palabras clave a valores
        $reemplazos = [
            '--nombre_cliente--' => $cliente->clie_nombre ?? '',
            '--apellido_cliente--' => $cliente->clie_apellido ?? '',
            '--nombre_completo--' => trim(($cliente->clie_nombre ?? '') . ' ' . ($cliente->clie_apellido ?? '')),
            '--ci_cliente--' => $cliente->clie_ci ?? '',
            '--telefono_cliente--' => $cliente->clie_telefono ?? '',
            '--direccion_cliente--' => $cliente->clie_direccion ?? '',
            '--ciudad_cliente--' => $cliente->ciudad_nombre ?? '',
            '--departamento_cliente--' => $cliente->departamento_nombre ?? '',
            '--fecha_nacimiento--' => $cliente->clie_fecha_nac ? Carbon::parse($cliente->clie_fecha_nac)->format('d/m/Y') : '',
            '--edad_cliente--' => $edad ?? '',
            '--fecha_actual--' => Carbon::now()->format('d/m/Y'),
            '--fecha_contrato--' => isset($datosAdicionales['fecha_contrato']) ?
                Carbon::parse($datosAdicionales['fecha_contrato'])->format('d/m/Y') :
                Carbon::now()->format('d/m/Y'),
            '--usuario_sistema--' => auth()->user()->name ?? '',
        ];

        // Agregar datos adicionales al array de reemplazos
        foreach ($datosAdicionales as $clave => $valor) {
            if (!isset($reemplazos["--{$clave}--"])) {
                $reemplazos["--{$clave}--"] = $valor;
            }
        }

        // Reemplazar todas las palabras clave en el contenido
        $contenidoGenerado = $this->contenido_plantilla;
        foreach ($reemplazos as $palabraClave => $valor) {
            $contenidoGenerado = str_replace($palabraClave, $valor, $contenidoGenerado);
        }

        return $contenidoGenerado;
    }

    /**
     * Obtiene la lista de palabras clave disponibles
     *
     * @return array Lista de palabras clave con sus descripciones
     */
    public static function getPalabrasClave()
    {
        return [
            'Datos del Cliente' => [
                '--nombre_cliente--' => 'Nombre del cliente',
                '--apellido_cliente--' => 'Apellido del cliente',
                '--nombre_completo--' => 'Nombre completo (nombre + apellido)',
                '--ci_cliente--' => 'Número de documento (CI)',
                '--telefono_cliente--' => 'Teléfono del cliente',
                '--direccion_cliente--' => 'Dirección del cliente',
                '--ciudad_cliente--' => 'Nombre de la ciudad',
                '--departamento_cliente--' => 'Nombre del departamento',
                '--fecha_nacimiento--' => 'Fecha de nacimiento (formato dd/mm/yyyy)',
                '--edad_cliente--' => 'Edad del cliente en años',
            ],
            'Datos del Sistema' => [
                '--fecha_actual--' => 'Fecha actual del sistema (formato dd/mm/yyyy)',
                '--fecha_contrato--' => 'Fecha del contrato (por defecto fecha actual)',
                '--usuario_sistema--' => 'Nombre del usuario que genera el contrato',
            ],
        ];
    }

    /**
     * Scope para obtener solo plantillas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por fecha de modificación descendente
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_modificacion', 'desc');
    }
}
