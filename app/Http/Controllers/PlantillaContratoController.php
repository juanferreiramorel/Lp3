<?php

namespace App\Http\Controllers;

use App\Models\PlantillaContrato;
use App\Models\ContratoGenerado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;
use Barryvdh\DomPDF\Facade\Pdf;

class PlantillaContratoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:plantillas_contratos index')->only(['index']);
        $this->middleware('permission:plantillas_contratos create')->only(['create', 'store']);
        $this->middleware('permission:plantillas_contratos edit')->only(['edit', 'update']);
        $this->middleware('permission:plantillas_contratos destroy')->only(['destroy']);
    }

    /**
     * Mostrar listado de plantillas de contratos
     */
    public function index(Request $request)
    {
        // Buscar
        $buscar = $request->get('buscar');
        $searchMode = $request->get('search_mode', 'all');

        // Procesar modo de búsqueda
        $searchConfig = $this->parseSearchMode($buscar, $searchMode);

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'pc.id_plantilla_contrato', 'field' => 'id_plantilla_contrato', 'first' => 'desc'],
            'nombre' => ['label' => 'Nombre Plantilla', 'sort' => 'pc.nombre_plantilla', 'field' => 'nombre_plantilla', 'tiebreaker' => 'pc.id_plantilla_contrato'],
            'descripcion' => ['label' => 'Descripción', 'sort' => 'pc.descripcion', 'tiebreaker' => 'pc.id_plantilla_contrato'],
            'estado' => ['label' => 'Estado', 'sort' => 'pc.activo', 'field' => 'activo', 'format' => 'estado', 'tiebreaker' => 'pc.id_plantilla_contrato'],
            'usuario' => ['label' => 'Usuario', 'sort' => 'u.name', 'field' => 'usuario_nombre', 'tiebreaker' => 'pc.id_plantilla_contrato'],
            'fecha_modificacion' => ['label' => 'Última Modificación', 'sort' => 'pc.fecha_modificacion', 'format' => 'datetime', 'tiebreaker' => 'pc.id_plantilla_contrato', 'first' => 'desc'],
            'acciones' => ['label' => 'Acción', 'width' => '180px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'fecha_modificacion', 'desc', $request);

        // One query, three possible WHERE clauses: the search mode only decides
        // the filter and its bindings, so the export can reuse all of it.
        $seleccion = 'SELECT pc.*, u.name as usuario_nombre
            FROM plantillas_contratos pc
            LEFT JOIN users u ON u.id = pc.user_id';
        $parametros = [];

        if ($searchConfig) {
            if ($searchConfig['mode'] === 'id') {
                // Búsqueda exacta por ID
                $seleccion .= '
                    WHERE pc.id_plantilla_contrato = ?';
                $parametros = [(int)$searchConfig['term']];
            } else {
                // Búsqueda múltiple en todos los campos
                $buscarTerm = $searchConfig['term'];
                $seleccion .= '
                    WHERE pc.nombre_plantilla ILIKE ?
                    OR pc.descripcion ILIKE ?
                    OR pc.contenido_plantilla ILIKE ?
                    OR u.name ILIKE ?
                    OR cast(pc.id_plantilla_contrato as text) ILIKE ?';
                $parametros = [
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%',
                    '%' . $buscarTerm . '%'
                ];
            }
        }

        $seleccion .= '
            ORDER BY ' . $sort->orderBy();

        // Exports run the same query without the page window, so the file
        // carries every row the current search matches, not just this page.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(
                fn () => DB::select($seleccion . ' LIMIT ' . \App\Support\TableExport::MAX_ROWS, $parametros),
                'plantillas_contratos'
            );
        }

        $plantillas = DB::select($seleccion, $parametros);

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($plantillas);

        $items = array_slice($plantillas, ($page - 1) * $perPage, $perPage);

        $plantillas = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Si es petición AJAX, retornar solo la tabla
        if ($request->ajax()) {
            return view('plantillas_contratos.table')
                ->with('plantillas', $plantillas)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('plantillas_contratos.index')
            ->with('plantillas', $plantillas)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $palabrasClave = PlantillaContrato::getPalabrasClave();
        return view('plantillas_contratos.create')->with('palabrasClave', $palabrasClave);
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validacion = Validator::make(
            $input,
            [
                'nombre_plantilla' => 'required|string|max:255',
                'contenido_plantilla' => 'required|string',
                'descripcion' => 'nullable|string',
                'activo' => 'boolean'
            ],
            [
                'nombre_plantilla.required' => 'El nombre de la plantilla es obligatorio',
                'nombre_plantilla.max' => 'El nombre no puede exceder 255 caracteres',
                'contenido_plantilla.required' => 'El contenido de la plantilla es obligatorio',
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', $validacion->errors()->first());
            return redirect()->route('plantillas_contratos.create')->withInput()->withErrors($validacion);
        }

        try {
            DB::beginTransaction();

            // Insertar plantilla
            $result = DB::insert(
                'INSERT INTO plantillas_contratos
                (nombre_plantilla, descripcion, contenido_plantilla, activo, user_id, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $input['nombre_plantilla'],
                    $input['descripcion'] ?? null,
                    $input['contenido_plantilla'],
                    isset($input['activo']) ? true : false,
                    Auth::id(),
                    Carbon::now(),
                    Carbon::now()
                ]
            );

            DB::commit();

            Alert::success('¡Éxito!', 'Plantilla de contrato creada correctamente.');
            return redirect()->route('plantillas_contratos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudo crear la plantilla: ' . $e->getMessage());
            return redirect()->route('plantillas_contratos.create')->withInput();
        }
    }

    /**
     * Mostrar detalle de una plantilla
     */
    public function show($id)
    {
        $plantilla = DB::selectOne(
            'SELECT pc.*, u.name as usuario_nombre
            FROM plantillas_contratos pc
            LEFT JOIN users u ON u.id = pc.user_id
            WHERE pc.id_plantilla_contrato = ?',
            [$id]
        );

        if (!$plantilla) {
            Alert::error('Error', 'Plantilla no encontrada.');
            return redirect()->route('plantillas_contratos.index');
        }

        $palabrasClave = PlantillaContrato::getPalabrasClave();
        return view('plantillas_contratos.show')
            ->with('plantilla', $plantilla)
            ->with('palabrasClave', $palabrasClave);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        $plantilla = DB::selectOne(
            'SELECT * FROM plantillas_contratos WHERE id_plantilla_contrato = ?',
            [$id]
        );

        if (!$plantilla) {
            Alert::error('Error', 'Plantilla no encontrada.');
            return redirect()->route('plantillas_contratos.index');
        }

        $palabrasClave = PlantillaContrato::getPalabrasClave();
        return view('plantillas_contratos.edit')
            ->with('plantilla', $plantilla)
            ->with('palabrasClave', $palabrasClave);
    }

    /**
     * Actualizar plantilla
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();

        // Verificar que la plantilla exista
        $plantilla = DB::selectOne(
            'SELECT * FROM plantillas_contratos WHERE id_plantilla_contrato = ?',
            [$id]
        );

        if (!$plantilla) {
            Alert::error('Error', 'Plantilla no encontrada.');
            return redirect()->route('plantillas_contratos.index');
        }

        $validacion = Validator::make(
            $input,
            [
                'nombre_plantilla' => 'required|string|max:255',
                'contenido_plantilla' => 'required|string',
                'descripcion' => 'nullable|string',
                'activo' => 'boolean'
            ],
            [
                'nombre_plantilla.required' => 'El nombre de la plantilla es obligatorio',
                'nombre_plantilla.max' => 'El nombre no puede exceder 255 caracteres',
                'contenido_plantilla.required' => 'El contenido de la plantilla es obligatorio',
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', $validacion->errors()->first());
            return redirect()->route('plantillas_contratos.edit', $id)->withInput()->withErrors($validacion);
        }

        try {
            DB::beginTransaction();

            // Actualizar plantilla
            DB::update(
                'UPDATE plantillas_contratos
                SET nombre_plantilla = ?,
                    descripcion = ?,
                    contenido_plantilla = ?,
                    activo = ?,
                    fecha_modificacion = ?
                WHERE id_plantilla_contrato = ?',
                [
                    $input['nombre_plantilla'],
                    $input['descripcion'] ?? null,
                    $input['contenido_plantilla'],
                    isset($input['activo']) ? true : false,
                    Carbon::now(),
                    $id
                ]
            );

            DB::commit();

            Alert::success('¡Éxito!', 'Plantilla de contrato actualizada correctamente.');
            return redirect()->route('plantillas_contratos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudo actualizar la plantilla: ' . $e->getMessage());
            return redirect()->route('plantillas_contratos.edit', $id)->withInput();
        }
    }

    /**
     * Eliminar plantilla
     */
    public function destroy($id)
    {
        try {
            // Verificar que la plantilla exista
            $plantilla = DB::selectOne(
                'SELECT * FROM plantillas_contratos WHERE id_plantilla_contrato = ?',
                [$id]
            );

            if (!$plantilla) {
                Alert::error('Error', 'Plantilla no encontrada.');
                return $this->sendError('Plantilla no encontrada');
            }

            // Verificar si hay contratos generados con esta plantilla
            $contratosGenerados = DB::selectOne(
                'SELECT COUNT(*) as total FROM contratos_generados WHERE id_plantilla_contrato = ?',
                [$id]
            );

            if ($contratosGenerados && $contratosGenerados->total > 0) {
                Alert::error('Error', 'No se puede eliminar la plantilla porque tiene contratos generados asociados. Puede desactivarla en su lugar.');
                return $this->sendError('No se puede eliminar la plantilla porque tiene contratos generados asociados. Puede desactivarla en su lugar.');
            }

            DB::beginTransaction();

            DB::delete('DELETE FROM plantillas_contratos WHERE id_plantilla_contrato = ?', [$id]);

            DB::commit();

            Alert::success('Éxito', 'Registro eliminado correctamente.');
            return $this->sendSuccess('Plantilla eliminada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudo eliminar el registro: ' . $e->getMessage());
            return $this->sendError('Error al eliminar la plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para generar contrato desde plantilla
     */
    public function generarFormulario($id)
    {
        $plantilla = PlantillaContrato::find($id);

        if (!$plantilla) {
            Alert::error('Error', 'Plantilla no encontrada.');
            return redirect()->route('plantillas_contratos.index');
        }

        // Obtener clientes activos
        $clientes = DB::select('
            SELECT id_cliente, clie_nombre, clie_apellido, clie_ci
            FROM clientes
            ORDER BY clie_apellido, clie_nombre
        ');

        return view('plantillas_contratos.generar')
            ->with('plantilla', $plantilla)
            ->with('clientes', $clientes);
    }

    /**
     * Vista previa editable del contrato antes de guardar
     */
    public function generar(Request $request, $id)
    {
        $validacion = Validator::make(
            $request->all(),
            [
                'id_cliente' => 'required|exists:clientes,id_cliente',
                'fecha_contrato' => 'nullable|date',
                'observaciones' => 'nullable|string'
            ],
            [
                'id_cliente.required' => 'Debe seleccionar un cliente',
                'id_cliente.exists' => 'El cliente seleccionado no existe',
                'fecha_contrato.date' => 'La fecha del contrato no es válida'
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', $validacion->errors()->first());
            return redirect()->back()->withInput()->withErrors($validacion);
        }

        try {
            $plantilla = PlantillaContrato::find($id);

            if (!$plantilla) {
                Alert::error('Error', 'Plantilla no encontrada.');
                return redirect()->route('plantillas_contratos.index');
            }

            // Generar contrato con datos del cliente
            $datosAdicionales = [];
            if ($request->has('fecha_contrato')) {
                $datosAdicionales['fecha_contrato'] = $request->fecha_contrato;
            }

            $contenidoGenerado = $plantilla->generarContrato($request->id_cliente, $datosAdicionales);

            // Obtener datos del cliente para mostrar en la vista previa
            $cliente = DB::selectOne(
                'SELECT c.*, ciu.descripcion as ciudad, dep.descripcion as departamento
                FROM clientes c
                LEFT JOIN ciudades ciu ON c.id_ciudad = ciu.id_ciudad
                LEFT JOIN departamentos dep ON c.id_departamento = dep.id_departamento
                WHERE c.id_cliente = ?',
                [$request->id_cliente]
            );

            // Mostrar vista previa editable
            return view('plantillas_contratos.previa_editable')
                ->with('plantilla', $plantilla)
                ->with('cliente', $cliente)
                ->with('contenidoGenerado', $contenidoGenerado)
                ->with('observaciones', $request->observaciones)
                ->with('fecha_contrato', $request->fecha_contrato ?? Carbon::now()->format('Y-m-d'));
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo generar el contrato: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Guardar contrato después de edición
     */
    public function guardarContrato(Request $request)
    {
        $validacion = Validator::make(
            $request->all(),
            [
                'id_plantilla_contrato' => 'required|exists:plantillas_contratos,id_plantilla_contrato',
                'id_cliente' => 'required|exists:clientes,id_cliente',
                'contenido_generado' => 'required|string',
                'observaciones' => 'nullable|string'
            ],
            [
                'contenido_generado.required' => 'El contenido del contrato es obligatorio'
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', $validacion->errors()->first());
            return redirect()->back()->withInput()->withErrors($validacion);
        }

        try {
            DB::beginTransaction();

            DB::insert(
                'INSERT INTO contratos_generados
                (id_plantilla_contrato, id_cliente, contenido_generado, observaciones, user_id, fecha_generacion)
                VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $request->id_plantilla_contrato,
                    $request->id_cliente,
                    $request->contenido_generado,
                    $request->observaciones ?? null,
                    Auth::id(),
                    Carbon::now()
                ]
            );

            $idContratoGenerado = DB::getPdo()->lastInsertId();

            DB::commit();

            Alert::success('¡Éxito!', 'Contrato guardado correctamente.');
            return redirect()->route('plantillas_contratos.ver_contrato', $idContratoGenerado);
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudo guardar el contrato: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Ver contrato generado
     */
    public function verContrato($idContratoGenerado)
    {
        $contrato = DB::selectOne(
            'SELECT cg.*,
                    pc.nombre_plantilla,
                    c.clie_nombre, c.clie_apellido, c.clie_ci,
                    u.name as usuario_nombre
            FROM contratos_generados cg
            LEFT JOIN plantillas_contratos pc ON pc.id_plantilla_contrato = cg.id_plantilla_contrato
            LEFT JOIN clientes c ON c.id_cliente = cg.id_cliente
            LEFT JOIN users u ON u.id = cg.user_id
            WHERE cg.id_contrato_generado = ?',
            [$idContratoGenerado]
        );

        if (!$contrato) {
            Alert::error('Error', 'Contrato no encontrado.');
            return redirect()->route('plantillas_contratos.index');
        }

        return view('plantillas_contratos.ver_contrato')->with('contrato', $contrato);
    }

    /**
     * Descargar contrato en PDF
     */
    public function descargarPdf($idContratoGenerado)
    {
        $contrato = DB::selectOne(
            'SELECT cg.*,
                    pc.nombre_plantilla,
                    c.clie_nombre, c.clie_apellido, c.clie_ci
            FROM contratos_generados cg
            LEFT JOIN plantillas_contratos pc ON pc.id_plantilla_contrato = cg.id_plantilla_contrato
            LEFT JOIN clientes c ON c.id_cliente = cg.id_cliente
            WHERE cg.id_contrato_generado = ?',
            [$idContratoGenerado]
        );

        if (!$contrato) {
            Alert::error('Error', 'Contrato no encontrado.');
            return redirect()->route('plantillas_contratos.index');
        }

        $pdf = Pdf::loadView('plantillas_contratos.pdf', ['contrato' => $contrato]);

        $nombreArchivo = 'Contrato_' . $contrato->clie_apellido . '_' . $contrato->clie_ci . '_' . date('YmdHis') . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * Cargar archivos/imágenes desde CKEditor/CKFinder
     */
    public function subirArchivoEditor(Request $request)
    {
        if (!$request->hasFile('upload')) {
            return response()->json([
                'uploaded' => 0,
                'error' => ['message' => 'No se ha enviado ningún archivo.']
            ], 422);
        }

        $request->validate([
            'upload' => 'required|image|mimes:jpg,jpeg,png,gif,webp,avif,svg|max:5120'
        ]);

        try {
            $archivo = $request->file('upload');
            $nombre = Str::uuid()->toString() . '.' . $archivo->getClientOriginalExtension();
            $destino = public_path('uploads/ckeditor');

            if (!File::exists($destino)) {
                File::makeDirectory($destino, 0755, true);
            }

            $archivo->move($destino, $nombre);
            $url = asset('uploads/ckeditor/' . $nombre);

            return response()->json([
                'uploaded' => 1,
                'fileName' => $nombre,
                'url' => $url
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'uploaded' => 0,
                'error' => ['message' => 'No se pudo cargar el archivo: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Editar contrato generado
     */
    public function editarContrato($idContratoGenerado)
    {
        $contrato = DB::selectOne(
            'SELECT cg.*,
                    pc.nombre_plantilla,
                    c.clie_nombre, c.clie_apellido, c.clie_ci,
                    u.name as usuario_nombre
            FROM contratos_generados cg
            LEFT JOIN plantillas_contratos pc ON pc.id_plantilla_contrato = cg.id_plantilla_contrato
            LEFT JOIN clientes c ON c.id_cliente = cg.id_cliente
            LEFT JOIN users u ON u.id = cg.user_id
            WHERE cg.id_contrato_generado = ?',
            [$idContratoGenerado]
        );

        if (!$contrato) {
            Alert::error('Error', 'Contrato no encontrado.');
            return redirect()->route('plantillas_contratos.contratos_generados');
        }

        return view('plantillas_contratos.editar_contrato')->with('contrato', $contrato);
    }

    /**
     * Editor OnlyOffice para contrato generado
     */
    public function editorOnlyOffice($idContratoGenerado)
    {
        $contrato = ContratoGenerado::with(['plantilla', 'cliente', 'usuario'])
            ->find($idContratoGenerado);

        if (!$contrato) {
            Alert::error('Error', 'Contrato no encontrado.');
            return redirect()->route('plantillas_contratos.contratos_generados');
        }

        return view('plantillas_contratos.onlyoffice_editor')->with('contrato', $contrato);
    }

    /**
     * Actualizar contrato generado
     */
    public function actualizarContrato(Request $request, $idContratoGenerado)
    {
        $validacion = Validator::make(
            $request->all(),
            [
                'contenido_generado' => 'required|string',
                'observaciones' => 'nullable|string'
            ],
            [
                'contenido_generado.required' => 'El contenido del contrato es obligatorio'
            ]
        );

        if ($validacion->fails()) {
            Alert::error('Error', $validacion->errors()->first());
            return redirect()->back()->withInput()->withErrors($validacion);
        }

        try {
            // Verificar que el contrato existe
            $contrato = DB::selectOne(
                'SELECT * FROM contratos_generados WHERE id_contrato_generado = ?',
                [$idContratoGenerado]
            );

            if (!$contrato) {
                Alert::error('Error', 'Contrato no encontrado.');
                return redirect()->route('plantillas_contratos.contratos_generados');
            }

            DB::beginTransaction();

            DB::update(
                'UPDATE contratos_generados
                SET contenido_generado = ?,
                    observaciones = ?
                WHERE id_contrato_generado = ?',
                [
                    $request->contenido_generado,
                    $request->observaciones ?? null,
                    $idContratoGenerado
                ]
            );

            DB::commit();

            Alert::success('¡Éxito!', 'Contrato actualizado correctamente.');
            return redirect()->route('plantillas_contratos.ver_contrato', $idContratoGenerado);
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudo actualizar el contrato: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Eliminar contrato generado
     */
    public function eliminarContrato($idContratoGenerado)
    {
        try {
            // Verificar que el contrato existe
            $contrato = DB::selectOne(
                'SELECT * FROM contratos_generados WHERE id_contrato_generado = ?',
                [$idContratoGenerado]
            );

            if (!$contrato) {
                $this->sendError('Contrato no encontrado');
                return redirect()->route('plantillas_contratos.contratos_generados');
            }

            DB::beginTransaction();

            DB::delete('DELETE FROM contratos_generados WHERE id_contrato_generado = ?', [$idContratoGenerado]);

            DB::commit();

            return $this->sendSuccess('Contrato eliminado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error al eliminar el contrato: ' . $e->getMessage());
        }
         return redirect(route('plantillas_contratos.contratos_generados'));
    }

    /**
     * Listar contratos generados
     */
    public function contratosGenerados(Request $request)
    {
        $buscar = $request->get('buscar');

        if ($buscar) {
            $contratos = DB::select(
                'SELECT cg.*,
                        pc.nombre_plantilla,
                        c.clie_nombre, c.clie_apellido, c.clie_ci,
                        u.name as usuario_nombre
                FROM contratos_generados cg
                LEFT JOIN plantillas_contratos pc ON pc.id_plantilla_contrato = cg.id_plantilla_contrato
                LEFT JOIN clientes c ON c.id_cliente = cg.id_cliente
                LEFT JOIN users u ON u.id = cg.user_id
                WHERE pc.nombre_plantilla ILIKE ?
                OR c.clie_nombre ILIKE ?
                OR c.clie_apellido ILIKE ?
                OR c.clie_ci ILIKE ?
                OR u.name ILIKE ?
                ORDER BY cg.fecha_generacion DESC',
                [
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%',
                    '%' . $buscar . '%'
                ]
            );
        } else {
            $contratos = DB::select('
                SELECT cg.*,
                        pc.nombre_plantilla,
                        c.clie_nombre, c.clie_apellido, c.clie_ci,
                        u.name as usuario_nombre
                FROM contratos_generados cg
                LEFT JOIN plantillas_contratos pc ON pc.id_plantilla_contrato = cg.id_plantilla_contrato
                LEFT JOIN clientes c ON c.id_cliente = cg.id_cliente
                LEFT JOIN users u ON u.id = cg.user_id
                ORDER BY cg.fecha_generacion DESC
            ');
        }

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = count($contratos);

        $items = array_slice($contratos, ($page - 1) * $perPage, $perPage);

        $contratos = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('plantillas_contratos.contratos_generados')->with('contratos', $contratos);
    }
}
