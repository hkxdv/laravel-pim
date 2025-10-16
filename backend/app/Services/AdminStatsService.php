<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\EnhancedStat;
use App\Interfaces\StatsServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;

final readonly class AdminStatsService implements StatsServiceInterface
{
    public function __construct(
        private StaffUserManagerInterface $staffUserManager,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array {
        // Totales básicos del dashboard Admin
        $totalUsers = $this->staffUserManager->getTotalUsers();
        $totalRoles = $this->staffUserManager->getTotalRoles();

        return [
            new EnhancedStat(
                key: 'total_users',
                title: 'Usuarios',
                description: 'Usuarios del sistema',
                icon: 'users',
                value: $totalUsers,
            ),
            new EnhancedStat(
                key: 'total_roles',
                title: 'Roles',
                description: 'Roles disponibles',
                icon: 'shield-check',
                value: $totalRoles,
            ),
        ];
    }
}
