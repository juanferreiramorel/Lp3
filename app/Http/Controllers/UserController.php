<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
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
        # Listar datos de usuarios
        $users = DB::select('SELECT u.*, r.name as rol, s.descripcion as sucursal
        FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
        ORDER BY id DESC');

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

        return view('users.index')->with('users', $users);
    }

    public function create()
    {
        $roles = DB::table('roles')->pluck('name', 'id');
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');

        return view('users.create')->with('roles', $roles)->with('sucursales', $sucursales);
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
            Flash::error('Usuario no encontrado.');
            return redirect(route('users.index'));
        }

        $roles = DB::table('roles')->pluck('name', 'id');
        $sucursales = DB::table('sucursales')->pluck('descripcion', 'id_sucursal');

        return view('users.edit')->with('users', $users)->with('roles', $roles)->with('sucursales', $sucursales);
    }

    public function update(Request $request, $id) 
    {
        $input = $request->all();
        // Obtener los datos del usuario
        $users = DB::selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        if(empty($users)){
            Flash::error('Usuario no encontrado.');
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
            Flash::error('Error de validación.');
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

        Alert::toast('Usuario actualizado correctamente', 'success');

        return redirect(route('users.index'));
    }

    public function destroy($id) 
    {
        $users = DB::selectOne('SELECT * FROM users WHERE id = ?', [$id]);

        if(empty($users)){
            Flash::error('Usuario no encontrado.');
            return redirect(route('users.index'));
        }

        // validar si debe inactivar o activar segun lo recuperado en la consulta
        $estado = $users->estado == false ? true : false;

        DB::update('UPDATE users SET estado = ? WHERE id = ?', 
        [
            $estado, // valor boolean ya que el campo estado es de tipo booleano
            $id
        ]);
        
        // Generar mensaje segun estado del usuario
        $mensaje = $estado == true ? 'Usuario activado exitosamente.' : 'Usuario inactivado exitosamente.';

        Flash::success($mensaje);

        return redirect(route('users.index'));
    }

    public function perfil()
    {
        // Mostrar el perfil del usuario autenticado
        return view('users.profile');
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
