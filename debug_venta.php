<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Venta;
use Illuminate\Support\Facades\DB;

// Fetch the sale that likely caused this.
// User said Total 15000 in error.
$venta = Venta::where('total', 15000)->orderBy('id_venta', 'desc')->first();

if (!$venta) {
    echo "No sale found with total 15000. Checking last sale:\n";
    $venta = Venta::latest('id_venta')->first();
}

if ($venta) {
    echo "Venta ID: " . $venta->id_venta . "\n";
    echo "Factura: " . $venta->factura_nro . "\n";
    echo "Total Header: " . $venta->total . "\n";
    
    $detalles = DB::table('detalle_venta')->where('id_venta', $venta->id_venta)->get();
    $sumDetalles = 0;
    echo "--- Detalles ---\n";
    foreach($detalles as $d) {
        $sub = $d->cantidad * $d->precio_unitario;
        $sumDetalles += $sub;
        echo "Prod Info: " . $d->id_producto . " | Cant: " . $d->cantidad . " | Precio: " . $d->precio_unitario . " | Sub: " . $sub . "\n";
    }
    echo "Sum Detalles: " . $sumDetalles . "\n";
    
    echo "--- Notas Credito ---\n";
    $ncs = DB::table('notas_credito')->where('id_venta', $venta->id_venta)->get();
    $sumNC = 0;
    foreach($ncs as $nc) {
        echo "NC ID: " . $nc->id_nota_credito . " | Total: " . $nc->total . " | Estado: " . $nc->estado . "\n";
        if($nc->estado != 'ANULADO') $sumNC += $nc->total;
    }
    echo "Total NC Válidas: " . $sumNC . "\n";
}
