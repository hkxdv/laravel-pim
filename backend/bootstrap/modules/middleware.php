<?php

declare(strict_types=1);

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return function (Middleware $middleware): void {
    $middleware->encryptCookies(
        except: [
            'appearance',
            'sidebar_state',
        ]
    );

    $middleware->web(append: [
        HandleAppearance::class,
        HandleInertiaRequests::class,
        AddLinkHeadersForPreloadedAssets::class,
    ]);

    $middleware->api(prepend: [
        EnsureFrontendRequestsAreStateful::class,
    ]);

    $middleware->alias([
        'permission' => CheckPermission::class,
        'abilities' => CheckAbilities::class,
        'ability' => CheckForAnyAbility::class,
    ]);

    // Redirección para usuarios NO autenticados (cuando falla auth:*)
    $middleware->redirectGuestsTo(
        function (Request $request): string {
            // Rutas internas protegidas deben llevar al formulario de login
            if (str_starts_with($request->path(), 'internal')) {
                return route('login');
            }

            // Rutas públicas: página de bienvenida
            return route('welcome');
        }
    );

    // Redirección para usuarios autenticados en rutas de invitado (guest:*)
    // Evita que un usuario ya autenticado viendo /internal/login vuelva a '/'
    // y entre en un loop de redirecciones.
    $middleware->redirectUsersTo(
        function (Request $request): string {
            if (Illuminate\Support\Facades\Auth::guard('staff')->check()) {
                return route('internal.dashboard');
            }

            return route('welcome');
        }
    );
};
