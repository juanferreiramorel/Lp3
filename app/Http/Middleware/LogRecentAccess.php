<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LogRecentAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only track GET requests, exclude AJAX, and only successful responses
        if ($request->isMethod('get') && !$request->ajax() && $response->getStatusCode() == 200) {

            // Check if user is authenticated and mode is automatic
            if (!auth()->check() || auth()->user()->shortcut_mode !== 'automatic') {
                return $response;
            }

            // Exclude common non-page routes
            if ($request->is('logout') || $request->is('login') || $request->is('register') || $request->is('/') || $request->is('home')) {
                return $response;
            }

            // Only navigable pages belong in a "recently visited" list. PDF
            // printouts, file downloads and JSON endpoints are responses, not
            // destinations: re-requesting one from the navbar does not take the
            // user anywhere they can work.
            if (!$this->isNavigablePage($response)) {
                return $response;
            }

            $url = $request->fullUrl();
            $path = $request->path();
            
            // Try to get a meaningful title
            $title = $this->getTitleFromPath($request);
            if (!$title) {
                return $response;
            }

            $currentAccess = [
                'url' => $url,
                'title' => $title,
                'path' => $path
            ];

            $recent = session('recent_access', []);

            // Drop entries already stored in live sessions under the placeholder
            // name, so the navbar repairs itself without waiting for the session
            // to expire.
            //
            // Deduplicate by title as well as by URL: five shortcuts all reading
            // "Recibos" tell the user nothing about where each one goes, and the
            // most recent visit is the only one worth keeping anyway.
            $recent = array_filter($recent, function ($item) use ($url, $title) {
                return is_array($item)
                    && isset($item['url'], $item['title'])
                    && $item['url'] !== $url
                    && mb_strtolower($item['title']) !== mb_strtolower($title)
                    && stripos($item['title'], 'generated::') !== 0;
            });

            // Add to beginning
            array_unshift($recent, $currentAccess);

            // Limit to 5
            $recent = array_slice($recent, 0, 5);

            session(['recent_access' => $recent]);
        }

        return $response;
    }

    /**
     * A navigable page renders HTML. Anything else — a streamed PDF, a file
     * download, a JSON payload — is excluded.
     */
    private function isNavigablePage($response): bool
    {
        $contentType = $response->headers->get('Content-Type');

        if ($contentType !== null && stripos($contentType, 'text/html') === false) {
            return false;
        }

        // A correct Content-Type can still describe an attachment.
        $disposition = $response->headers->get('Content-Disposition');

        return $disposition === null || stripos($disposition, 'attachment') === false;
    }

    /**
     * Turn a path into a readable label: "reporte-libro-venta" -> "Reporte libro venta".
     * Numeric segments are record ids and carry no meaning in a menu.
     */
    private function humanizePath($request): ?string
    {
        foreach (explode('/', $request->path()) as $segment) {
            if ($segment === '' || is_numeric($segment)) {
                continue;
            }

            $words = trim(preg_replace('/[-_]+/', ' ', $segment));

            return $words === '' ? null : ucfirst(mb_strtolower($words));
        }

        return null;
    }

    private function getTitleFromPath($request)
    {
        $route = $request->route();
        if (!$route) return null;

        $name = $route->getName();

        // With `route:cache`, Laravel invents a name like "generated::EoPGyuENc4BcHdJf"
        // for every route registered without one, so an unnamed route no longer
        // reports a null name — it reports that placeholder. Printing it is where
        // "Generated::EoPGyuENc4BcHdJf" in the navbar came from.
        if (!$name || str_starts_with($name, 'generated::')) {
            return $this->humanizePath($request);
        }

        // Map common route names to friendly titles
        $titles = [
            'ventas.index' => 'Listado Ventas',
            'ventas.create' => 'Nueva Venta',
            'ventas.show' => 'Ver Venta',
            'apertura-cierre-caja.index' => 'Arqueo de Caja',
            'pedidos.index' => 'Pedidos',
            'pedidos.create' => 'Nuevo Pedido',
            'clientes.index' => 'Clientes',
            'clientes.create' => 'Nuevo Cliente',
            'cuentasacobrar.index' => 'Cuentas a Cobrar',
            'recibossimples.index' => 'Recibos',
            'recibossimples.create' => 'Nuevo Recibo',
            'compras.index' => 'Compras',
            'compras.create' => 'Nueva Compra',
            'ordencompra.index' => 'Ordenes de Compra',
            'proveedores.index' => 'Proveedores',
            'cuentasapagar.index' => 'Cuentas a Pagar',
            'productos.index' => 'Productos',
            'stock.index' => 'Stock',
            'marcas.index' => 'Marcas',
            'plantillas_contratos.index' => 'Contratos',
            'users.index' => 'Usuarios',
            'sucursales.index' => 'Sucursales',
            'home' => 'Inicio',
            'dashboard' => 'Dashboard',
        ];

        if (isset($titles[$name])) {
            return $titles[$name];
        }

        // Fallback: format the name (e.g. 'plantillas_contratos.index' -> 'Plantillas contratos')
        $resource = explode('.', $name)[0] ?? '';
        $words = trim(preg_replace('/[-_]+/', ' ', $resource));

        return $words === '' ? $this->humanizePath($request) : ucfirst(mb_strtolower($words));
    }
}
