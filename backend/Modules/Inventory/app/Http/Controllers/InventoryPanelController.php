<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers;

use App\DTO\EnhancedStat;

/**
 * Controlador principal del Módulo de Inventario.
 * Estandariza el contrato con el frontend y delega la lógica en el backend.
 */
final class InventoryPanelController extends InventoryBaseController
{
    /**
     * Implementación concreta para obtener estadísticas del módulo.
     * Devuelve un array de EnhancedStat consumible por el frontend.
     *
     * @return EnhancedStat[]
     */
    protected function getModuleStats(): array
    {
        $user = $this->getAuthenticatedUser();

        return $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user
        );
    }
}
