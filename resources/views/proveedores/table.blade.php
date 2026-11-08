<div class="card-body p-0">
    <x-data-table :columns="$columns" :sort="$sort" :paginator="$proveedores"
                  id="proveedores-table"
                  empty="No hay proveedores para mostrar.">
                @foreach ($proveedores as $proveedor)
                    <tr>
                        <td>{{ $proveedor->id_proveedor }}</td>
                        <td>{{ $proveedor->descripcion }}</td>
                        <td>{{ $proveedor->ruc }}</td>
                        <td>{{ $proveedor->direccion }}</td>
                        <td>{{ $proveedor->telefono }}</td>
                        <td style="width: 120px">
                            {!! Form::open(['route' => ['proveedores.destroy', $proveedor->id_proveedor], 'method' => 'delete']) !!}
                            <div class='btn-group'>

                                <a href="{{ route('proveedores.edit', [$proveedor->id_proveedor]) }}"
                                    class='btn btn-default btn-xs'>
                                    <i class="far fa-edit"></i>
                                </a>
                                {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-xs alert-delete',
                                    'data-mensage' => $proveedor->descripcion,
                                ]) !!}
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
    </x-data-table>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $proveedores])
        </div>
    </div>
</div>
