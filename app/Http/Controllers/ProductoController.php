<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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
                     ORDER BY p.id_producto DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarPrecio . '%', '%' . $buscarTerm . '%']
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
        // Obtener las marcas para cargar el select
        $marcas = DB::table('marcas')->pluck('descripcion', 'id_marca');

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
            'imagen_producto' => 'nullable|image|max:2048' // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
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

        // Insertar el nuevo producto en la base de datos
        $idProducto = DB::selectOne(
            'INSERT INTO productos (descripcion, precio, id_marca, imagen_producto) VALUES (?, ?, ?, ?) RETURNING id_producto',
            [
                $input['descripcion'],
                $precio,
                $input['id_marca'],
                $input['imagen_producto']
            ]
        );

        // Sincronizar impuestos si se seleccionaron
        if (isset($input['taxes']) && is_array($input['taxes'])) {
            foreach ($input['taxes'] as $taxId) {
                DB::insert(
                    'INSERT INTO product_tax (id_producto, id_tax, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                    [$idProducto->id_producto, $taxId]
                );
            }
        }

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

        // Obtener las marcas para cargar el select
        $marcas = DB::table('marcas')->pluck('descripcion', 'id_marca');

        // Obtener los impuestos activos
        $taxes = DB::select('SELECT * FROM taxes WHERE activo = true ORDER BY nombre');

        // Obtener los impuestos seleccionados para este producto
        $selectedTaxes = DB::select(
            'SELECT id_tax FROM product_tax WHERE id_producto = ?',
            [$id]
        );
        $selectedTaxes = array_column($selectedTaxes, 'id_tax');

        return view('productos.edit')
            ->with('productos', $productos)
            ->with('marcas', $marcas)
            ->with('taxes', $taxes)
            ->with('selectedTaxes', $selectedTaxes);
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
            'imagen_producto' => 'nullable|image|max:2048', // Validación para la imagen (opcional)
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
            'id_marca.required' => 'La marca es obligatoria.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
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

        // Actualizar el producto en la base de datos
        DB::update(
            'UPDATE productos SET descripcion = ?, precio = ?, id_marca = ?, imagen_producto = ? WHERE id_producto = ?',
            [
                $input['descripcion'],
                $precio,
                $input['id_marca'],
                $input['imagen_producto'],
                $id
            ]
        );

        // Sincronizar impuestos
        // Primero eliminar todas las relaciones existentes
        DB::delete('DELETE FROM product_tax WHERE id_producto = ?', [$id]);

        // Luego agregar las nuevas relaciones si se seleccionaron
        if (isset($input['taxes']) && is_array($input['taxes'])) {
            foreach ($input['taxes'] as $taxId) {
                DB::insert(
                    'INSERT INTO product_tax (id_producto, id_tax, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                    [$id, $taxId]
                );
            }
        }

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
            // Eliminar imagen del producto si existe
            if ($producto->imagen_producto && file_exists($this->path . $producto->imagen_producto)) {
                unlink($this->path . $producto->imagen_producto);
            }

            // Eliminar el producto de la base de datos
            DB::delete('DELETE FROM productos WHERE id_producto = ?', [$id]);
            Flash::success('Producto eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se puede eliminar este producto porque está siendo utilizado en otros registros.');
        }

        return redirect(route('productos.index'));
    }
}
