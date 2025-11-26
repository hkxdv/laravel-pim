<?php

declare(strict_types=1);

namespace Modules\Sales\App\Services;

// use App\DTO\EnhancedStat;
use App\Interfaces\StatsServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;

final class SalesStatsService implements StatsServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array {
        return [];
    }
}
