<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ClienteController extends AppBaseController
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
        // Obtener parámetros de búsqueda
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');
        $estadoFilter = $request->get('estado_filter'); // nuevo: filtro de estado del dropdown

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // Construir la query base con subconsulta para columna calculada estado
        $query = 'SELECT * FROM (
            SELECT c.*, ciu.descripcion as ciudad,
            d.descripcion as departamento,
            CASE WHEN c.activo = true THEN \'Activo\' ELSE \'Inactivo\' END as estado
            FROM clientes c
            JOIN ciudades ciu ON ciu.id_ciudad = c.id_ciudad
            JOIN departamentos d ON d.id_departamento = c.id_departamento
        ) AS clientes_con_estado WHERE 1=1';
        $params = [];

        // Aplicar filtro de estado si existe (dropdown tiene prioridad)
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $estadoValue = ($estadoFilter === '1' || $estadoFilter === 'true') ? 'Activo' : 'Inactivo';
            $query .= ' AND estado = ?';
            $params[] = $estadoValue;
        }

        // Aplicar búsqueda por texto
        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $query .= ' AND id_cliente = ?';
                $params[] = (int)$searchConfig['term'];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];

                // Convertir fecha si está en formato dd/mm/yyyy a yyyy-mm-dd para búsqueda
                $buscarFecha = $buscarTerm;
                if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $buscarTerm, $matches)) {
                    $buscarFecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                }

                $query .= ' AND (clie_nombre ILIKE ? OR clie_apellido ILIKE ? OR clie_ci ILIKE ? OR clie_telefono ILIKE ? OR ciudad ILIKE ? OR departamento ILIKE ? OR cast(id_cliente as text) ILIKE ? OR cast(clie_fecha_nac as text) ILIKE ? OR estado ILIKE ?)';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarTerm . '%';
                $params[] = '%' . $buscarFecha . '%';
                $params[] = '%' . $buscarTerm . '%';
            }
        }

        $query .= ' ORDER BY id_cliente DESC';

        // Ejecutar la consulta
        $clientes = DB::select($query, $params);

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

        // Si es petición AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('clientes.table')->with('clientes', $clientes);
        }

        return view('clientes.index')
            ->with('clientes', $clientes)
            ->with('estadoFilter', $estadoFilter);
    }

    public function create()
    {
        // Armar consultar para cargar ciudad y departamento para el select
        // Usar helper del AppBaseController para traer ciudades activas
        $ciudades = $this->getCiudadesConDepartamento();
        $departamentos = $this->getDepartamentosDropdown();

        return view('clientes.create')->with('ciudades', $ciudades)
            ->with('departamentos', $departamentos);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validacion = Validator::make(
            $input,
            [
                'clie_nombre' => 'required',
                'clie_apellido' => 'required',
                'clie_ci' => 'required',
                'clie_fecha_nac' => 'required|date',
                'id_departamento' => 'required|exists:departamentos,id_departamento',
                'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            ],
            [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_ci.required' => 'Campo CI obligatorio',
                'clie_ci.unique' => 'El dato de CI ya existe',
                'clie_fecha_nac.required' => 'Campo fecha de nacimiento obligatorio',
                'clie_fecha_nac.date' => 'Campo fecha de nacimiento debe ser una fecha válida',
                'id_departamento.required' => 'Campo departamento obligatorio',
                'id_departamento.exists' => 'Campo departamento debe ser un departamento válido',
                'id_ciudad.required' => 'Campo ciudad obligatorio',
                'id_ciudad.exists' => 'Campo ciudad debe ser una ciudad válida',
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', 'Error en la validación de datos.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        # Obtener fecha actual
        $fec_actual = Carbon::now();
        # Parsear la fecha de nacimiento input clie_fecha_nac
        $fecha_nac = Carbon::parse($input['clie_fecha_nac']);

        ##validar edad del cliente
        $edad = $fec_actual->diffInYears($fecha_nac);
        if ($edad < 18) {
            Alert::error('Error', 'El cliente debe ser mayor de 18 años.');
            return redirect(route('clientes.create'))->withInput();
        }

        ## validar fecha mayor al actual
        if ($fecha_nac > $fec_actual) {
            Alert::error('Error', 'La fecha de nacimiento no puede ser mayor a la fecha actual.');
            return redirect(route('clientes.create'))->withInput();
        }

        ##validar cantidad de digitos del campo ci
        $ci = strlen($input['clie_ci']); // utilizar strlen para contar caracteres
        if ($ci < 4) { # mayor a 8 caracteres
            Alert::error('Error', 'El nro de cedula solo podra contener minimo 4 digitos.');
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
            isset($input['clie_telefono']) ? $input['clie_telefono'] : null,
            isset($input['clie_direccion']) ? strtoupper($input['clie_direccion']) : null,
            $input['clie_fecha_nac'],
            $input['id_departamento'],
            $input['id_ciudad']
        ]);

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
        // Usar helper del AppBaseController incluyendo la ciudad actual del cliente
        $ciudades = $this->getCiudadesConDepartamento($clientes->id_ciudad);
        $departamentos = $this->getDepartamentosDropdown($clientes->id_departamento);

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

        // Validaciones
        $validacion = Validator::make(
            $input,
            [
                'clie_nombre' => 'required',
                'clie_apellido' => 'required',
                'clie_ci' => 'required',
                'clie_fecha_nac' => 'required|date',
                'id_departamento' => 'required|exists:departamentos,id_departamento',
                'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            ],
            [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_ci.required' => 'Campo CI obligatorio',
                'clie_ci.unique' => 'El dato de CI ya existe',
                'clie_fecha_nac.required' => 'Campo fecha de nacimiento obligatorio',
                'clie_fecha_nac.date' => 'Campo fecha de nacimiento debe ser una fecha válida',
                'id_departamento.required' => 'Campo departamento obligatorio',
                'id_departamento.exists' => 'Campo departamento debe ser un departamento válido',
                'id_ciudad.required' => 'Campo ciudad obligatorio',
                'id_ciudad.exists' => 'Campo ciudad debe ser una ciudad válida',
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', 'Error en la validación de datos.');
            return redirect()->back()->withErrors($validacion)->withInput();
        }

        # Obtener fecha actual
        $fec_actual = Carbon::now();
        # Parsear la fecha de nacimiento input clie_fecha_nac
        $fecha_nac = Carbon::parse($input['clie_fecha_nac']);

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
        if ($ci < 4) { # mayor a 8 caracteres
            // Flash::error('El nro de cedula solo podra contener 8 digítos.');
            Alert::error('Error', 'El nro de cedula solo podra contener minimo 4 digitos.');
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
                strtoupper($input['clie_nombre']),
                strtoupper($input['clie_apellido']),
                $input['clie_ci'],
                isset($input['clie_telefono']) ? $input['clie_telefono'] : null,
                isset($input['clie_direccion']) ? strtoupper($input['clie_direccion']) : null,
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
            Alert::error('Error', 'Cliente no encontrado.');
            return redirect(route('clientes.index'));
        }

        try {
            // Cambiar el estado activo (toggle)
            $nuevoEstado = !$clientes->activo;
            DB::update('UPDATE clientes SET activo = ? WHERE id_cliente = ?', [$nuevoEstado, $id]);
            
            $mensaje = $nuevoEstado ? 'Cliente activado correctamente.' : 'Cliente inactivado correctamente.';
            Alert::success('Éxito', $mensaje);
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado: ' . $e->getMessage());
        }

        return redirect(route('clientes.index'));
    }

    /**
     * Store a newly created cliente via AJAX
     */
    public function storeAjax(Request $request)
    {
        $input = $request->all();

        // Validación
        $validator = Validator::make(
            $input,
            [
                'clie_nombre' => 'required',
                'clie_apellido' => 'required',
                'clie_ci' => 'required',
                'clie_fecha_nac' => 'required|date',
                'id_departamento' => 'required|exists:departamentos,id_departamento',
                'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            ],
            [
                'clie_nombre.required' => 'Campo nombre obligatorio',
                'clie_apellido.required' => 'Campo apellido obligatorio',
                'clie_ci.required' => 'Campo CI obligatorio',
                'clie_fecha_nac.required' => 'Campo fecha de nacimiento obligatorio',
                'clie_fecha_nac.date' => 'Campo fecha de nacimiento debe ser una fecha válida',
                'id_departamento.required' => 'Campo departamento obligatorio',
                'id_departamento.exists' => 'Campo departamento debe ser un departamento válido',
                'id_ciudad.required' => 'Campo ciudad obligatorio',
                'id_ciudad.exists' => 'Campo ciudad debe ser una ciudad válida',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar edad del cliente
        $fec_actual = Carbon::now();
        $fecha_nac = Carbon::parse($input['clie_fecha_nac']);
        $edad = $fec_actual->diffInYears($fecha_nac);
        if ($edad < 18) {
            return response()->json([
                'success' => false,
                'errors' => ['clie_fecha_nac' => ['El cliente debe ser mayor de 18 años.']]
            ], 422);
        }

        // Validar fecha mayor al actual
        if ($fecha_nac > $fec_actual) {
            return response()->json([
                'success' => false,
                'errors' => ['clie_fecha_nac' => ['La fecha de nacimiento no puede ser mayor a la fecha actual.']]
            ], 422);
        }

        // Validar cantidad de dígitos del campo CI
        $ci = strlen($input['clie_ci']);
        if ($ci < 4) {
            return response()->json([
                'success' => false,
                'errors' => ['clie_ci' => ['El nro de cedula solo podra contener minimo 4 digitos.']]
            ], 422);
        }

        // Validar que el CI no esté duplicado
        $existe = DB::selectOne(
            'SELECT * FROM clientes WHERE clie_ci = ?',
            [$input['clie_ci']]
        );
        if ($existe) {
            return response()->json([
                'success' => false,
                'errors' => ['clie_ci' => ['Ya existe un cliente con este número de cédula.']]
            ], 422);
        }

        // Insertar datos en clientes y obtener el registro creado
        $nuevoCliente = DB::selectOne(
            'INSERT INTO clientes (clie_nombre, clie_apellido, clie_ci, clie_telefono, clie_direccion, clie_fecha_nac, id_departamento, id_ciudad)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id_cliente, clie_nombre, clie_apellido, clie_ci',
            [
                strtoupper($input['clie_nombre']),
                strtoupper($input['clie_apellido']),
                $input['clie_ci'],
                $input['clie_telefono'] ?? '',
                strtoupper($input['clie_direccion'] ?? ''),
                $input['clie_fecha_nac'],
                $input['id_departamento'],
                $input['id_ciudad']
            ]
        );

        return response()->json([
            'success' => true,
            'cliente' => [
                'id_cliente' => $nuevoCliente->id_cliente,
                'nombre_completo' => $nuevoCliente->clie_nombre . ' ' . $nuevoCliente->clie_apellido . ' - ' . $nuevoCliente->clie_ci
            ]
        ]);
    }
}
