<?php
use Illuminate\Support\Facades\DB;
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orphans = DB::table('detalle_ventas')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))
          ->from('productos')
          ->whereColumn('detalle_ventas.id_producto', 'productos.id_producto');
    })
    ->count();

echo "Orphaned Records: " . $orphans . "\n";

if ($orphans > 0) {
    echo "Found orphans! List (Limit 10):\n";
    $list = DB::table('detalle_ventas')
        ->whereNotExists(function($q) {
             $q->select(DB::raw(1))
               ->from('productos')
               ->whereColumn('detalle_ventas.id_producto', 'productos.id_producto');
        })
        ->limit(10)
        ->get();
    foreach($list as $l) {
        echo " - ID: {$l->id_detalle_venta}, ID_Producto: {$l->id_producto}, Venta: {$l->id_venta}\n";
    }
}
