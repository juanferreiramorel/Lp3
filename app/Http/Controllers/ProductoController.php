<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;// libreria a importar para paginacion manual

class ProductoController extends AppBaseController
{
    // Definir una variable para el path
    private $path;
    public function __construct()
    {
        $this->middleware('auth');
        // Definir Path para grabar mi archivo recibido
        $this->path = public_path() . "/img/productos/";
        // permisos
        $this->middleware('permission:productos index')->only(['index']);
        $this->middleware('permission:productos create')->only(['create', 'store']);
        $this->middleware('permission:productos edit')->only(['edit', 'update']);
        $this->middleware('permission:productos destroy')->only(['destroy']);

    }
    public function index(Request $request)
    {
        // Obtener parámetros de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter');
        $marcaFilter = $request->get('marca_filter');
        $taxFilter = $request->get('tax_filter');
        $stockFilter = $request->get('stock_filter');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir la query base con subconsulta para columna calculada estado
        $query = 'SELECT * FROM (
            SELECT p.*, m.descripcion as marcas, t.nombre as tax_nombre, t.porcentaje as tax_porcentaje,
                   CASE WHEN p.activo = true THEN \'Activo\' ELSE \'Inactivo\' END as estado
            FROM productos p
                JOIN marcas m ON p.id_marca = m.id_marca
                LEFT JOIN taxes t ON p.id_tax = t.id_tax
        ) AS productos_completo WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado si existe
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $estadoValue = ($estadoFilter === '1' || $estadoFilter === 'true') ? 'Activo' : 'Inactivo';
            $query .= ' AND estado = ?';
            $params[] = $estadoValue;
        }

        // Aplicar filtro de marca si existe
        if ($marcaFilter !== null && $marcaFilter !== '') {
            $query .= ' AND id_marca = ?';
            $params[] = (int)$marcaFilter;
        }

        // Aplicar filtro de impuesto si existe
        if ($taxFilter !== null && $taxFilter !== '') {
            $query .= ' AND id_tax = ?';
            $params[] = (int)$taxFilter;
        }

        // Aplicar filtro de controlar stock si existe
        if ($stockFilter !== null && $stockFilter !== '') {
            $stockValue = ($stockFilter === '1' || $stockFilter === 'true');
            $query .= ' AND controlar_stock = ?';
            $params[] = $stockValue;
        }

        // Aplicar búsqueda por texto
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND id_producto = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Limpiar separadores de miles para búsqueda de precios
                $buscarPrecio = str_replace('.', '', $buscarTerm);

                // Consulta con parámetros preparados para seguridad
                $query .= ' AND (descripcion ILIKE ?
                     OR marcas ILIKE ?
                     OR codigo_barras ILIKE ?
                     OR cast(id_producto as text) ILIKE ?
                     OR cast(precio as text) ILIKE ?
                     OR TO_CHAR(precio, \'FM999G999G999G999G999\') ILIKE ?
                     OR estado ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarPrecio . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        $query .= ' ORDER BY id_producto DESC';

        // Ejecutar la consulta
        $productos = DB::select($query, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($productos);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($productos, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $productos = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // si la accion es buscardor entonces significa que se debe recargar mediante ajax la tabla
        if ($request->ajax()) {// devuelve true o false si es ajax o no
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('productos.table')->with('productos', $productos);
        }


        // Obtener marcas para el filtro
        $marcas = DB::table('marcas')->orderBy('descripcion')->pluck('descripcion', 'id_marca');

        // Obtener impuestos para el filtro
        $taxes = DB::table('taxes')->orderBy('nombre')->pluck('nombre', 'id_tax');

        return view('productos.index')
            ->with('productos', $productos)
            ->with('estadoFilter', $estadoFilter)
            ->with('marcas', $marcas)
            ->with('taxes', $taxes);
    }

    public function create()
    {
        // Obtener solo las marcas activas para el select
        $marcas = DB::table('marcas')->where('activo', true)->pluck('descripcion', 'id_marca');

        // Obtener los impuestos activos
        $taxes = DB::select('SELECT * FROM taxes WHERE activo = true ORDER BY nombre');

        return view('productos.create')
            ->with('marcas', $marcas)
            ->with('taxes', $taxes);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'precio' => 'required',
            'id_marca' => 'required|exists:marcas,id_marca',
            'id_tax' => 'required|exists:taxes,id_tax',
            'controlar_stock' => 'required|boolean',
            'imagen_producto' => 'nullable|image|max:2048' // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
            'id_tax.required' => 'El impuesto es obligatorio. Todos los productos deben tener un impuesto asignado.',
            'id_tax.exists' => 'El impuesto seleccionado no existe.',
            'controlar_stock.required' => 'Debe indicar si el producto controla stock.',
            'controlar_stock.boolean' => 'El valor de controlar stock no es válido.',
            'imagen_producto.image' => 'El archivo debe ser una imagen.',
            'imagen_producto.max' => 'La imagen no debe superar los 2MB.',
        ]);

        // Si la validación falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada
        $existe = DB::selectOne(
            'SELECT * FROM productos WHERE LOWER(descripcion) = LOWER(?)',
            [$input['descripcion']]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un producto con esta descripción.'])->withInput();
        }

        // Validar con hasFile que exista ese dato
        $imagenPath = null;
        if ($request->hasFile('imagen_producto')) {
            // Generar nombre único para evitar conflictos
            $extension = $request->file('imagen_producto')->getClientOriginalExtension();
            $nombreUnico = time() . '_' . uniqid() . '.' . $extension;

            // Guardar en public/img/productos con nombre único
            $request->file('imagen_producto')->move($this->path, $nombreUnico);
            $imagenPath = $nombreUnico;
        }
        // sobre escribir el atributo imagen_producto de la variable $input
        $input['imagen_producto'] = $imagenPath;


        // Sacar separador de miles y cambiar  por vacio en el precio
        $precio = str_replace('.', '', $input['precio']);

        $controlarStock = filter_var($input['controlar_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $controlarStock = $controlarStock === null ? (int) $input['controlar_stock'] : $controlarStock;
        $controlarStock = $controlarStock ? 1 : 0;
        $input['controlar_stock'] = $controlarStock;

        // Insertar el nuevo producto en la base de datos
        DB::selectOne(
            'INSERT INTO productos (descripcion, codigo_barras, precio, id_marca, id_tax, controlar_stock, imagen_producto) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id_producto',
            [
                $input['descripcion'],
                $input['codigo_barras'] ?? null,
                $precio,
                $input['id_marca'],
                $input['id_tax'],
                $controlarStock,
                $input['imagen_producto']
            ]
        );

        // Redirigir a la lista de productos con un mensaje de éxito
        Alert::success('Éxito', 'Producto creado correctamente.');

        return redirect(route('productos.index'));
    }

    public function edit($id)
    {
        $productos = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        // Si el producto no existe, redirigir con un mensaje de error
        if (empty($productos)) {
            Alert::error('Error', 'Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        // Obtener marcas activas O la marca actual del producto (aunque esté inactiva)
        // Esto permite editar productos con marcas inactivas sin perder la asignación
        $marcas = DB::table('marcas')
            ->where('activo', true)
            ->orWhere('id_marca', $productos->id_marca)
            ->pluck('descripcion', 'id_marca');

        // Obtener los impuestos activos
        $taxes = DB::select('SELECT * FROM taxes WHERE activo = true ORDER BY nombre');

        return view('productos.edit')
            ->with('productos', $productos)
            ->with('marcas', $marcas)
            ->with('taxes', $taxes);
    }

    public function update(Request $request, $id) 
    {
        $input = $request->all();

        // Obtener el producto de la base de datos 1 solo valor utilizando selectOne
        $productos = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        // Si el producto no existe, redirigir con un mensaje de error
        if (empty($productos)) {
            Alert::error('Error', 'Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'precio' => 'required',
            'id_marca' => 'required|exists:marcas,id_marca',
            'id_tax' => 'required|exists:taxes,id_tax',
            'controlar_stock' => 'required|boolean',
            'imagen_producto' => 'nullable|image|max:2048', // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
            'id_tax.required' => 'El impuesto es obligatorio. Todos los productos deben tener un impuesto asignado.',
            'id_tax.exists' => 'El impuesto seleccionado no existe.',
            'controlar_stock.required' => 'Debe indicar si el producto controla stock.',
            'controlar_stock.boolean' => 'El valor de controlar stock no es válido.',
            'imagen_producto.image' => 'El archivo debe ser una imagen.',
            'imagen_producto.max' => 'La imagen no debe superar los 2MB.',
        ]);

        // Si la validación falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que la descripción no esté duplicada (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM productos WHERE LOWER(descripcion) = LOWER(?) AND id_producto != ?',
            [$input['descripcion'], $id]
        );
        if ($existe) {
            return back()->withErrors(['descripcion' => 'Ya existe un producto con esta descripción.'])->withInput();
        }

        // validar con hasFile que exista ese dato
        $imagenPath = $productos->imagen_producto; // Mantener imagen anterior por defecto

        if ($request->hasFile('imagen_producto')) {
            // Generar nombre único para evitar conflictos
            $extension = $request->file('imagen_producto')->getClientOriginalExtension();
            $nombreUnico = time() . '_' . uniqid() . '.' . $extension;

            // Eliminar imagen anterior si existe
            if ($productos->imagen_producto && file_exists($this->path . $productos->imagen_producto)) {
                unlink($this->path . $productos->imagen_producto);
            }

            // Guardar nueva imagen con nombre único
            $request->file('imagen_producto')->move($this->path, $nombreUnico);
            $imagenPath = $nombreUnico;
        }
        // sobre escribir el atributo imagen_producto de la variable $input
        $input['imagen_producto'] = $imagenPath;


        // Sacar separador de miles y cambiar  por vacio en el precio
        $precio = str_replace('.', '', $input['precio']);

        $controlarStock = filter_var($input['controlar_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $controlarStock = $controlarStock === null ? (int) $input['controlar_stock'] : $controlarStock;
        $controlarStock = $controlarStock ? 1 : 0;
        $input['controlar_stock'] = $controlarStock;

        // Actualizar el producto en la base de datos
        DB::update(
            'UPDATE productos SET descripcion = ?, codigo_barras = ?, precio = ?, id_marca = ?, id_tax = ?, controlar_stock = ?, imagen_producto = ? WHERE id_producto = ?',
            [
                $input['descripcion'],
                $input['codigo_barras'] ?? null,
                $precio,
                $input['id_marca'],
                $input['id_tax'],
                $controlarStock,
                $input['imagen_producto'],
                $id
            ]
        );

        // Redirigir a la lista de productos con un mensaje de éxito
        Alert::success('Éxito', 'Producto actualizado correctamente.');

        return redirect(route('productos.index'));
    }

    public function destroy($id)
    {
        // Verificar si el producto existe
        $producto = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        if (empty($producto)) {
            Alert::error('Error', 'Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        try {
            // Cambiar el estado activo (toggle)
            $nuevoEstado = !$producto->activo;
            DB::update('UPDATE productos SET activo = ? WHERE id_producto = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Producto activado correctamente.' : 'Producto inactivado correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect(route('productos.index'));
    }
}
