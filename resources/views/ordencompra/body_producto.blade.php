<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Código</th>
            <th>Producto</th>
            <th class="text-right">Precio</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productos as $product)
            <tr onclick="seleccionarProducto('{{ $product->id_producto }}', '{{ addslashes($product->descripcion) }}', '{{ $product->precio }}', '{{ $product->tax_porcentaje ?? 0 }}')">
                <td>{{ $product->id_producto }}</td>
                <td>{{ $product->descripcion }}</td>
                <td class="text-right">{{ number_format($product->precio, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No se encontraron productos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
