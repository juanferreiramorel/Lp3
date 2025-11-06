<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use RealRashid\SweetAlert\Facades\Alert;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Manejar errores de base de datos (violación de llave foránea, etc.)
        if ($exception instanceof QueryException) {
            // Verificar si es error de violación de llave foránea
            if (strpos($exception->getMessage(), 'foreign key constraint') !== false ||
                strpos($exception->getMessage(), 'SQLSTATE[23503]') !== false ||
                strpos($exception->getMessage(), 'violates foreign key constraint') !== false) {
                Alert::error('Error', 'No se puede eliminar este registro porque está siendo utilizado en otro lugar del sistema.');
            } else {
                Alert::error('Error', 'Error en la base de datos. Por favor, contacte al administrador.');
            }
            return back();
        }

        // Manejar errores 404 - Página no encontrada
        if ($exception instanceof NotFoundHttpException) {
            Alert::error('Error 404', 'La página solicitada no fue encontrada.');
            return redirect()->route('home');
        }

        // Manejar errores HTTP generales
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $message = '';

            switch ($statusCode) {
                case 403:
                    $message = 'No tiene permisos para acceder a este recurso.';
                    break;
                case 404:
                    $message = 'Recurso no encontrado.';
                    break;
                case 500:
                    $message = 'Error interno del servidor.';
                    break;
                case 503:
                    $message = 'Servicio no disponible.';
                    break;
                default:
                    $message = 'Ha ocurrido un error. Por favor, intente nuevamente.';
            }

            Alert::error('Error ' . $statusCode, $message);
            return back();
        }

        // Para cualquier otro error no manejado específicamente
        if (!($exception instanceof ValidationException) && !config('app.debug')) {
            Alert::error('Error', 'Ha ocurrido un error inesperado. Por favor, intente nuevamente.');
            return back();
        }

        return parent::render($request, $exception);
    }
}
