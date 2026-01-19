<?php

use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Get the latest Sale
$lastVenta = Venta::orderBy('id_venta', 'desc')->first();

if (!$lastVenta) {
    echo "No sales found.\n";
    exit;
}

echo "Latest Venta ID: " . $lastVenta->id_venta . "\n";
echo "Factura Nro: " . $lastVenta->factura_nro . "\n";

// 2. Check Raw DB for details
$rawDetails = DB::table('detalle_ventas')->where('id_venta', $lastVenta->id_venta)->get();
echo "Raw DB Details Count: " . $rawDetails->count() . "\n";
foreach($rawDetails as $d) {
    echo " - Item: ProdID=" . $d->id_producto . ", Precio=" . $d->precio . ", TaxID=" . ($d->id_tax ?? 'NULL') . "\n";
}

// 3. Check Eloquent Relationship
echo "\nChecking Eloquent 'detalles' relation...\n";
$eloquentDetails = $lastVenta->detalles;

echo "Eloquent Details Count: " . $eloquentDetails->count() . "\n";

if ($rawDetails->count() > 0 && $eloquentDetails->count() == 0) {
    echo "MISMATCH! Eloquent is not finding the records.\n";
    echo "Venta Keys: PK=" . $lastVenta->getKeyName() . ", Val=" . $lastVenta->getKey() . "\n";
    echo "Relation localKey: " . $lastVenta->detalles()->getLocalKeyName() . "\n";
    echo "Relation foreignKey: " . $lastVenta->detalles()->getForeignKeyName() . "\n";
} elseif ($eloquentDetails->count() > 0) {
    echo "Eloquent Loaded OK. Checking Products...\n";
    foreach($eloquentDetails as $det) {
        $prod = $det->producto;
        if ($prod) {
            echo " - Product: " . $prod->descripcion . " (Tax: " . ($prod->tax ? $prod->tax->porcentaje : 'N/A') . "%)\n";
        } else {
            echo " - Product: NULL (Problem!)\n";
        }
    }
}
