<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class EmpresaController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:empresas index')->only(['index']);
        $this->middleware('permission:empresas create')->only(['create', 'store']);
        $this->middleware('permission:empresas edit')->only(['edit', 'update']);
        $this->middleware('permission:empresas destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $sql_busqueda = 'WHERE 1=1';

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $sql_busqueda = "WHERE e.id_empresa = " . (int)$searchConfig['term'];
            } else {
                $buscarTerm = $searchConfig['term'];
                $sql_busqueda = "WHERE (CAST(e.id_empresa AS TEXT) iLIKE '%{$buscarTerm}%'
                    OR e.razon_social ILIKE '%{$buscarTerm}%'
                    OR e.nombre_comercial ILIKE '%{$buscarTerm}%'
                    OR e.ruc ILIKE '%{$buscarTerm}%')";
            }
        }

        $empresas = DB::select(
            "SELECT e.* FROM empresas e
            {$sql_busqueda}
            ORDER BY e.id_empresa DESC"
        );

        if ($request->ajax()) {
            return view('empresas.table')->with('empresas', $empresas);
        }

        return view('empresas.index')->with('empresas', $empresas);
    }

    public function create()
    {
        return view('empresas.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validacion = Validator::make(
            $input,
            [
                'razon_social' => 'required|string|max:255',
                'ruc' => 'required|string|max:50|unique:empresas,ruc',
                'nombre_comercial' => 'nullable|string|max:255',
                'direccion' => 'nullable|string',
                'telefono' => 'nullable|string|max:40',
                'email' => 'nullable|email|max:100',
                'logo' => 'nullable|string|max:255',
            ],
            [
                'razon_social.required' => 'El campo razón social es obligatorio.',
                'razon_social.max' => 'La razón social no debe exceder los 255 caracteres.',
                'ruc.required' => 'El campo RUC es obligatorio.',
                'ruc.max' => 'El RUC no debe exceder los 50 caracteres.',
                'ruc.unique' => 'El RUC ya está registrado.',
                'email.email' => 'El email debe ser una dirección válida.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        DB::beginTransaction();
        try {
            DB::insert(
                'INSERT INTO empresas (razon_social, nombre_comercial, ruc, direccion, telefono, email, logo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $input['razon_social'],
                    $input['nombre_comercial'] ?? null,
                    $input['ruc'],
                    $input['direccion'] ?? null,
                    $input['telefono'] ?? null,
                    $input['email'] ?? null,
                    $input['logo'] ?? null,
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Alert::toast('Error al registrar la empresa: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput();
        }

        Alert::toast('Empresa registrada exitosamente.', 'success');
        return redirect()->route('empresas.index');
    }

    public function show($id)
    {
        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::toast('Empresa no encontrada', 'error');
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
            Alert::toast('Empresa no encontrada', 'error');
            return redirect()->route('empresas.index');
        }

        return view('empresas.edit')->with('empresa', $empresa);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::toast('Empresa no encontrada', 'error');
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
                'logo' => 'nullable|string|max:255',
            ],
            [
                'razon_social.required' => 'El campo razón social es obligatorio.',
                'ruc.required' => 'El campo RUC es obligatorio.',
                'ruc.unique' => 'El RUC ya está registrado.',
                'email.email' => 'El email debe ser una dirección válida.',
            ]
        );

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
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
                    $input['logo'] ?? null,
                    $id
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Alert::toast('Error al actualizar la empresa: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput();
        }

        Alert::toast('Empresa actualizada exitosamente.', 'success');
        return redirect()->route('empresas.index');
    }

    public function destroy($id)
    {
        $empresa = DB::selectOne('SELECT * FROM empresas WHERE id_empresa = ?', [$id]);

        if (empty($empresa)) {
            Alert::toast('Empresa no encontrada', 'error');
            return redirect()->route('empresas.index');
        }

        // Verificar si tiene sucursales asociadas
        $sucursales = DB::selectOne('SELECT COUNT(*) as total FROM sucursales WHERE id_empresa = ?', [$id]);

        if ($sucursales->total > 0) {
            Alert::toast('No se puede eliminar la empresa porque tiene sucursales asociadas', 'error');
            return redirect()->route('empresas.index');
        }

        DB::beginTransaction();
        try {
            DB::delete('DELETE FROM empresas WHERE id_empresa = ?', [$id]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Alert::toast('Error al eliminar la empresa: ' . $e->getMessage(), 'error');
            return redirect()->route('empresas.index');
        }

        Alert::toast('Empresa eliminada exitosamente.', 'success');
        return redirect()->route('empresas.index');
    }
}
