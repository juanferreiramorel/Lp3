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
        if ($request->isMethod('get') && !$request->ajax() && $response->status() == 200) {
            
            // Check if user is authenticated and mode is automatic
            if (!auth()->check() || auth()->user()->shortcut_mode !== 'automatic') {
                return $response;
            }

            // Exclude common non-page routes
            if ($request->is('logout') || $request->is('login') || $request->is('register') || $request->is('/') || $request->is('home')) {
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

            // Remove if exists to re-add at top
            $recent = array_filter($recent, function($item) use ($url) {
                return $item['url'] !== $url;
            });

            // Add to beginning
            array_unshift($recent, $currentAccess);

            // Limit to 5
            $recent = array_slice($recent, 0, 5);

            session(['recent_access' => $recent]);
        }

        return $response;
    }

    private function getTitleFromPath($request)
    {
        $route = $request->route();
        if (!$route) return null;

        $name = $route->getName();
        if (!$name) return null;

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

        // Fallback: Format the name (e.g. 'cajas.index' -> 'Cajas')
        $parts = explode('.', $name);
        if (count($parts) > 0) {
            return ucfirst($parts[0]);
        }

        return null;
    }
}
