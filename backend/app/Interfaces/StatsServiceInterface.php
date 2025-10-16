<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTO\EnhancedStat;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contrato para servicios de estadística por módulo.
 */
interface StatsServiceInterface
{
    /**
     * Devuelve las estadísticas enriquecidas para el panel del módulo actual.
     *
     * @param  string  $moduleSlug  Slug del módulo para contextualizar el cálculo.
     * @param  Authenticatable|null  $user  Usuario autenticado (si aplica).
     * @return EnhancedStat[]
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array;
}
