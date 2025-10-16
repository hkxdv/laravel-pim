<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Interfaz para la auditoría y gestión de seguridad del sistema.
 * Define cómo se auditan y registran eventos relacionados con la seguridad.
 */
interface SecurityAuditInterface
{
    /**
     * Prepara la sesión después de una autenticación exitosa.
     * Regenera la sesión y el token para prevenir ataques de fijación de sesión.
     *
     * @param  Request  $request  Request actual
     */
    public function prepareAuthenticatedSession(Request $request): void;

    /**
     * Maneja la notificación de inicio de sesión para inicios de sesión sospechosos.
     *
     * @param  Authenticatable  $user  Usuario autenticado
     * @param  Request  $request  Request actual
     */
    public function handleSuspiciousLoginNotification(
        Authenticatable $user,
        Request $request
    ): void;

    /**
     * Cierra la sesión del usuario para un guard específico.
     *
     * @param  Request  $request  Request actual
     * @param  string  $guard  Guard de autenticación
     */
    public function logout(Request $request, string $guard): void;
}
