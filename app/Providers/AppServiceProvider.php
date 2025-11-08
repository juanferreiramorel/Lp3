<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
    }
}
