<?php

declare(strict_types=1);

use App\Exceptions\ErrorPageResponder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return function (bool $showLaravelErrors): callable {
    return function (Exceptions $exceptions) use ($showLaravelErrors): void {
        // Si se solicita mostrar errores de Laravel o estamos en modo debug (ENV), no registramos los manejadores personalizados
        if ($showLaravelErrors || (bool) Env::get('APP_DEBUG', false)) {
            // Configuración para mostrar errores detallados de Laravel
            $exceptions->dontReport([
                AuthenticationException::class,
                AuthorizationException::class,
                HttpException::class,
                ModelNotFoundException::class,
                ValidationException::class,
            ]);

            return;
        }

        // Registro de manejadores usando la clase dedicada
        $exceptions->renderable(
            fn(UnauthorizedException $e, Request $request): \Symfony\Component\HttpFoundation\Response => ErrorPageResponder::unauthorized($request)
        );

        $exceptions->renderable(
            fn(HttpException $e, Request $request): \Symfony\Component\HttpFoundation\Response => ErrorPageResponder::http($e, $request)
        );

        $exceptions->renderable(
            fn(AuthenticationException $e, Request $request): ?\Symfony\Component\HttpFoundation\Response => ErrorPageResponder::authentication($e, $request)
        );

        $exceptions->renderable(
            fn(ValidationException $e, Request $request): \Symfony\Component\HttpFoundation\Response => ErrorPageResponder::validation($request)
        );

        $exceptions->renderable(
            fn(\Throwable $e, Request $request): ?\Symfony\Component\HttpFoundation\Response => ErrorPageResponder::generic($request)
        );
    };
};
