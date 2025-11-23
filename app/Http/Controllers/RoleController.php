<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Spatie\Permission\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:roles index')->only(['index']);
        $this->middleware('permission:roles create')->only(['create', 'store']);
        $this->middleware('permission:roles edit')->only(['edit', 'update']);
        $this->middleware('permission:roles destroy')->only(['destroy']);
    }
    public function index(Request $request)
    {
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Construir consulta con filtros de búsqueda por todos los campos visibles
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                $roles = DB::select(
                    'SELECT * FROM roles WHERE id = ? ORDER BY id DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                $buscarTerm = $searchConfig['term'];
                $roles = DB::select(
                    'SELECT * FROM roles
                    WHERE CAST(id AS TEXT) ILIKE ?
                    OR name ILIKE ?
                    OR guard_name ILIKE ?
                    ORDER BY id DESC',
                    [
                        '%' . $buscarTerm . '%',
                        '%' . $buscarTerm . '%',
                        '%' . $buscarTerm . '%'
                    ]
                );
            }
        } else {
            $roles = DB::select('SELECT * FROM roles ORDER BY id DESC');
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($roles);              // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($roles, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $roles = new LengthAwarePaginator(
            $items,        // registros de esta página
            $total,        // total de registros
            $perPage,      // registros por página
            $page,         // página actual
            [
                'path'  => $request->url(),     // mantiene la ruta base
                'query' => $request->query(),   // mantiene parámetros como "buscar"
            ]
        );

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('roles.table')->with('roles', $roles);
        }

        return view('roles.index')->with('roles', $roles);
    }

    public function create()
    {
        // obtener todos los permisos
        $permisos = DB::table('permissions')->get();

        return view('roles.create')->with('permisos', $permisos);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $input['guard_name'] = 'web';

        // capturar lo seleccionado de la tabla permisos
        $permissions = $request->input('permiso_id', []);

        // validaciones
        $validateData = Validator::make($input, [
            'name' => 'required|unique:roles,name',
        ], [
            'name.required' => 'El campo nombre es obligatorio',
            'name.unique' => 'El nombre del rol ya existe'
        ]);
        // si falla la validacion
        if ($validateData->fails()) {
            return redirect()->back()->withErrors($validateData)->withInput();
        }

        // crear el insert y capturar el id del rol creado
        $role_id = DB::table('roles')->insertGetId(
            [
                'name' => $input['name'],
                'guard_name' => $input['guard_name']
            ]
        );


        // asignar permisos al rol
        $role = Role::find($role_id);
        // $role = DB::selectOne('SELECT * FROM roles WHERE id = ?', [$role_id]);
        // asignar permisos al rol utilizando syncPermissions
        $role->permissions()->sync($permissions);
        // Limpiar la caché de permisos y roles
        Artisan::call('optimize:clear');

        Alert::toast('Rol creado correctamente', 'success');
        return redirect()->route('roles.index');
    }

    public function edit($id)
    {
        // obtener el rol por su id
        // $role = DB::selectOne('SELECT * FROM roles WHERE id = ?', [$id]);
        // Utilizando find del modelo Role librería Spatie
        $role = Role::find($id);

        if(empty($role)) {
            Alert::toast('Rol no encontrado', 'error');
            return redirect()->route('roles.index');
        }
        // obtener todos los permisos
        $permisos = DB::table('permissions')->get();

        // obtener los permisos asignados al rol
        $rolePermissions = DB::select('SELECT permission_id FROM role_has_permissions WHERE role_id = ?', [$id]);

        // crear un array con los ids de los permisos asignados al rol
        $rolePermissionIds = [];
        foreach ($rolePermissions as $permission) {
            $rolePermissionIds[] = $permission->permission_id;
        }

        return view('roles.edit')->with('roles', $role)
                                 ->with('permisos', $permisos)
                                 ->with('rolePermissionIds', $rolePermissionIds);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $input['guard_name'] = 'web';

        // capturar lo seleccionado de la tabla permisos
        $permissions = $request->get('permiso_id', []);

        // validaciones
        $validateData = Validator::make($input, [
            'name' => 'required|unique:roles,name,'.$id,
        ], [
            'name.required' => 'El campo nombre es obligatorio',
            'name.unique' => 'El nombre del rol ya existe'
        ]);

        if ($validateData->fails()) {
            return redirect()->back()->withErrors($validateData)->withInput();
        }

        // actualizar el rol
        DB::update('UPDATE roles SET name = ?, guard_name = ? WHERE id = ?', [
            $input['name'], 
            $input['guard_name'],
            $id
        ]);

        // asignar permisos al rol utilizando syncPermissions
        $role = Role::find($id);
        $role->permissions()->sync($permissions);
        // Limpiar la caché de permisos y roles
        Artisan::call('optimize:clear');

        Alert::toast('Rol actualizado correctamente', 'success');
        return redirect()->route('roles.index');
    }

    public function destroy($id)
    {
        $role = DB::selectOne('SELECT * FROM roles WHERE id = ?', [$id]);
        if(empty($role)) {
            Alert::error('Error', 'Rol no encontrado.');
            return redirect()->route('roles.index');
        }

        try {
            DB::delete('DELETE FROM roles WHERE id = ?', [$id]);
            Alert::success('Éxito', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
            return redirect()->route('roles.index');
        }

        return redirect()->route('roles.index');
    }
}
