    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table" id="productos-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Imagen</th>
                        <th>Descripción</th>
                        <th>Código de Barras</th>
                        <th>Precio</th>
                        <th>Impuesto</th>
                        <th>Controla Stock</th>
                        <th>Marca</th>
                        <th colspan="3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $producto)
                        <tr>
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
                            <td style="width: 120px">
                                {!! Form::open(['route' => ['productos.destroy', $producto->id_producto], 'method' => 'delete']) !!}
                                <div class='btn-group'>
                                    <a href="{{ route('productos.edit', [$producto->id_producto]) }}"
                                        class='btn btn-default btn-xs'>
                                        <i class="far fa-edit"></i>
                                    </a>
                                    {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-xs alert-delete',
                                        'data-mensaje' => 'este registro',
                                    ]) !!}
                                </div>
                                {!! Form::close() !!}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $productos])
            </div>
        </div>
    </div>
