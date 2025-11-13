<table class="table table-sm table-hover">
    <thead>
        <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productos as $product)
            <tr onclick="seleccionarProducto('{{ $product->id_producto }}', '{{ addslashes($product->descripcion) }}', '{{ $product->precio }}')">
                <td>{{ $product->id_producto }}</td>
                <td>{{ $product->descripcion }}</td>
                <td>{{ number_format($product->precio, 0, ',', '.') }}</td>
                <td>
                    <span class="badge badge-{{ $product->cantidad > 0 ? 'success' : 'danger' }}">
                        {{ $product->cantidad }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No se encontraron productos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
