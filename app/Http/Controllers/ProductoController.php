<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
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
        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $productos = DB::select(
                    'SELECT p.*, m.descripcion as marcas
                     FROM productos p
                        JOIN marcas m ON p.id_marca = m.id_marca
                     WHERE p.id_producto = ?
                     ORDER BY p.id_producto DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Limpiar separadores de miles para búsqueda de precios
                $buscarPrecio = str_replace('.', '', $buscarTerm);

                // Consulta con parámetros preparados para seguridad
                $productos = DB::select(
                    'SELECT p.*, m.descripcion as marcas
                     FROM productos p
                        JOIN marcas m ON p.id_marca = m.id_marca
                     WHERE p.descripcion ILIKE ?
                     OR m.descripcion ILIKE ?
                     OR cast(p.id_producto as text) ILIKE ?
                     OR cast(p.precio as text) ILIKE ?
                     OR TO_CHAR(p.precio, \'FM999G999G999G999G999\') ILIKE ?
                     OR cast(p.tipo_iva as text) ILIKE ?
                     ORDER BY p.id_producto DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarPrecio . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            // Consulta para obtener los productos con la marca asociada
            $productos = DB::select(
                'SELECT p.*, m.descripcion as marcas
                 FROM productos p
                    JOIN marcas m ON p.id_marca = m.id_marca
                 ORDER BY p.id_producto DESC'
            );
        }

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


        return view('productos.index')->with('productos', $productos);
    }

    public function create()
    {
        // Cargar los tipos de IVA para el select
        $tipo_iva = array(
            '0' => 'Exento',
            '5' => 'Gravada 5%',
            '10' => 'Gravada 10%',
        );

        // Obtener las marcas para cargar el select
        $marcas = DB::table('marcas')->pluck('descripcion', 'id_marca');

        return view('productos.create')->with('tipo_iva', $tipo_iva)->with('marcas', $marcas);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'precio' => 'required',
            'id_marca' => 'required|exists:marcas,id_marca',
            'tipo_iva' => 'required|numeric',
            'imagen_producto' => 'nullable|image|max:2048' // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
            'tipo_iva.required' => 'El tipo de IVA es obligatorio.',
            'tipo_iva.numeric' => 'El tipo de IVA debe ser un número válido.',
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
        if ($request->hasFile('imagen_producto')) {
            // obtener nombre del archivo de imagen con getClientOriginalName
            $imagen = $request->file('imagen_producto')->getClientOriginalName();

            // mover el archivo imagen al path definido
            $request->file('imagen_producto')->move($this->path, $imagen);
        }
        // sobre escribir el atributo imagen_producto de la variable $input
        // validar con isset() para verificar que exista la variable $imagen
        $input['imagen_producto'] = isset($imagen) ? $imagen : null;


        // Sacar separador de miles y cambiar  por vacio en el precio
        $precio = str_replace('.', '', $input['precio']);

        // Insertar el nuevo producto en la base de datos
        DB::insert(
            'INSERT INTO productos (descripcion, precio, id_marca, tipo_iva, imagen_producto) VALUES (?, ?, ?, ?, ?)',
            [
                $input['descripcion'],
                $precio,
                $input['id_marca'],
                $input['tipo_iva'],
                $input['imagen_producto']
            ]
        );

        // Redirigir a la lista de productos con un mensaje de éxito
        Flash::success('Producto creado correctamente.');

        return redirect(route('productos.index'));
    }

    public function edit($id)
    {
        $productos = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        // Si el producto no existe, redirigir con un mensaje de error
        if (empty($productos)) {
            Flash::error('Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        // Cargar los tipos de IVA para el select
        $tipo_iva = array(
            '0' => 'Exento',
            '5' => 'Gravada 5%',
            '10' => 'Gravada 10%',
        );

        // Obtener las marcas para cargar el select
        $marcas = DB::table('marcas')->pluck('descripcion', 'id_marca');

        return view('productos.edit')->with('productos', $productos)->with('tipo_iva', $tipo_iva)->with('marcas', $marcas);
    }

    public function update(Request $request, $id) 
    {
        $input = $request->all();

        // Obtener el producto de la base de datos 1 solo valor utilizando selectOne
        $productos = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        // Si el producto no existe, redirigir con un mensaje de error
        if (empty($productos)) {
            Flash::error('Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'descripcion' => 'required',
            'precio' => 'required',
            'id_marca' => 'required|exists:marcas,id_marca',
            'tipo_iva' => 'required|numeric',
            'imagen_producto' => 'nullable|image|max:2048', // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
            'tipo_iva.required' => 'El tipo de IVA es obligatorio.',
            'tipo_iva.numeric' => 'El tipo de IVA debe ser un número válido.',
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

        // validar con hasFile que exista eese dato
        if ($request->hasFile('imagen_producto')) {
            // obtener nombre del archivo de imagen con getClientOriginalName
            $imagen = $request->file('imagen_producto')->getClientOriginalName();

            // mover el archivo imagen al path definido
            $request->file('imagen_producto')->move($this->path, $imagen);
        }
        // sobre escribir el atributo imagen_producto de la variable $input
        $input['imagen_producto'] = isset($imagen) ? $imagen : $productos->imagen_producto;


        // Sacar separador de miles y cambiar  por vacio en el precio
        $precio = str_replace('.', '', $input['precio']);

        // Actualizar el producto en la base de datos
        DB::update(
            'UPDATE productos SET descripcion = ?, precio = ?, id_marca = ?, tipo_iva = ?, imagen_producto = ? WHERE id_producto = ?',
            [
                $input['descripcion'],
                $precio,
                $input['id_marca'],
                $input['tipo_iva'],
                $input['imagen_producto'],
                $id
            ]
        );

        // Redirigir a la lista de productos con un mensaje de éxito
        Flash::success('Producto actualizado correctamente.');

        return redirect(route('productos.index'));
    }

    public function destroy($id)
    {
        // Verificar si el producto existe
        $producto = DB::selectOne('SELECT * FROM productos WHERE id_producto = ?', [$id]);

        if (empty($producto)) {
            Flash::error('Producto no encontrado.');
            return redirect(route('productos.index'));
        }

        try {
            // Eliminar el producto de la base de datos
            DB::delete('DELETE FROM productos WHERE id_producto = ?', [$id]);
            Flash::success('Producto eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se puede eliminar este producto porque está siendo utilizado en otros registros.');
        }

        return redirect(route('productos.index'));
    }
}
