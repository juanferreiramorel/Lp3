<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:search index')->only(['index']);
    }

    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $results = [];
        $groups  = []; // agrupado por carpeta

        if ($q !== '') {
            $needle = $this->normalize($q);
            $collect = [];

            // 1) paths locales
            foreach (config('view.paths', []) as $basePath) {
                if (!is_dir($basePath)) continue;
                foreach (File::allFiles($basePath) as $file) {
                    if (!Str::endsWith($file->getFilename(), '.blade.php')) continue;

                    $relative  = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
                    $viewName  = str_replace('/', '.', preg_replace('#\.blade\.php$#', '', $relative));
                    $folder    = dirname($relative); // ej: cargos
                    $folder    = $folder === '.' ? 'root' : $folder;

                    $nameScore = Str::contains($this->normalize($relative), $needle);
                    $content   = @file_get_contents($file->getPathname()) ?: '';
                    $cntScore  = $content !== '' && Str::contains($this->normalize($content), $needle);

                    if ($nameScore || $cntScore) {
                        $collect[$viewName] = $this->decorate($viewName, $file->getPathname(), $relative, $q, $content, 'local', $folder);
                    }
                }
            }

            // 2) namespaces (adminlte::..., etc.)
            $hints = View::getFinder()->getHints();
            foreach ($hints as $ns => $paths) {
                foreach ($paths as $basePath) {
                    if (!is_dir($basePath)) continue;
                    foreach (File::allFiles($basePath) as $file) {
                        if (!Str::endsWith($file->getFilename(), '.blade.php')) continue;

                        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
                        $viewNs   = $ns.'::'.str_replace('/', '.', preg_replace('#\.blade\.php$#', '', $relative));
                        $folder   = $ns.'/'.(dirname($relative) === '.' ? 'root' : dirname($relative));

                        $nameScore = Str::contains($this->normalize($ns.'::'.$relative), $needle);
                        $content   = @file_get_contents($file->getPathname()) ?: '';
                        $cntScore  = $content !== '' && Str::contains($this->normalize($content), $needle);

                        if ($nameScore || $cntScore) {
                            $collect[$viewNs] = $this->decorate($viewNs, $file->getPathname(), $relative, $q, $content, 'namespace', $folder);
                        }
                    }
                }
            }

            // limitar y ordenar por carpeta + título
            $results = array_values($collect);
            usort($results, function($a, $b){
                return [$a['folder'], $a['title']] <=> [$b['folder'], $b['title']];
            });
            $results = array_slice($results, 0, 300);

            // agrupar
            foreach ($results as $item) {
                $groups[$item['folder']][] = $item;
            }
        }

        return view('search.results', [
            'q'      => $q,
            'groups' => $groups,
            'total'  => array_sum(array_map('count', $groups)),
        ]);
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $s ?: '';
    }

    private function decorate(string $viewName, string $path, string $relative, string $q, string $content, string $type, string $folder): array
    {
        $title   = basename($relative, '.blade.php');          // ej: index
        $bytes   = @filesize($path) ?: 0;
        $sizeKB  = number_format($bytes/1024, 1, ',', '.');
        $mtime   = @filemtime($path) ?: time();
        $updated = date('d/m/Y H:i', $mtime);

        // snippet: línea con match o primeras 160 chars
        $snippet = '';
        if ($q && $content) {
            $pos = stripos($content, $q);
            if ($pos !== false) {
                $start = max(0, $pos - 80);
                $len   = 160;
                $snippet = trim(preg_replace('/\s+/', ' ', substr($content, $start, $len)));
            }
        }
        if ($snippet === '' && $content) {
            $snippet = trim(preg_replace('/\s+/', ' ', substr($content, 0, 160)));
        }

        return [
            'view'    => $viewName,        // p.ej. cargos.index o adminlte::page
            'title'   => $title,           // index
            'file'    => $path,            // C:\...\index.blade.php
            'rel'     => $relative,        // cargos/index.blade.php
            'size'    => $sizeKB,          // “4,2”
            'updated' => $updated,         // “28/08/2025 14:33”
            'snippet' => $snippet,
            'type'    => $type,            // local|namespace
            'folder'  => $folder,          // cargos | ns/...
        ];
    }
}
