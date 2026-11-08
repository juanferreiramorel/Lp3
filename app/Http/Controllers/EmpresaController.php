<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class EmpresaController extends AppBaseController
{
    // Definir una variable para el path
    private $path;

    public function __construct()
    {
        $this->middleware('auth');
        // Definir Path para grabar mi archivo recibido
        $this->path = public_path() . "/img/empresas/";
        $this->middleware('permission:empresas index')->only(['index']);
        $this->middleware('permission:empresas create')->only(['create', 'store']);
        $this->middleware('permission:empresas edit')->only(['edit', 'update']);
        $this->middleware('permission:empresas destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter'); // New filter
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $sql_busqueda .= " AND e.id_empresa = " . (int)$searchConfig['term'];
            } else {
                $buscarTerm = $searchConfig['term'];
                $sql_busqueda .= " AND (CAST(e.id_empresa AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR e.razon_social ILIKE '%{$buscarTerm}%'
                    OR e.nombre_comercial ILIKE '%{$buscarTerm}%'
                    OR e.ruc ILIKE '%{$buscarTerm}%')";
            }
        }

        // Apply state filter if present
        if ($estadoFilter !== null && $estadoFilter !== '') {
             $valor = ($estadoFilter === '1' || $estadoFilter === 'true') ? 'TRUE' : 'FALSE';
             $sql_busqueda .= " AND e.estado = $valor";
        }

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'e.id_empresa', 'first' => 'desc'],
            'razon_social' => ['label' => 'Razón Social', 'sort' => 'e.razon_social', 'tiebreaker' => 'e.id_empresa'],
            'nombre_comercial' => ['label' => 'Nombre Comercial', 'sort' => 'e.nombre_comercial', 'tiebreaker' => 'e.id_empresa'],
            'ruc' => ['label' => 'RUC', 'sort' => 'e.ruc', 'tiebreaker' => 'e.id_empresa'],
            'telefono' => ['label' => 'Teléfono', 'sort' => 'e.telefono', 'tiebreaker' => 'e.id_empresa'],
            'email' => ['label' => 'Email', 'sort' => 'e.email', 'tiebreaker' => 'e.id_empresa'],
            'estado' => ['label' => 'Estado', 'sort' => 'e.estado', 'tiebreaker' => 'e.id_empresa'],
            'acciones' => ['label' => 'Acción', 'width' => '140px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $empresas = DB::select(
            "SELECT e.* FROM empresas e
            {$sql_busqueda}
            ORDER BY {$sort->orderBy()}"
        );

        if ($request->ajax()) {
            return view('empresas.table')
                ->with('empresas', $empresas)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('empresas.index')
            ->with('empresas', $empresas)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    public function create()
    {
        return view('empresas.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Log de entrada para depuración
        Log::info('Intentando crear empresa', ['data' => $input]);

        $validacion = Validator::make(
            $input,
            [
                'razon_social' => 'required|string|max:255',
                'ruc' => 'required|string|max:50|unique:empresas,ruc',
                'nombre_comercial' => 'nullable|string|max:255',
                'direccion' => 'nullable|string',
                'telefono' => 'nullable|string|max:40',
                'email' => 'nullable|email|max:100',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'razon_social.required' => 'El campo razón social es obligatorio.',
                'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',
                'ruc.required' => 'El campo RUC es obligatorio.',
                'ruc.max' => 'El RUC no debe exceder los 50 caracteres.',
                'ruc.unique' => 'El RUC ya está registrado.',
                'email.email' => 'El email debe ser una dirección válida.',
                'logo.image' => 'El archivo debe ser una imagen.',
                'logo.mimes' => 'El logo debe ser un archivo de tipo: jpeg, png, jpg, gif.',
                'logo.max' => 'El logo no debe ser mayor a 2MB.',
            ]
        );

        if ($validacion->fails()) {
            Log::warning('Validación falló al crear empresa', ['errores' => $validacion->errors()->toArray()]);
            Alert::error('Error de Validación', 'Por favor revise los campos del formulario.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Manejar la subida del logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            Log::info('Subiendo logo');

            // Generar nombre único para evitar conflictos
            $extension = $request->file('logo')->getClientOriginalExtension();
            $nombreUnico = time() . '_' . uniqid() . '.' . $extension;

            // Guardar en public/img/empresas con nombre único
            $request->file('logo')->move($this->path, $nombreUnico);
            $logoPath = $nombreUnico; // Solo guardar el nombre del archivo
            Log::info('Logo subido exitosamente', ['archivo' => $logoPath]);
        }

        DB::beginTransaction();
        try {

            $result = DB::insert(
                'INSERT INTO empresas (razon_social, nombre_comercial, ruc, direccion, telefono, email, logo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $input['razon_social'],
                    $input['nombre_comercial'] ?? null,
                    $input['ruc'],
                    $input['direccion'] ?? null,
                    $input['telefono'] ?? null,
                    $input['email'] ?? null,
                    $logoPath,
                ]
            );

            Log::info('Empresa insertada exitosamente', ['resultado' => $result]);

            DB::commit();

            Alert::success('Éxito', 'Empresa creada correctamente.');
            return redirect()->route('empresas.index');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error al registrar empresa', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            // Eliminar el logo si se subió pero falló la transacción
            if (isset($logoPath) && $logoPath && file_exists($this->path . $logoPath)) {
                unlink($this->path . $logoPath);
            }

            Alert::error('Error', 'No se pudo crear el registro: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::error('Error', 'Empresa no encontrada.');
            return redirect()->route('empresas.index');
        }

        // Obtener sucursales de la empresa
        $sucursales = DB::select('SELECT * FROM sucursales WHERE id_empresa = ?', [$id]);

        return view('empresas.show')
            ->with('empresa', $empresa)
            ->with('sucursales', $sucursales);
    }

    public function edit($id)
    {
        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::error('Error', 'Empresa no encontrada.');
            return redirect()->route('empresas.index');
        }

        return view('empresas.edit')->with('empresa', $empresa);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        Log::info('Intentando actualizar empresa', ['id' => $id, 'data' => $input]);

        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::error('Error', 'Empresa no encontrada');
            return redirect()->route('empresas.index');
        }

        $validacion = Validator::make(
            $input,
            [
                'razon_social' => 'required|string|max:255',
                'ruc' => 'required|string|max:50|unique:empresas,ruc,' . $id . ',id_empresa',
                'nombre_comercial' => 'nullable|string|max:255',
                'direccion' => 'nullable|string',
                'telefono' => 'nullable|string|max:40',
                'email' => 'nullable|email|max:100',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'razon_social.required' => 'El campo razón social es obligatorio.',
                'ruc.required' => 'El campo RUC es obligatorio.',
                'ruc.unique' => 'El RUC ya está registrado.',
                'email.email' => 'El email debe ser una dirección válida.',
                'logo.image' => 'El archivo debe ser una imagen.',
                'logo.mimes' => 'El logo debe ser un archivo de tipo: jpeg, png, jpg, gif.',
                'logo.max' => 'El logo no debe ser mayor a 2MB.',
            ]
        );

        if ($validacion->fails()) {
            Log::warning('Validación falló al actualizar empresa', ['errores' => $validacion->errors()->toArray()]);
            Alert::error('Error de Validación', 'Por favor revise los campos del formulario.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Manejar la subida del nuevo logo
        $logoPath = $empresa->logo; // Mantener el logo anterior por defecto

        if ($request->hasFile('logo')) {
            Log::info('Actualizando logo');

            // Generar nombre único para evitar conflictos
            $extension = $request->file('logo')->getClientOriginalExtension();
            $nombreUnico = time() . '_' . uniqid() . '.' . $extension;

            // Eliminar el logo anterior si existe
            if ($empresa->logo && file_exists($this->path . $empresa->logo)) {
                unlink($this->path . $empresa->logo);
                Log::info('Logo anterior eliminado', ['archivo' => $empresa->logo]);
            }

            // Guardar el nuevo logo con nombre único
            $request->file('logo')->move($this->path, $nombreUnico);
            $logoPath = $nombreUnico; // Solo guardar el nombre del archivo
            Log::info('Nuevo logo subido', ['archivo' => $logoPath]);
        }

        DB::beginTransaction();
        try {

            DB::update(
                'UPDATE empresas SET razon_social = ?, nombre_comercial = ?, ruc = ?,
                direccion = ?, telefono = ?, email = ?, logo = ?, updated_at = NOW()
                WHERE id_empresa = ?',
                [
                    $input['razon_social'],
                    $input['nombre_comercial'] ?? null,
                    $input['ruc'],
                    $input['direccion'] ?? null,
                    $input['telefono'] ?? null,
                    $input['email'] ?? null,
                    $logoPath,
                    $id
                ]
            );

            Log::info('Empresa actualizada exitosamente', ['id' => $id]);

            DB::commit();

            Alert::success('Éxito', 'Empresa actualizada correctamente.');
            return redirect()->route('empresas.index');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error al actualizar empresa', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            Alert::error('Error', 'No se pudo actualizar el registro: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($id)
    {
        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::error('Error', 'Empresa no encontrada.');
            return redirect()->route('empresas.index');
        }

        try {
            // Toggle estado
            $nuevoEstado = $empresa->estado ? 'false' : 'true'; // SQL understands 'true'/'false' or 1/0
            
            DB::update('UPDATE empresas SET estado = ? WHERE id_empresa = ?', [$nuevoEstado === 'true' ? true : false, $id]);
            
            $mensaje = ($nuevoEstado === 'true') ? 'Empresa activada correctamente.' : 'Empresa inactivada correctamente.';
            Alert::success('Éxito', $mensaje);
            
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }
        return redirect()->route('empresas.index');
    }
}
