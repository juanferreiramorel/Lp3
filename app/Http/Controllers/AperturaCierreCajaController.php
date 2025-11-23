<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AperturaCierreCajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:aperturacierrecaja index')->only(['index']);
        $this->middleware('permission:aperturacierrecaja create')->only(['create', 'store']);
        $this->middleware('permission:aperturacierrecaja edit')->only(['edit', 'update', 'cerrar_caja', 'editCierre']);
        $this->middleware('permission:aperturacierrecaja destroy')->only(['destroy']);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // normalizar monto de apertura quitando separadores de miles y usando punto como decimal
        $input['monto_apertura'] = $this->normalizarMonto($input['monto_apertura']);

        // validar datos recibidos
        $validacion = Validator::make($input, [
            'id_caja' => 'required|exists:cajas,id_caja',
            'monto_apertura' => 'numeric|min:0',
            'fecha_apertura' => 'required|date',
        ], [
            'id_caja.required' => 'El campo caja es obligatorio',
            'id_caja.exists' => 'El campo caja seleccionado no existe',
            'monto_apertura.numeric' => 'El campo monto apertura debe ser un número',
            'monto_apertura.min' => 'El campo monto apertura debe ser un número positivo',
            'fecha_apertura.required' => 'El campo fecha apertura es obligatorio',
            'fecha_apertura.date' => 'El campo fecha apertura debe ser una fecha válida',
        ]);

        if ($validacion->fails()) {
            return redirect()->route('ventas.index')
                ->withErrors($validacion)
                ->withInput();
        }

        // insertar datos en la tabla apertura_cierre_cajas
        DB::insert('INSERT INTO apertura_cierre_cajas (id_caja, monto_apertura, monto_cierre, fecha_apertura, user_id, estado)
            VALUES (?, ?, ?, ?, ?, ?)', [
            $input['id_caja'],
            $input['monto_apertura'] ?? 0, //validar si no posee monto apertura colocar 0
            0, // monto cierre 0 al abrir caja
            $input['fecha_apertura'],
            auth()->user()->id,
            'ABIERTA'
        ]);

        // redireccionar a la vista ventas con mensaje de exito
        Alert::toast('Caja abierta con éxito', 'success');
        return redirect()->route('ventas.index');
    }

    public function cerrar_caja(Request $request, $id_apertura)
    {
        $input = $request->all();

        // normalizar monto cierre eliminando separadores de miles y usando punto como decimal
        $input['monto_cierre'] = $this->normalizarMonto($input['monto_cierre']);

        // validar que no exista montos sin cobrar en las ventas
        $ventas_sin_cobrar = DB::selectOne(
            'SELECT COUNT(*) AS no_cobrados 
            FROM ventas 
            WHERE id_apertura = ? AND estado = ?',
            [
                $id_apertura,
                'PENDIENTE'
            ]
        );
        // si existen ventas sin cobrar no se puede cerrar caja
        if ($ventas_sin_cobrar->no_cobrados > 0) {
            Alert::error('Error', 'No se puede cerrar la caja, existen ventas sin cobrar');
            return redirect()->route('ventas.index');
        }
                
        // si no realizar update de apertura_cierre_cajas
        DB::update('UPDATE apertura_cierre_cajas 
            SET monto_cierre = ?, 
                estado = ? 
            WHERE id_apertura = ?', [
            $input['monto_cierre'],
            'CERRADA',
            $id_apertura
        ]);

        // faltaria el pdf arqueo de caja segun lo recaudado en las diferentes formas de pagos. 
        $cierre_caja = DB::selectOne(
            'SELECT ape.*, s.descripcion as sucursal, c.descripcion as caja, u.name as usuario
                    FROM apertura_cierre_cajas ape
                JOIN cajas c ON c.id_caja = ape.id_caja
                JOIN users u ON u.id = ape.user_id
                JOIN sucursales s ON s.id_sucursal = c.id_sucursal
                WHERE id_apertura = ? and ape.estado = ?',
            [
                $id_apertura,
                'CERRADA'
            ]
        );

        // obtener los totales por forma de pago segun cobros
        $totales_forma_pago = DB::select(
            "SELECT fp.descripcion as forma_pago, coalesce(sum(c.cobro_importe), 0) as total_cobro
                FROM cobros c
                JOIN metodo_pagos fp ON fp.id_metodo_pago = c.id_metodo_pago
                JOIN ventas ve on ve.id_venta = c.id_venta 
                    WHERE ve.id_apertura = ? and ve.estado = ?
                GROUP BY fp.id_metodo_pago",
            [
                $id_apertura,
                'COMPLETADO'
            ]
        );

        // retornar vista con los datos del cierre de caja en pdf
        $pdf = Pdf::loadView('ventas.cierre_caja_pdf', compact('cierre_caja', 'totales_forma_pago'));
        return $pdf->stream('cierre_caja_' . Carbon::now()->format('Y-m-d') . '.pdf');// concatenar fecha actual al nombre del archivo

        // redireccionar a la vista ventas con mensaje de exito
        // Alert::toast('Caja cerrada con éxito', 'success');
        // return redirect()->route('ventas.index');
    }

    public function editCierre($id_apertura)
    {
        // buscar el registro por id_apertura
        $apertura = DB::selectOne('SELECT * FROM apertura_cierre_cajas WHERE id_apertura = ?', [$id_apertura]);

        // validar si la apertura existe
        if (empty($apertura)) {
            return response()->json([
                'message' => 'La apertura de caja no existe',
                'success' => false
            ]);
        }

        // sumamos los totales de las ventas realizadas en la caja abierta segun estado de ventas COMPLETADO
        // $total_ventas = DB::selectOne(
        //     'SELECT SUM(total) AS total 
        //     FROM ventas
        //         WHERE id_apertura = ? AND estado = ?', 
        //     [
        //         $apertura->id_apertura, 
        //         'COMPLETADO'
        //     ]
        // );

        // sumamos los totales de las ventas realizadas en la caja abierta segun estado de ventas COMPLETADAS
        $total_ventas = DB::selectOne(
            "SELECT coalesce(sum(c.cobro_importe), 0)as total_cobro
            FROM cobros c
                JOIN ventas ve on ve.id_venta = c.id_venta 
            WHERE ve.id_apertura = ? and ve.estado = 'PAGADO' ",
            [
                $apertura->id_apertura,
            ]
        );

        // normalizar monto de apertura antes de enviarlo a la vista
        $apertura->monto_apertura = $this->normalizarMonto($apertura->monto_apertura);

        // retornar datos en formato json
        return response()->json([
            'apertura' => $apertura,
            'total_ventas' => $total_ventas->total_cobro ?? 0,
            'success' => true
        ]);
    }

    /**
     * Normaliza montos recibidos desde formularios con distintos formatos.
     */
    private function normalizarMonto($valor)
    {
        if (is_null($valor) || $valor === '') {
            return 0;
        }

        if (is_numeric($valor) && strpos($valor, '.') === false && strpos($valor, ',') === false) {
            return (float)$valor;
        }

        $valor = trim(str_replace(' ', '', $valor));

        if (strpos($valor, ',') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            return (float)$valor;
        }

        if (strpos($valor, '.') !== false) {
            $decimales = strlen(substr(strrchr($valor, '.'), 1));

            if ($decimales === 1 || $decimales === 2) {
                return (float)$valor;
            }

            $valor = str_replace('.', '', $valor);
            return (float)$valor;
        }

        return (float)$valor;
    }
}
