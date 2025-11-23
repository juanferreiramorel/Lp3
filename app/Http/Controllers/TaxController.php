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

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $taxes = DB::select(
                    'SELECT * FROM taxes WHERE id_tax = ? ORDER BY id_tax DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                $taxes = DB::select(
                    'SELECT * FROM taxes
                     WHERE nombre ILIKE ?
                     OR descripcion ILIKE ?
                     OR cast(porcentaje as text) ILIKE ?
                     OR cast(id_tax as text) ILIKE ?
                     ORDER BY id_tax DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            // Consulta para obtener todos los impuestos
            $taxes = DB::select('SELECT * FROM taxes ORDER BY id_tax DESC');
        }

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
            return view('taxes.table')->with('taxes', $taxes);
        }

        return view('taxes.index')->with('taxes', $taxes);
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
