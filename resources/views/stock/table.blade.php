    <div class="card-body p-0">
        <x-data-table :columns="$columns" :sort="$sort" :paginator="$stock"
                      id="productos-table"
                      empty="No hay registros de stock para mostrar.">
                    @foreach ($stock as $fila)
                        <tr>
                            <td>{{ $fila->id }}</td>
                            <td>{{ $fila->sucursal }}</td>
                            <td>{{ $fila->producto }}</td>
                            <td>{{ $fila->marca }}</td>
                            <td>{{ $fila->cantidad }}</td>
                        </tr>
                    @endforeach
        </x-data-table>

        <div class="card-footer clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $stock])
            </div>
        </div>
    </div>
