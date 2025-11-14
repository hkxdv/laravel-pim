<?php

declare(strict_types=1);

namespace Modules\Module01\App\Services;

use App\DTO\EnhancedStat;
use App\Interfaces\StatsServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;

final class Module01StatsService implements StatsServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPanelStats(string $moduleSlug, ?Authenticatable $user = null): array
    {
        $panelItems = config('module01.panel_items', []);
        $contextualDefault = config('module01.contextual_nav.default', []);

        return [
            new EnhancedStat(
                key: 'panel_items',
                title: 'Ítems de panel',
                description: 'Total de accesos del panel',
                icon: 'layout-dashboard',
                value: count($panelItems),
            ),
            new EnhancedStat(
                key: 'contextual_links',
                title: 'Navegación contextual',
                description: 'Enlaces disponibles',
                icon: 'list',
                value: count($contextualDefault),
            ),
        ];
    }
}
