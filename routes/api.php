<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ciudades/{id_departamento}', [App\Http\Controllers\CiudadController::class, 'getByDepartamento']);

/*
|--------------------------------------------------------------------------
| OnlyOffice API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('onlyoffice')->group(function () {
    // Descarga de documento (sin auth para que OnlyOffice pueda acceder)
    Route::get('/download/{documentId}/{filename}', [App\Http\Controllers\Api\OnlyOfficeController::class, 'download'])
        ->name('onlyoffice.download');
    
    // Callback de guardado (sin auth, OnlyOffice llama a esta URL)
    Route::post('/callback/{documentId}', [App\Http\Controllers\Api\OnlyOfficeController::class, 'callback'])
        ->name('onlyoffice.callback');
    
    // Health check del servidor
    Route::get('/health', [App\Http\Controllers\Api\OnlyOfficeController::class, 'healthCheck'])
        ->name('onlyoffice.health');
    
    // Configuración del editor (usa sesión web en lugar de Sanctum)
    Route::get('/config/{documentId}', [App\Http\Controllers\Api\OnlyOfficeController::class, 'editorConfig'])
        ->name('onlyoffice.config');
});
