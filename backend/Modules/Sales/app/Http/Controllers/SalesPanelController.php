<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Controllers;

use App\DTO\EnhancedStat;
use Modules\Sales\App\Models\SalesOrder;

/**
 * Controlador principal del Módulo.
 */
final class SalesPanelController extends SalesBaseController
{
    /**
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

    /**
     * Aporta métricas adicionales del módulo para el panel.
     *
     * @return array<string, mixed>
     */
    protected function getAdditionalPanelData(): array
    {
        $totalOrders = (int) SalesOrder::query()->count();
        $deliveredOrders = (int) SalesOrder::query()
            ->where('status', 'delivered')
            ->count();
        $sumTotals = (float) SalesOrder::query()->sum('total');

        return [
            'stats' => [
                'ordersTotal' => $totalOrders,
                'deliveredOrders' => $deliveredOrders,
                'sumTotals' => round($sumTotals, 2),
            ],
        ];
    }
}
