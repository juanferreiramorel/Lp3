<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class TaxController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // permisos
        $this->middleware('permission:taxes index')->only(['index']);
        $this->middleware('permission:taxes create')->only(['create', 'store']);
        $this->middleware('permission:taxes edit')->only(['edit', 'update']);
        $this->middleware('permission:taxes destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Obtener el término de búsqueda del request
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_tax', 'field' => 'id_tax', 'first' => 'desc'],
            'nombre' => ['label' => 'Nombre', 'sort' => 'nombre'],
            'descripcion' => ['label' => 'Descripción', 'sort' => 'descripcion'],
            'porcentaje' => ['label' => 'Porcentaje', 'sort' => 'porcentaje', 'first' => 'desc'],
            'estado' => ['label' => 'Estado', 'sort' => 'activo', 'field' => 'activo', 'format' => 'estado'],
            'acciones' => ['label' => 'Acciones', 'width' => '140px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        // The WHERE clause is built apart from the SELECT so the listing and
        // the export can share one statement.
        $sql_busqueda = '';
        $params = [];

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $sql_busqueda = 'WHERE id_tax = ?';
                $params = [(int)$searchConfig['term']];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                $sql_busqueda = 'WHERE nombre ILIKE ?
                     OR descripcion ILIKE ?
                     OR cast(porcentaje as text) ILIKE ?
                     OR cast(id_tax as text) ILIKE ?';
                $params = ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%'];
            }
        }

        $seleccion = "SELECT * FROM taxes {$sql_busqueda} ORDER BY {$sort->orderBy()}";

        // Exports run the same query without the page window, so the file
        // carries every row the current filters match, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => DB::select($seleccion . ' LIMIT ' . \App\Support\TableExport::MAX_ROWS, $params), 'impuestos');
        }

        $taxes = DB::select($seleccion, $params);

        // Definimos los valores de paginación
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($taxes);

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($taxes, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $taxes = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Si la acción es búsqueda mediante ajax
        if ($request->ajax()) {
            return view('taxes.table')->with('taxes', $taxes)->with('columns', $columns)->with('sort', $sort);
        }

        return view('taxes.index')->with('taxes', $taxes)->with('columns', $columns)->with('sort', $sort);
    }

    public function create()
    {
        return view('taxes.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'nombre' => 'required|max:100',
            'descripcion' => 'nullable|max:255',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'activo' => 'boolean'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',
            'porcentaje.required' => 'El porcentaje es obligatorio.',
            'porcentaje.numeric' => 'El porcentaje debe ser un número válido.',
            'porcentaje.min' => 'El porcentaje no puede ser negativo.',
            'porcentaje.max' => 'El porcentaje no puede superar el 100%.',
        ]);

        // Si la validación falla, redirigir con errores
        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que el nombre no esté duplicado
        $existe = DB::selectOne(
            'SELECT * FROM taxes WHERE LOWER(nombre) = LOWER(?)',
            [$input['nombre']]
        );
        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe un impuesto con este nombre.'])->withInput();
        }

        // Establecer valor por defecto para activo
        $activo = isset($input['activo']) ? true : false;

        // Insertar el nuevo impuesto en la base de datos
        DB::insert(
            'INSERT INTO taxes (nombre, descripcion, porcentaje, activo, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [
                $input['nombre'],
                $input['descripcion'] ?? null,
                $input['porcentaje'],
                $activo
            ]
        );

        Alert::success('Éxito', 'Impuesto creado correctamente.');

        return redirect(route('taxes.index'));
    }

    public function edit($id)
    {
        $tax = DB::selectOne('SELECT * FROM taxes WHERE id_tax = ?', [$id]);

        if (empty($tax)) {
            Alert::error('Error', 'Impuesto no encontrado.');
            return redirect(route('taxes.index'));
        }

        return view('taxes.edit')->with('tax', $tax);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        // Obtener el impuesto de la base de datos
        $tax = DB::selectOne('SELECT * FROM taxes WHERE id_tax = ?', [$id]);

        if (empty($tax)) {
            Alert::error('Error', 'Impuesto no encontrado.');
            return redirect(route('taxes.index'));
        }

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'nombre' => 'required|max:100',
            'descripcion' => 'nullable|max:255',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'activo' => 'boolean'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',
            'porcentaje.required' => 'El porcentaje es obligatorio.',
            'porcentaje.numeric' => 'El porcentaje debe ser un número válido.',
            'porcentaje.min' => 'El porcentaje no puede ser negativo.',
            'porcentaje.max' => 'El porcentaje no puede superar el 100%.',
        ]);

        if ($validacion->fails()) {
            return redirect()->back()
                ->withErrors($validacion)
                ->withInput();
        }

        // Validar que el nombre no esté duplicado (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM taxes WHERE LOWER(nombre) = LOWER(?) AND id_tax != ?',
            [$input['nombre'], $id]
        );
        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe un impuesto con este nombre.'])->withInput();
        }

        // Establecer valor para activo
        $activo = isset($input['activo']) ? true : false;

        // Actualizar el impuesto en la base de datos
        DB::update(
            'UPDATE taxes SET nombre = ?, descripcion = ?, porcentaje = ?, activo = ?, updated_at = NOW() WHERE id_tax = ?',
            [
                $input['nombre'],
                $input['descripcion'] ?? null,
                $input['porcentaje'],
                $activo,
                $id
            ]
        );

        Alert::success('Éxito', 'Impuesto actualizado correctamente.');

        return redirect(route('taxes.index'));
    }

    public function destroy($id)
    {
        // Verificar si el impuesto existe
        $tax = DB::selectOne('SELECT * FROM taxes WHERE id_tax = ?', [$id]);

        if (empty($tax)) {
            Alert::error('Error', 'Impuesto no encontrado.');
            return redirect(route('taxes.index'));
        }

        try {
            // Verificar si el impuesto está asociado a productos
            $productosAsociados = DB::selectOne(
                'SELECT COUNT(*) as total FROM productos WHERE id_tax = ?',
                [$id]
            );

            if ($productosAsociados && $productosAsociados->total > 0) {
                Alert::error('Error', 'No se puede eliminar este impuesto porque está asociado a ' . $productosAsociados->total . ' producto(s).');
                return redirect(route('taxes.index'));
            }

            // Eliminar el impuesto de la base de datos
            DB::delete('DELETE FROM taxes WHERE id_tax = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect(route('taxes.index'));
    }

    public function show($id)
    {
        $tax = DB::selectOne('SELECT * FROM taxes WHERE id_tax = ?', [$id]);

        if (empty($tax)) {
            Flash::error('Impuesto no encontrado.');
            return redirect(route('taxes.index'));
        }

        // Obtener productos asociados a este impuesto
        $productos = DB::select(
            'SELECT p.*, m.descripcion as marcas
             FROM productos p
             JOIN marcas m ON p.id_marca = m.id_marca
             WHERE p.id_tax = ?
             ORDER BY p.descripcion',
            [$id]
        );

        return view('taxes.show')
            ->with('tax', $tax)
            ->with('productos', $productos);
    }
}
