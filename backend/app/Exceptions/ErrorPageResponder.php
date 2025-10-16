<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ErrorPageResponder
{
    public static function unauthorized(
        Request $request
    ): \Symfony\Component\HttpFoundation\Response {
        return self::inertiaErrorPage(
            403,
            self::friendlyMessage(403),
            $request
        );
    }

    public static function http(
        HttpException $e,
        Request $request
    ): \Symfony\Component\HttpFoundation\Response {
        $status = $e->getStatusCode();

        return self::inertiaErrorPage(
            $status,
            self::friendlyMessage($status, $e->getMessage()),
            $request
        );
    }

    public static function authentication(
        AuthenticationException $e,
        Request $request
    ): ?\Symfony\Component\HttpFoundation\Response {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        return null;
    }

    public static function validation(
        Request $request
    ): \Symfony\Component\HttpFoundation\Response {
        return self::inertiaErrorPage(
            422,
            self::friendlyMessage(422),
            $request
        );
    }

    public static function generic(
        Request $request
    ): ?\Symfony\Component\HttpFoundation\Response {
        if (! config('app.debug')) {
            return self::inertiaErrorPage(
                500,
                self::friendlyMessage(500),
                $request
            );
        }

        return null;
    }

    private static function friendlyMessage(
        int $status,
        ?string $message = null
    ): string {
        // Si hay un mensaje específico y no estamos en producción, mostrarlo
        if ($message && ! app()->isProduction()) {
            return $message;
        }

        $errorMessages = [
            400 => 'La solicitud contiene errores o no puede ser procesada.',
            401 => 'No has iniciado sesión o tu sesión ha expirado.',
            403 => 'No tienes permiso para acceder a esta página.',
            404 => 'Lo sentimos, la página que buscas no existe.',
            405 => 'El método de solicitud no está permitido.',
            408 => 'La solicitud tardó demasiado tiempo en completarse.',
            419 => 'Tu sesión ha expirado. Por favor, recarga la página e intenta nuevamente.',
            422 => 'Los datos proporcionados no son válidos. Por favor, verifica la información.',
            429 => 'Has realizado demasiadas solicitudes en poco tiempo. Por favor, espera un momento.',
            500 => 'Se ha producido un error interno en el servidor.',
            503 => 'El servicio no está disponible temporalmente. Por favor, intenta de nuevo más tarde.',
        ];

        return $errorMessages[$status]
            ?? 'Se ha producido un error inesperado.';
    }

    private static function inertiaErrorPage(
        int $status,
        string $message,
        Request $request
    ): \Symfony\Component\HttpFoundation\Response {
        return Inertia::render('errors/error-page', [
            'status' => $status,
            'message' => $message,
        ])->toResponse($request)->setStatusCode($status);
    }
}
