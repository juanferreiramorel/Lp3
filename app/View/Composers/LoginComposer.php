<?php

namespace App\View\Composers;

use App\Models\Empresa;
use App\Models\Timbrado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Feeds the sign-in screen the header of a real receipt: the company, its
 * RUC and the invoice timbrado in force.
 *
 * Everything shown here is already printed on every invoice the business
 * hands out, so it is safe before authentication. Operational state (a
 * timbrado about to expire or run out, or none in force) is deliberately
 * NOT shown here: this page is public, and that belongs behind sign-in.
 */
class LoginComposer
{
    private const DIAS = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];

    public function compose(View $view): void
    {
        $view->with('comprobante', $this->comprobante());
    }

    private function comprobante(): array
    {
        $ahora = Carbon::now();

        $datos = [
            'empresa'  => null,
            'ruc'      => null,
            'timbrado' => null,
            'fecha'    => self::DIAS[$ahora->dayOfWeek] . ' ' . $ahora->format('d/m/y'),
            'hora'     => $ahora->format('H:i'),
        ];

        // The sign-in screen must render even when this lookup cannot: a
        // missing table or a bad row is not a reason to lock everyone out.
        try {
            $timbrado = Timbrado::vigentes()
                ->facturas()
                ->with('empresa')
                ->orderBy('fecha_vencimiento')
                ->first();

            $empresa = $timbrado?->empresa
                ?? Empresa::active()->orderBy('id_empresa')->first();

            if ($empresa) {
                $datos['empresa'] = $empresa->nombre_comercial ?: $empresa->razon_social;
                $datos['ruc'] = $empresa->ruc;
            }

            if ($timbrado) {
                $datos['timbrado'] = [
                    'numero'     => $timbrado->numero_timbrado,
                    'expedicion' => str_pad((string) $timbrado->establecimiento, 3, '0', STR_PAD_LEFT)
                        . ' — '
                        . str_pad((string) $timbrado->punto_expedicion, 3, '0', STR_PAD_LEFT),
                    'vigencia'   => $timbrado->fecha_inicio->format('d/m/y')
                        . ' — '
                        . $timbrado->fecha_vencimiento->format('d/m/y'),
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Login receipt header unavailable', ['error' => $e->getMessage()]);
        }

        return $datos;
    }
}
