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
        $this->middleware('permission:ayuda index')->only(['index', 'show', 'file']);
        $this->middleware('permission:ayuda create')->only(['create', 'store']);
        $this->middleware('permission:ayuda edit')->only(['edit', 'update']);
        $this->middleware('permission:ayuda destroy')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $buscar = trim($request->get('buscar'));

        // One definition drives both the ORDER BY below and the table header.
        // A column without 'sort' simply is not sortable.
        $columns = [
            'id' => ['label' => '#', 'sort' => 'id_help', 'first' => 'desc'],
            'titulo' => ['label' => 'Titulo', 'sort' => 'title'],
            'modulo' => ['label' => 'Modulo', 'sort' => 'module'],
            'ruta' => ['label' => 'Ruta', 'sort' => 'route_name'],
            'permiso' => ['label' => 'Permiso', 'sort' => 'permission_slug'],
            'estado' => ['label' => 'Estado', 'sort' => 'is_active'],
            'pdf' => ['label' => 'PDF'],
            'acciones' => ['label' => 'Acciones', 'width' => '160px'],
        ];

        $sort = \App\Support\TableSort::resolve($columns, 'id', 'desc', $request);

        $query = DB::table('help_topics')->orderByRaw($sort->orderBy());

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
            return redirect()->route('ayuda.index');
        }

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
            return redirect()->route('ayuda.index');
        }

        if (!Storage::disk('public')->exists($helpTopic->pdf_path)) {
            Alert::toast('El archivo no existe en el servidor', 'error');
            return redirect()->route('ayuda.index');
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
