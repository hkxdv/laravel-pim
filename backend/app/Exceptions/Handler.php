<?php

declare(strict_types=1);

namespace App\Exceptions;

use Error;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * Los tipos de excepción que no se informan.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Una lista de los entradas que nunca se flashan a la sesión en excepciones de validación.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registrar los callbacks de manejo de excepciones para la aplicación.
     */
    public function register(): void
    {
        // Registra errores críticos en un log separado
        $this->reportable(function (Error $e): void {
            Log::channel('daily')->critical('Error fatal: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    /**
     * Muestra una excepción en una respuesta HTTP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        // Si se solicita mostrar errores detallados de Laravel o estamos en modo debug
        if (
            \Illuminate\Support\Facades\Request::query('show_laravel_errors') !== null
            || (bool) (\Illuminate\Support\Env::get('SHOW_LARAVEL_ERRORS', false))
            || config('app.debug')
        ) {
            // Devolver la respuesta predeterminada de Laravel con todos los detalles del error
            return parent::render($request, $e);
        }

        // De lo contrario, usar el comportamiento predeterminado
        return parent::render($request, $e);
    }
}
