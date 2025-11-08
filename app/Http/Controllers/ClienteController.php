<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use RealRashid\SweetAlert\Facades\Alert;

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:clientes index')->only(['index']);
        $this->middleware('permission:clientes create')->only(['create', 'store']);
        $this->middleware('permission:clientes edit')->only(['edit', 'update']);
        $this->middleware('permission:clientes destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // Buscar
        $buscar = $request->get('buscar');

        if ($buscar) {
            // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
            $buscarFecha = $buscar;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscar, $matches)) {
                $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }

            // Búsqueda con formato de fecha para TO_CHAR
            $clientes = DB::select(
                'SELECT c.*, ciu.descripcion as ciudad,
                d.descripcion as departamento
                FROM clientes c
                    JOIN ciudades ciu ON ciu.id_ciudad = c.id_ciudad
                    JOIN departamentos d ON d.id_departamento = c.id_departamento
                WHERE c.clie_nombre ILIKE ?
                OR c.clie_apellido ILIKE ?
                OR c.clie_ci ILIKE ?
                OR c.clie_telefono ILIKE ?
                OR ciu.descripcion ILIKE ?
                OR d.descripcion ILIKE ?
                OR cast(c.id_cliente as text) ILIKE ?
                OR cast(c.clie_fecha_nac as text) ILIKE ?
                OR TO_CHAR(c.clie_fecha_nac, \'DD/MM/YYYY\') ILIKE ?
                ORDER BY c.id_cliente DESC',
                [
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscarFecha . '%',
                    '%' . $buscar . '%'
                ]
            );
        } else {
            $clientes = DB::select('
                SELECT c.*, ciu.descripcion as ciudad,
                d.descripcion as departamento
                FROM clientes c
                JOIN ciudades ciu ON ciu.id_ciudad = c.id_ciudad
                JOIN departamentos d ON d.id_departamento = c.id_departamento
                ORDER BY c.id_cliente DESC
            ');
        }

        // Definimos los valores de paginación
        $page = $request->input('page', 1);   // página actual (por defecto 1)
        $perPage = 10;                        // cantidad de registros por página
        $total = count($clientes);           // total de registros

        // Cortamos el array para solo devolver los registros de la página actual
        $items = array_slice($clientes, ($page - 1) * $perPage, $perPage);

        // Creamos el paginador manualmente
        $clientes = new LengthAwarePaginator(
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
        if ($request->ajax()) {
            //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
            return view('clientes.table')->with('clientes', $clientes);
        }


        return view('clientes.index')->with('clientes', $clientes);
    }

    public function create()
    {
        // Armar consultar para cargar ciudad y departamento para el select
        $ciudades = DB::table('ciudades')->pluck('descripcion', 'id_ciudad');
        $departamentos = DB::table('departamentos')->pluck('descripcion', 'id_departamento');

        return view('clientes.create')->with('ciudades', $ciudades)
            ->with('departamentos', $departamentos);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        # Obtener fecha actual
        $fec_actual = Carbon::now();
        # Parsear la fecha de nacimiento input clie_fecha_nac
        $fecha_nac = Carbon::parse($input['clie_fecha_nac']);

        $validacion = Validator::make(
            $input,
            [
                'clie_nombre.required',
                'clie_apellido.required',
                #'clie_ci.required|unique:clientes,clie_ci|max:8',
                'clie_fecha_nac.required|date',
                'id_departamento.required|exists:departamentos,id_departamento',
                'id_ciudad.required|exists:ciudades,id_ciudad',
            ],
            [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_ci.required' => 'Campo CI obligatorio',
                'clie_ci.unique' => 'El dato de CI ya existe',
                #'clie_ci.max' => 'El campo CI debe tener como máximo 8 caracteres',
                'clie_fecha_nac.required' => 'Campo fecha de nacimiento obligatorio',
                'clie_fecha_nac.date' => 'Campo fecha de nacimiento debe ser una fecha válida',
                'id_departamento.required' => 'Campo departamento obligatorio',
                'id_departamento.exists' => 'Campo departamento debe ser un departamento válido',
                'id_ciudad.required' => 'Campo ciudad obligatorio',
                'id_ciudad.exists' => 'Campo ciudad debe ser una ciudad válida',
            ]
        );

        ##validar edad del cliente
        $edad = $fec_actual->diffInYears($fecha_nac);
        if ($edad < 18) {
            Flash::error('El cliente debe ser mayor de 18 años.');
            return redirect(route('clientes.create'))->withInput();
        }

        ## validar fecha mayor al actual
        if ($fecha_nac > $fec_actual) {
            // Flash::error('La fecha de nacimiento no puede ser mayor a la fecha actual.');
            Alert::error('Error', 'La fecha de nacimiento no puede ser mayor a la fecha actual.');
            return redirect(route('clientes.create'))->withInput();
        }

        ##validar cantidad de digitos del campo ci
        $ci = strlen($input['clie_ci']); // utilizar strlen para contar caracteres
        if ($ci > 8) { # mayor a 8 caracteres
            // Flash::error('El nro de cedula solo podra contener 8 digítos.');
            Alert::error('Error', 'El nro de cedula solo podra contener 8 digítos.');
            return redirect(route('clientes.create'))->withInput();
        }

        // Validar que el CI no esté duplicado
        $existe = DB::selectOne(
            'SELECT * FROM clientes WHERE clie_ci = ?',
            [$input['clie_ci']]
        );
        if ($existe) {
            Alert::error('Error', 'Ya existe un cliente con este número de cédula.');
            return redirect(route('clientes.create'))->withInput();
        }

        # insertar datos en clientes
        DB::insert('INSERT INTO clientes (clie_nombre, clie_apellido, clie_ci, clie_telefono, clie_direccion, clie_fecha_nac, id_departamento, id_ciudad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            strtoupper($input['clie_nombre']),
            strtoupper($input['clie_apellido']),
            $input['clie_ci'],
            $input['clie_telefono'],
            strtoupper($input['clie_direccion']),
            $input['clie_fecha_nac'],
            $input['id_departamento'],
            $input['id_ciudad']
        ]);

        // Flash::success('Cliente creado correctamente.');
        Alert::success('Exito', 'Cliente creado correctamente.');

        return redirect(route('clientes.index'));
    }

    public function edit($id)
    {
        // Validar que exista el id cliente antes de procesar
        $clientes = DB::selectOne('SELECT * FROM clientes WHERE id_cliente = ?', [$id]);

        if (empty($clientes)) {
            // Flash::error('Cliente no encontrado.');
            Alert::error('Error', 'Cliente no encontrado.');
            return redirect(route('clientes.index'));
        }

        // Armar consultar para cargar ciudad y departamento para el select
        $ciudades = DB::table('ciudades')->pluck('descripcion', 'id_ciudad');
        $departamentos = DB::table('departamentos')->pluck('descripcion', 'id_departamento');

        return view('clientes.edit')->with('clientes', $clientes)
            ->with('ciudades', $ciudades)
            ->with('departamentos', $departamentos);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $clientes = DB::selectOne('SELECT * FROM clientes WHERE id_cliente = ?', [$id]);

        if (empty($clientes)) {
            // Flash::error('Cliente no encontrado.');
            Alert::error('Error', 'Cliente no encontrado.');
            return redirect(route('clientes.index'));
        }

        # Obtener fecha actual
        $fec_actual = Carbon::now();
        # Parsear la fecha de nacimiento input clie_fecha_nac
        $fecha_nac = Carbon::parse($input['clie_fecha_nac']);

        // Validaciones
        $validacion = Validator::make(
            $input,
            [
                'clie_nombre.required',
                'clie_apellido.required',
                #'clie_ci.required|unique:clientes,clie_ci|max:8',
                'clie_fecha_nac.required|date',
                'id_departamento.required|exists:departamentos,id_departamento',
                'id_ciudad.required|exists:ciudades,id_ciudad',
            ],
            [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_ci.required' => 'Campo CI obligatorio',
                'clie_ci.unique' => 'El dato de CI ya existe',
                #'clie_ci.max' => 'El campo CI debe tener como máximo 8 caracteres',
                'clie_fecha_nac.required' => 'Campo fecha de nacimiento obligatorio',
                'clie_fecha_nac.date' => 'Campo fecha de nacimiento debe ser una fecha válida',
                'id_departamento.required' => 'Campo departamento obligatorio',
                'id_departamento.exists' => 'Campo departamento debe ser un departamento válido',
                'id_ciudad.required' => 'Campo ciudad obligatorio',
                'id_ciudad.exists' => 'Campo ciudad debe ser una ciudad válida',
            ]
        );

        if ($validacion->fails()) {
            // Flash::error('Error en la validación de datos.');
            Alert::error('Error', 'Error en la validación de datos.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        ##validar edad del cliente
        $edad = $fec_actual->diffInYears($fecha_nac);
        if ($edad < 18 || $fecha_nac > $fec_actual) {
            // Flash::error('El cliente debe ser mayor de 18 años y la fecha de nacimiento no puede ser mayor a la fecha actual.');
            Alert::error('Error', 'El cliente debe ser mayor de 18 años y la fecha de nacimiento no puede ser mayor a la fecha actual.');
            return redirect()->back()->withInput();
        }

        ## validar fecha mayor al actual
        // if ($fecha_nac > $fec_actual) {
        //     Flash::error('La fecha de nacimiento no puede ser mayor a la fecha actual.');
        //     return redirect()->back()->withInput();
        // }

        ##validar cantidad de digitos del campo ci
        $ci = strlen($input['clie_ci']); // utilizar strlen para contar caracteres
        if ($ci > 8) { # mayor a 8 caracteres
            // Flash::error('El nro de cedula solo podra contener 8 digítos.');
            Alert::error('Error', 'El nro de cedula solo podra contener 8 digítos.');
            return redirect(route('clientes.edit', $id))->withInput();
        }

        // Validar que el CI no esté duplicado (excepto el registro actual)
        $existe = DB::selectOne(
            'SELECT * FROM clientes WHERE clie_ci = ? AND id_cliente != ?',
            [$input['clie_ci'], $id]
        );
        if ($existe) {
            Alert::error('Error', 'Ya existe un cliente con este número de cédula.');
            return redirect(route('clientes.edit', $id))->withInput();
        }

        DB::update(
            'UPDATE clientes SET 
            clie_nombre = ?, 
            clie_apellido = ?, 
            clie_ci = ?, 
            clie_telefono = ?, 
            clie_direccion = ?, 
            clie_fecha_nac = ?, 
            id_departamento = ?, 
            id_ciudad = ? 
        WHERE id_cliente = ?',
            [
                $input['clie_nombre'],
                $input['clie_apellido'],
                $input['clie_ci'],
                $input['clie_telefono'],
                $input['clie_direccion'],
                $input['clie_fecha_nac'],
                $input['id_departamento'],
                $input['id_ciudad'],
                $id
            ]
        );

        // Flash::success('Cliente actualizado correctamente.');
        Alert::success('Exito', 'Cliente actualizado correctamente.');
        return redirect(route('clientes.index'));
    }

    public function destroy($id)
    {
        $clientes = DB::selectOne('SELECT * FROM clientes WHERE id_cliente = ?', [$id]);

        if (empty($clientes)) {
            // Flash::error('Cliente no encontrado.');
            Alert::error('Error', 'Cliente no encontrado.');
            return redirect(route('clientes.index'));
        }
        # Utilizaremos try catch en clientes 
        try {
            DB::delete('DELETE FROM clientes WHERE id_cliente = ?', [$id]);
            // Flash::success('Cliente eliminado correctamente.');
            Alert::success('Exito', 'Cliente eliminado correctamente.');
        } catch (\Exception $e) { // excepcion capturada desde la base de datos
            Alert::error('Error', 'Error al eliminar el cliente. Por motivo: ' . $e->getMessage());
        }

        return redirect(route('clientes.index'));
    }
}
