<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $routeName = optional(Route::current())->getName();
            $helpTopics = collect();

            if ($routeName && Schema::hasTable('help_topics')) {
                $helpTopics = DB::table('help_topics')
                    ->where('route_name', $routeName)
                    ->where('is_active', true)
                    ->orderByDesc('updated_at')
                    ->get();
            }

            $view->with('helpTopicsForRoute', $helpTopics);
        });

        // Logging de queries SQL en modo debug
        if (config('app.debug') && config('app.env') === 'local') {
            DB::listen(function ($query) {
                // Solo loguear queries lentas (más de 100ms)
                if ($query->time > 100) {
                    Log::channel('queries')->warning('Query lenta detectada', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Logging de errores de base de datos
        DB::listen(function ($event) {
            if (property_exists($event, 'exception')) {
                Log::channel('queries')->error('Error en query SQL', [
                    'sql' => $event->sql ?? 'N/A',
                    'error' => $event->exception->getMessage(),
                ]);
            }
        });
    }
}
