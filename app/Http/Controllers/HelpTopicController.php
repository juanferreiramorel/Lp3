<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class HelpTopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // `ayuda index` administers the help module: it lists, and from there
        // topics are created and edited. Reading the manual for a screen is a
        // different thing, gated per topic by its own permission_slug inside
        // show() and file(). Requiring `ayuda index` to read meant anyone who
        // could use Clientes but not administer help got a 403.
        $this->middleware('permission:ayuda index')->only(['index']);
        $this->middleware('permission:ayuda create')->only(['create', 'store']);
        $this->middleware('permission:ayuda edit')->only(['edit', 'update']);
        $this->middleware('permission:ayuda destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        // trim(null) is deprecated in PHP 8.1+, and `buscar` is absent on the
        // first load of the page.
        $buscar = trim((string) $request->get('buscar'));

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        // 'field' says which property of the row holds the value and 'format'
        // how to print it; both are only read by the export.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_help', 'field' => 'id_help', 'first' => 'desc'],
            'titulo' => ['label' => 'Titulo', 'sort' => 'title', 'field' => 'title'],
            'modulo' => ['label' => 'Modulo', 'sort' => 'module', 'field' => 'module'],
            'ruta' => ['label' => 'Ruta', 'sort' => 'route_name', 'field' => 'route_name'],
            'permiso' => ['label' => 'Permiso', 'sort' => 'permission_slug', 'field' => 'permission_slug'],
            // The flag itself would land in the file as 1 or an empty cell, so
            // the query resolves it into the same words the grid shows.
            'estado' => ['label' => 'Estado', 'sort' => 'is_active', 'field' => 'estado_texto'],
            // Nothing but a link to the file lives in this cell.
            'pdf' => ['label' => 'PDF', 'export' => false],
            'acciones' => ['label' => 'Acciones', 'width' => '160px', 'export' => false],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $query = DB::table('help_topics')
            ->select('help_topics.*')
            ->selectRaw("CASE WHEN is_active::boolean THEN 'Activo' ELSE 'Inactivo' END as estado_texto")
            ->orderByRaw($sort->orderBy());

        if ($buscar) {
            $query->where(function ($sub) use ($buscar) {
                $like = '%' . $buscar . '%';
                $sub->where('title', 'ILIKE', $like)
                    ->orWhere('module', 'ILIKE', $like)
                    ->orWhere('route_name', 'ILIKE', $like)
                    ->orWhere('permission_slug', 'ILIKE', $like)
                    ->orWhere('action_key', 'ILIKE', $like);
            });
        }

        // The export reads the same builder before it is paginated, so the
        // file carries every row the current search matches. The builder is
        // only executed once, because this branch returns.
        if ($export = \App\Support\TableExport::fromRequest($request, $columns)) {
            return $export->download(fn () => $query->limit(\App\Support\TableExport::MAX_ROWS)->get(), 'ayuda');
        }

        $helpTopics = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('ayuda.table')
                ->with('helpTopics', $helpTopics)
                ->with('columns', $columns)
                ->with('sort', $sort);
        }

        return view('ayuda.index')
            ->with('helpTopics', $helpTopics)
            ->with('columns', $columns)
            ->with('sort', $sort);
    }

    public function create()
    {
        return view('ayuda.create')->with([
            'permissions' => $this->permissionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            $this->rules()
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $pdfPath = $this->storePdfFile($request->file('pdf'));

        DB::table('help_topics')->insert([
            'route_name' => $request->input('route_name'),
            'permission_slug' => $request->input('permission_slug'),
            'module' => $request->input('module'),
            'action_key' => $request->input('action_key'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'pdf_path' => $pdfPath,
            'is_active' => $request->boolean('is_active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Alert::toast('Tema de ayuda creado con éxito', 'success');

        return redirect()->route('ayuda.index');
    }

    public function show($id)
    {
        $helpTopic = DB::table('help_topics')->where('id_help', $id)->first();

        if (!$helpTopic) {
            Alert::toast('Tema de ayuda no encontrado', 'error');
            return redirect()->route('home');
        }

        abort_unless(\App\Support\HelpAccess::canRead($helpTopic), 403);

        return view('ayuda.show')->with('helpTopic', $helpTopic);
    }

    public function edit($id)
    {
        $helpTopic = DB::table('help_topics')->where('id_help', $id)->first();

        if (!$helpTopic) {
            Alert::toast('Tema de ayuda no encontrado', 'error');
            return redirect()->route('ayuda.index');
        }

        return view('ayuda.edit')->with([
            'helpTopic' => $helpTopic,
            'permissions' => $this->permissionOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $helpTopic = DB::table('help_topics')->where('id_help', $id)->first();

        if (!$helpTopic) {
            Alert::toast('Tema de ayuda no encontrado', 'error');
            return redirect()->route('ayuda.index');
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules(isUpdate: true)
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'route_name' => $request->input('route_name'),
            'permission_slug' => $request->input('permission_slug'),
            'module' => $request->input('module'),
            'action_key' => $request->input('action_key'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => now(),
        ];

        if ($request->hasFile('pdf')) {
            $this->deletePdfFile($helpTopic->pdf_path);
            $data['pdf_path'] = $this->storePdfFile($request->file('pdf'));
        }

        DB::table('help_topics')->where('id_help', $id)->update($data);

        Alert::toast('Tema de ayuda actualizado con éxito', 'success');

        return redirect()->route('ayuda.index');
    }

    public function file($id)
    {
        $helpTopic = DB::table('help_topics')->where('id_help', $id)->first();

        if (!$helpTopic || !$helpTopic->pdf_path) {
            Alert::toast('Archivo no disponible', 'error');
            return redirect()->route('home');
        }

        abort_unless(\App\Support\HelpAccess::canRead($helpTopic), 403);

        if (!Storage::disk('public')->exists($helpTopic->pdf_path)) {
            Alert::toast('El archivo no existe en el servidor', 'error');
            return redirect()->route('home');
        }

        return response()->file(Storage::disk('public')->path($helpTopic->pdf_path));
    }

    public function destroy($id)
    {
        $helpTopic = DB::table('help_topics')->where('id_help', $id)->first();

        if (!$helpTopic) {
            Alert::toast('Tema de ayuda no encontrado', 'error');
            return redirect()->route('ayuda.index');
        }

        try {
            $this->deletePdfFile($helpTopic->pdf_path);
            DB::table('help_topics')->where('id_help', $id)->delete();
            Alert::toast('Tema de ayuda eliminado con éxito', 'success');
        } catch (\Exception $exception) {
            Alert::error('Error', 'No se puede eliminar este tema de ayuda.');
        }

        return redirect()->route('ayuda.index');
    }

    private function rules(bool $isUpdate = false): array
    {
        return [
            'route_name' => ['nullable', 'string', 'max:150'],
            'permission_slug' => ['nullable', 'string', 'max:150'],
            'module' => ['nullable', 'string', 'max:100'],
            'action_key' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'pdf' => [($isUpdate ? 'nullable' : 'required'), 'file', 'mimes:pdf', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function permissionOptions(): array
    {
        return DB::table('permissions')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    private function storePdfFile(UploadedFile $file): string
    {
        Storage::disk('public')->makeDirectory('help');

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('help', $filename, 'public');
    }

    private function deletePdfFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
