<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Controlador para gestionar la página de bienvenida de la aplicación.
 *
 * Se encarga de renderizar la vista principal que se muestra a los visitantes
 * antes de que inicien sesión.
 */
final class WelcomeController extends Controller
{
    /**
     * Muestra la página de bienvenida.
     *
     * Renderiza el componente de Inertia 'welcome' y le pasa la información
     * del usuario autenticado, si existe. Esto permite a la vista mostrar
     * dinámicamente opciones de "Login" o "Dashboard".
     *
     * @param  Request  $request  La solicitud HTTP entrante.
     * @return InertiaResponse La respuesta de Inertia que renderiza la vista.
     */
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('public/welcome', [
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }
}
