<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Http\Controllers;

use App\DTO\EnhancedStat;
use App\Models\AgentLog;
use Illuminate\Support\Facades\Date;

/**
 * Controlador de panel para el Módulo.
 */
final class WhatsAppPanelController extends WhatsAppBaseController
{
    /**
     * @return array<int, EnhancedStat>
     */
    protected function getModuleStats(): array
    {
        $totalItems = (int) AgentLog::query()->count();

        $recentEvents = (int) AgentLog::query()
            ->where('created_at', '>=', Date::now()->subDays(7))
            ->count();

        return [
            new EnhancedStat(
                key: 'totalItems',
                title: 'Registros',
                description: 'Total de registros en el sistema',
                icon: 'list',
                value: $totalItems
            ),
            new EnhancedStat(
                key: 'recentEvents',
                title: 'Eventos recientes',
                description: 'Registros en los últimos 7 días',
                icon: 'clock',
                value: $recentEvents
            ),
        ];
    }
}
