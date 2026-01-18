<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class PagoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pagos index')->only(['index']);
        $this->middleware('permission:pagos create')->only(['store']);
    }

    public function index(Request $request)
    {
        // recibir parametros id compra y buscar las compras asociadas
        $id_compra = $request->get('id_compra');

        // buscar las compras asociadas
        if (empty($id_compra)) {
            Alert::toast('Debe seleccionar una compra', 'error');
            return redirect()->route('compras.index');
        }
        // consultar la compra
        $compra = DB::selectOne("SELECT c.*, p.descripcion as proveedor
                    FROM compras c
                    INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                    WHERE id_compra = ?", [$id_compra]);

        if (empty($compra)) {
            Alert::toast('La compra no existe', 'error');
            return redirect()->route('compras.index');
        }

        // enviar metodos de pago, listar solos los activos
        // enviar metodos de pago, listar solos los activos
        $metodos_pago = $this->getMetodosPago();

        // retornar la vista de pagos que se encuentra en resources/views/compras/pagos.blade.php
        return view('compras.pagos')->with('compras', $compra)->with('metodos_pago', $metodos_pago);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // validar que la compra exista
        $compras = DB::selectOne("SELECT * FROM compras WHERE id_compra = ?", [$input['id_compra']]);

        if (empty($compras)) {
            Alert::toast('La compra no existe', 'error');
            return redirect()->route('compras.index');
        }

        DB::beginTransaction();
        try {
            $total_pagos = 0; // acumulador de pagos para calculo del total
            // validar que exista la formas de pago recibidas del formulario
            if ($request->has('forma_pago')) {
                foreach ($input['forma_pago'] as $key => $metodo) {
                    // validar que la forma de pago exista
                    $metodo_pago = DB::selectOne("SELECT * FROM metodo_pagos WHERE id_metodo_pago = ?", [$metodo]);
                    if (empty($metodo_pago)) {
                        Alert::toast('La forma de pago no existe', 'error');
                        return redirect()->route('pagos.index', ['id_compra' => $input['id_compra']]);
                    }

                    $importe = str_replace('.', '', $input['importe'][$key] ?? '0');
                    // Convertir a numérico para evitar errores de tipos
                    $importe = (int)$importe;

                    // validar que el importe sea un numero mayor a 0
                    if ($importe <= 0) {
                        Alert::toast('El importe debe ser un numero mayor a 0', 'error');
                        return redirect()->route('pagos.index', ['id_compra' => $input['id_compra']]);
                    }

                    // guardar acumulador de pagos
                    $total_pagos += $importe;

                    // registrar el pago
                    DB::insert(
                        'INSERT INTO pagos(id_compra, user_id, id_metodo_pago, pago_fecha, pago_importe, pago_estado, nro_voucher)
                        VALUES(?, ?, ?, ?, ?, ?, ?)',
                        [
                            $compras->id_compra,
                            auth()->user()->id,
                            $metodo,
                            Carbon::now()->format('Y-m-d'),
                            $importe,
                            'PAGADO',
                            $input['nro_voucher'][$key] ?? null
                        ]
                    );
                }

                // validar que el total de pagos sea igual al total de la compra
                if ($total_pagos != $compras->total) {
                    Alert::toast('El total de pagos debe ser igual al total de la compra', 'error');
                    return redirect()->route('pagos.index', ['id_compra' => $input['id_compra']]);
                }
                // Actualizar el estado de la compra a PAGADO si el total de pagos es igual al total de la compra
                DB::update("UPDATE compras SET estado = 'PAGADO' WHERE id_compra = ?", [$compras->id_compra]);

                DB::commit();
            }
        } catch (\Exception $e) {
            // si hay un error en la transaccion, hacer rollback
            DB::rollBack();
            // registrar el error en el log
            Log::info("Error en transaccion de pagos::::::" . $e->getMessage());
            Alert::toast("Error en el proceso:" . $e->getMessage(), "error");

            return redirect()->route('pagos.index', ['id_compra' => $input['id_compra']]);
        }

        Alert::toast('Pago guardado correctamente', 'success');
        return redirect()->route('compras.index');
    }
}
