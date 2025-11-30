<table class="table">
    <thead>
        <tr>
            <th>Código de Producto</th>
            <th>Producto</th>
            <th>Precio</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productos as $product)
            @php
                // Obtener tax_porcentaje del objeto procesado por el controlador
                // El controlador ya procesó tax_porcentaje_val y lo asignó a tax_porcentaje
                $tax_porcentaje = 0;
                
                // Verificar si existe tax_porcentaje y tiene un valor válido
                if (isset($product->tax_porcentaje)) {
                    $tax_val = (float)$product->tax_porcentaje;
                    // Si el valor es mayor a 100, es un precio, establecer a 0
                    if ($tax_val > 100) {
                        $tax_porcentaje = 0;
                    } else {
                        $tax_porcentaje = $tax_val;
                    }
                }
                
                // Debug temporal - remover después
                // var_dump('Producto: ' . $product->id_producto . ', tax_porcentaje: ' . ($product->tax_porcentaje ?? 'NULL'));
            @endphp
            <tr onclick="seleccionarProducto('{{ $product->id_producto }}', '{{ addslashes($product->descripcion) }}', {{ intval($product->precio) }}, {{ $tax_porcentaje }})">
                <td>{{ $product->id_producto }}</td>
                <td>{{ $product->descripcion }}</td>
                <td>{{ number_format($product->precio, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">No se encontraron productos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
