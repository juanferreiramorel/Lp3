<table class="table">
    <thead>
        <tr>
            <th>Código de Producto</th>
            <th>Producto</th>
            <th>IVA</th>
            <th>Precio (IVA incl.)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productos as $product)
            <tr onclick="seleccionarProducto('{{ $product->id_producto }}', '{{ $product->descripcion }}', '{{ $product->precio }}')">
                <td>{{ $product->id_producto }}</td>
                <td>{{ $product->descripcion }}</td>
                <td>
                    @if(isset($product->tax_nombre))
                        {{ number_format($product->tax_porcentaje ?? 0, 0) }}%
                    @else
                        -
                    @endif
                </td>
                <td>{{ number_format($product->precio, 0, ',', '.') }} Gs.</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No se encontraron productos.</td>
            </tr>
        @endforelse
    </tbody>
</table>
