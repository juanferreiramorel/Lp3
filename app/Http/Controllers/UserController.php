<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends AppBaseController
{
    public function __construct()
    {
        // validar que el usuario este autenticado
        $this->middleware('auth');
        // validar permisos para cada accion
        $this->middleware('permission:users index')->only(['index']);
        $this->middleware('permission:users create')->only(['create', 'store']);
        $this->middleware('permission:users edit')->only(['edit', 'update']);
        $this->middleware('permission:users destroy')->only(['destroy']);
    }
    public function index(Request $request)
    {
        // Obtener el término de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir consulta con filtros de búsqueda
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $users = DB::select(
                    'SELECT u.*, r.name as rol, s.descripcion as sucursal
                    FROM users u
                        LEFT JOIN roles r ON u.role_id = r.id
                        LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                    WHERE u.id = ?
                    ORDER BY u.id DESC',
                    [(int)$searchConfig['term']]
                );
            } else {
                // Búsqueda general en múltiples campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd
                $buscarFecha = $buscarTerm;
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                $users = DB::select(
                    'SELECT u.*, r.name as rol, s.descripcion as sucursal
                    FROM users u
                        LEFT JOIN roles r ON u.role_id = r.id
                        LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                    WHERE cast(u.id as text) ILIKE ?
                    OR u.name ILIKE ?
                    OR u.email ILIKE ?
                    OR u.ci ILIKE ?
                    OR u.telefono ILIKE ?
                    OR cast(u.fecha_ingreso as text) ILIKE ?
                    OR TO_CHAR(u.fecha_ingreso, \'DD/MM/YYYY\') ILIKE ?
                    OR r.name ILIKE ?
                    OR s.descripcion ILIKE ?
                    ORDER BY u.id DESC',
                    ['%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarFecha . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%', '%' . $buscarTerm . '%']
                );
            }
        } else {
            $users = DB::select('SELECT u.*, r.name as rol, s.descripcion as sucursal
            FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
            ORDER BY u.id DESC');
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($users);              // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($users, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $users = new LengthAwarePaginator(
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
            return view('users.table')->with('users', $users);
        }

        return view('users.index')->with('users', $users);
    }

    public function create()
    {
        $roles = DB::table('roles')->pluck('name', 'id');
        $sucursales = $this->buildSucursalesDisponibles([], true);

        return view('users.create')
            ->with('roles', $roles)
            ->with('sucursales', $sucursales)
            ->with('sucursalesAsignadas', []);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // Validar los datos del formulario
        $validacion = Validator::make($input, [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required|min:6',
            'ci' => 'required|unique:users',
            'fecha_ingreso' => 'required|date',
            'role_id' => 'required|exists:roles,id',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
        ], [
            'name.required' => 'El campo Nombre es obligatorio.',
            'email.required' => 'El campo Email es obligatorio.',
            'email.unique' => 'El nickname ya está en uso.',
            'password.required' => 'El campo Contraseña es obligatorio.',
            'password.min' => 'La Contraseña debe tener al menos 6 caracteres.',
            'ci.required' => 'El campo Nro Documento es obligatorio.',
            'ci.unique' => 'El Nro Documento ya está en uso.',
            'fecha_ingreso.required' => 'El campo Fecha de Ingreso es obligatorio.',
            'fecha_ingreso.date' => 'El campo Fecha de Ingreso debe ser una fecha válida.',
            'role_id.required' => 'El campo Rol es obligatorio.',
            'role_id.exists' => 'El Rol seleccionado no es válido.',
            'id_sucursal.required' => 'El campo Sucursal es obligatorio.',
            'id_sucursal.exists' => 'La Sucursal seleccionada no es válida.',
        ]);

        if ($validacion->fails()) {
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Si la validación pasa, guardar el usuario
        $contrasena = Hash::make($input['password']);

        // Insertar el nuevo usuario en la base de datos utilizando el modelo User
        $usuario = new User();
        $usuario->role_id = $input['role_id'];
        $usuario->name = $input['name'];
        $usuario->email = $input['email'];
        $usuario->password = $contrasena;
        $usuario->ci = $input['ci'];
        $usuario->direccion = $input['direccion'] ?? null;
        $usuario->telefono = $input['telefono'] ?? null;
        $usuario->fecha_ingreso = $input['fecha_ingreso'];
        $usuario->id_sucursal = $input['id_sucursal'];
        $usuario->save();
        // Asignar el rol al usuario
        $usuario->roles()->sync($input['role_id']);

        $sucursalesPermitidas = collect($input['sucursales_permitidas'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (!in_array((int) $usuario->id_sucursal, $sucursalesPermitidas, true)) {
            $sucursalesPermitidas[] = (int) $usuario->id_sucursal;
        }

        $usuario->sucursalesPermitidas()->sync($sucursalesPermitidas);

        // DB::insert('INSERT INTO users (name, email, password, ci, direccion, telefono, fecha_ingreso) 
        // VALUES (?, ?, ?, ?, ?, ?, ?)', [
        //     $input['name'],
        //     $input['email'],
        //     $contrasena,
        //     $input['ci'],
        //     $input['direccion'],
        //     $input['telefono'],
        //     $input['fecha_ingreso'],
        // ]);

        Alert::toast('Usuario creado correctamente', 'success');

        return redirect(route('users.index'));
    }

    public function edit($id)
    {
        // Obtener los datos del usuario
        $users = DB::selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        if(empty($users)){
            Alert::error('Error', 'Usuario no encontrado.');
            return redirect(route('users.index'));
        }

        $roles = DB::table('roles')->pluck('name', 'id');
        $sucursalesAsignadas = DB::table('sucursal_user')
            ->where('user_id', $id)
            ->pluck('id_sucursal')
            ->map(fn ($sucursal) => (int) $sucursal)
            ->toArray();

        if (!in_array((int) $users->id_sucursal, $sucursalesAsignadas, true)) {
            $sucursalesAsignadas[] = (int) $users->id_sucursal;
        }

        $sucursales = $this->buildSucursalesDisponibles($sucursalesAsignadas, true);

        return view('users.edit')
            ->with('users', $users)
            ->with('roles', $roles)
            ->with('sucursales', $sucursales)
            ->with('sucursalesAsignadas', $sucursalesAsignadas);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        // Obtener los datos del usuario
        $users = DB::selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        if(empty($users)){
            Alert::error('Error', 'Usuario no encontrado.');
            return redirect(route('users.index'));
        }

        $validacion = Validator::make($input, [
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $users->id, // Excluir el usuario actual de la validación
            'password' => 'nullable|min:6',
            'ci' => 'required|unique:users,ci,' . $users->id, // Excluir el ci del usuario actual de la validacion
            'fecha_ingreso' => 'required|date',
            'role_id' => 'required|exists:roles,id',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
        ], [
            'name.required' => 'El campo Nombre es obligatorio.',
            'email.required' => 'El campo Email es obligatorio.',
            'email.unique' => 'El nickname ya está en uso.',
            'password.min' => 'La Contraseña debe tener al menos 6 caracteres.',
            'ci.required' => 'El campo Nro Documento es obligatorio.',
            'ci.unique' => 'El Nro Documento ya está en uso.',
            'fecha_ingreso.required' => 'El campo Fecha de Ingreso es obligatorio.',
            'fecha_ingreso.date' => 'El campo Fecha de Ingreso debe ser una fecha válida.',
            'role_id.required' => 'El campo Rol es obligatorio.',
            'role_id.exists' => 'El Rol seleccionado no es válido.',
            'id_sucursal.required' => 'El campo Sucursal es obligatorio.',
            'id_sucursal.exists' => 'La Sucursal seleccionada no es válida.',
        ]);

        if ($validacion->fails()) {
            Alert::error('Error', 'Error de validación.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        // Validar si la contraseña ha sido cambiada
        $contrasena = null;
        if(!empty($input['password']) && !Hash::check($input['password'], $users->password)) {
            $contrasena = Hash::make($input['password']); // Encriptar la contraseña
        }else{
            $contrasena = $users->password; // Mantener la contraseña sin encriptar
        }

        // Actualizar el usuario utilizando el modelo User
        $usuario = User::where('id', $id)->first();// recuperar el usuario a actualizar
        $usuario->role_id = $input['role_id'];
        $usuario->name = $input['name'];
        $usuario->email = $input['email'];
        $usuario->password = $contrasena;
        $usuario->ci = $input['ci'];
        $usuario->direccion = $input['direccion'] ?? null;
        $usuario->telefono = $input['telefono'] ?? null;
        $usuario->fecha_ingreso = $input['fecha_ingreso'];
        $usuario->id_sucursal = $input['id_sucursal'];
        $usuario->save();
        // Asignar el rol al usuario
        $usuario->roles()->sync($input['role_id']);

        $sucursalesPermitidas = collect($input['sucursales_permitidas'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (!in_array((int) $usuario->id_sucursal, $sucursalesPermitidas, true)) {
            $sucursalesPermitidas[] = (int) $usuario->id_sucursal;
        }

        $usuario->sucursalesPermitidas()->sync($sucursalesPermitidas);

        Alert::toast('Usuario actualizado correctamente', 'success');

        return redirect(route('users.index'));
    }

    /**
     * Construye el listado de sucursales disponibles para el usuario autenticado,
     * garantizando que los IDs adicionales proporcionados también estén presentes.
     *
     * @param  array<int,int>  $idsExtra
     * @param  bool  $listarTodas  Forzar listado completo (ignora restricciones)
     * @return \Illuminate\Support\Collection
     */
    protected function buildSucursalesDisponibles(array $idsExtra = [], bool $listarTodas = false)
    {
        // Sanitizar idsExtra
        $includeIds = collect($idsExtra)
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        // Si se solicita listar todas (común en create/edit de usuarios para admins)
        if ($listarTodas) {
            return $this->getTodasSucursalesActivas($includeIds)
                ->sortBy(fn ($descripcion) => Str::lower($descripcion));
        }

        // Si se respetan los permisos del usuario actual
        // Nota: Esto aplicará filtros de permisos a los IDs incluidos también.
        return $this->getSucursalesPermitidas(null, $includeIds)
            ->sortBy(fn ($descripcion) => Str::lower($descripcion));
    }

    public function destroy($id)
    {
        $users = DB::selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        if(empty($users)){
            Alert::error('Error', 'Usuario no encontrado.');
            return redirect(route('users.index'));
        }

        try {
            // validar si debe inactivar o activar segun lo recuperado en la consulta
            $estado = $users->estado == false ? true : false;

            DB::update('UPDATE users SET estado = ? WHERE id = ?',
            [
                $estado, // valor boolean ya que el campo estado es de tipo booleano
                $id
            ]);

            // Generar mensaje segun estado del usuario
            $mensaje = $estado == true ? 'Usuario activado exitosamente.' : 'Registro eliminado correctamente.';

            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
        }

        return redirect(route('users.index'));
    }

    public function perfil()
    {
        // Lista de rutas disponibles para accesos directos
        $availableShortcuts = [
            'ventas.index' => 'Ventas',
            'apertura-cierre-caja.index' => 'Arqueo de Caja',
            'pedidos.index' => 'Pedidos',
            'clientes.index' => 'Clientes',
            'cuentasacobrar.index' => 'Cuentas a Cobrar',
            'recibossimples.index' => 'Recibos Simples',
            'compras.index' => 'Compras',
            'ordencompra.index' => 'Ordenes de Compra',
            'proveedores.index' => 'Proveedores',
            'cuentasapagar.index' => 'Cuentas a Pagar',
            'productos.index' => 'Productos',
            'stock.index' => 'Stock',
            'marcas.index' => 'Marcas',
            'plantillas_contratos.index' => 'Plantillas Contratos',
            'users.index' => 'Usuarios',
            'sucursales.index' => 'Sucursales',
        ];

        // Mostrar el perfil del usuario autenticado
        return view('users.profile', compact('availableShortcuts'));
    }

    public function updatePerfil(Request $request)
    {
        $user = auth()->user();
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shortcut_mode' => 'required|in:automatic,manual',
            'shortcuts' => 'nullable|array|max:5',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif.',
            'avatar.max' => 'La imagen no debe pesar más de 2048 kilobytes.',
            'shortcuts.max' => 'No puedes seleccionar más de 5 accesos directos.',
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Error de validación');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update basic info
        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->direccion = $input['direccion'] ?? null;
        $user->telefono = $input['telefono'] ?? null;
        
        // Handle Avatar Deletion
        if ($request->has('delete_avatar') && $request->delete_avatar == '1') {
            if ($user->avatar_url) {
                // Determine file path from keys or URL logic if needed. 
                // Since we store full URL, we might need to parse it or just rely on new upload overwriting.
                // ideally we delete the old file.
                $oldPath = public_path('avatars/' . basename($user->avatar_url));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                $user->avatar_url = null;
            }
        }

        // Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            $imageName = time() . '.' . $request->avatar->extension();
            $request->avatar->move(public_path('avatars'), $imageName);
            $user->avatar_url = asset('avatars/' . $imageName);
        }

        // Handle Shortcuts
        $user->shortcut_mode = $input['shortcut_mode'];
        
        if ($input['shortcut_mode'] === 'manual') {
            $user->shortcuts = isset($input['shortcuts']) ? json_encode($input['shortcuts']) : null;
        }

        $user->save();

        Alert::toast('Perfil actualizado correctamente', 'success');
        return redirect()->back();
    }

    public function cambiarPassword(Request $request)
    {
        $input = $request->all();

        ##validar datos de contraseña utilizando validate de laravel
        $validator = Validator::make(
            $request->all(),
            [
                'password' => 'required|min:6',
                'confirm-password' => 'required|same:password',
            ],
            [
                'password.required'         => 'La contraseña es requerida',
                'password.min'              => 'Debe tener al menos 6 digítos la contraseña',
                'confirm-password.required' => 'La confirmación de contraseña es requerida',
                'confirm-password.same'     => 'Las contraseñas no coinciden',
            ]
        );

        # verificar si la validación falla
        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        #Actualizar la contraseña del usuario en session
        DB::update('UPDATE users SET password = ? WHERE id = ?', 
        [
            Hash::make($input['password']), // utilizar Hash para encriptar la contraseña
            auth()->user()->id
        ]);

        // mostrar mensaje de exito
        Alert::toast('Contraseña actualizada correctamente.!', 'success');

        // quedarse en el mismo formulario profile
        return redirect()->back();
    }

}
