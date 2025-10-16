<?php

declare(strict_types=1);

namespace Modules\Module02\App\Http\Controllers;

use App\DTO\EnhancedStat;

/**
 * Controlador principal del Módulo 02.
 * Gestiona la visualización del panel y las operaciones relacionadas con este módulo.
 *
 * NOTA: Este controlador hereda el método showModulePanel() de ModuleOrchestrationController,
 * el cual está marcado como 'final' y no debe ser sobrescrito. Para personalizar el
 * comportamiento del panel, implementa los métodos de extensión:
 * - getModuleStats(): para definir las estadísticas específicas del módulo
 * - getAdditionalPanelData(): para agregar datos adicionales al panel
 */
final class Module02PanelController extends Module02BaseController
{
    /**
     * Implementación concreta para obtener estadísticas del módulo.
     * Devuelve un array de EnhancedStat consumible por el frontend.
     *
     * @return array<int, EnhancedStat>|null
     */
    protected function getModuleStats(): ?array
    {
        $user = $this->getAuthenticatedUser();
        $stats = $this->statsService->getPanelStats($this->getModuleSlug(), $user);

        return is_array($stats) ? $stats : [];
    }
}
