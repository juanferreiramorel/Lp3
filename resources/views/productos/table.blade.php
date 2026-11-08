    <div class="card-body p-0">
        <x-data-table :columns="$columns" :sort="$sort" :paginator="$productos"
                      id="productos-table"
                      empty="No hay productos para mostrar.">
                    @foreach ($productos as $producto)
                        <tr style="{{ !$producto->activo ? 'opacity: 0.6; background-color: #f8f9fa;' : '' }}">
                            <td>{{ $producto->id_producto }}</td>
                            <td>
                                @if ($producto->imagen_producto)
                                    <img src="{{ asset('img/productos/' . $producto->imagen_producto) }}"
                                        alt="Imagen del Producto" style="width: 80px; height: 50px; display: block; margin: auto;">
                                @else
                                    No hay imagen
                                @endif
                            </td>
                            <td>{{ $producto->descripcion }}</td>
                            <td>{{ $producto->codigo_barras ?? '-' }}</td>
                            <td>{{ number_format($producto->precio, 0, ',', '.') }}</td>
                            <td>
                                @if($producto->tax_nombre)
                                    {{ $producto->tax_nombre }} ({{ number_format($producto->tax_porcentaje, 2) }}%)
                                @else
                                    <span class="text-muted">Sin impuesto</span>
                                @endif
                            </td>
                            <td>{{ !empty($producto->controlar_stock) ? 'Sí' : 'No' }}</td>
                            <td>{{ $producto->marcas }}</td>
                            <td>
                                @if($producto->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td style="width: 120px">
                                {!! Form::open(['route' => ['productos.destroy', $producto->id_producto], 'method' => 'delete']) !!}
                                <div class='btn-group'>
                                    <a href="{{ route('productos.edit', [$producto->id_producto]) }}" class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                    @if($producto->activo)
                                        {!! Form::button('<i class="fas fa-ban"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-xs alert-toggle',
                                            'data-title' => 'Inactivar Producto',
                                            'data-mensage' => 'Desea inactivar el producto: ' . $producto->descripcion . '?',
                                            'title' => 'Inactivar producto'
                                        ]) !!}
                                    @else
                                        {!! Form::button('<i class="fas fa-check-circle"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-success btn-xs alert-toggle',
                                            'data-title' => 'Activar Producto',
                                            'data-mensage' => 'Desea activar el producto: ' . $producto->descripcion . '?',
                                            'title' => 'Activar producto'
                                        ]) !!}
                                    @endif
                                </div>
                                {!! Form::close() !!}
                            </td>
                        </tr>
                    @endforeach
        </x-data-table>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $productos])
            </div>
        </div>
    </div>
