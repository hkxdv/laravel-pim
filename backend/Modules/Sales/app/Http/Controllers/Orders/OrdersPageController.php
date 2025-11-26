<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Controllers\Orders;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Sales\App\Http\Controllers\SalesBaseController;
use Modules\Sales\App\Models\SalesOrder;

final class OrdersPageController extends SalesBaseController
{
    /**
     * Renderiza el listado de órdenes.
     */
    public function list(Request $request): InertiaResponse
    {
        $perPage = max(1, (int) $request->integer('per_page', 10));
        $page = max(1, (int) $request->integer('page', 1));
        $status = (string) $request->string('status')->toString();

        $query = SalesOrder::query()
            ->with(['items.product:id,sku,name,stock'])
            ->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        foreach ($orders->items() as $order) {
            /** @var SalesOrder $order */
            $items = $order->items;
            $order->setAttribute('items_count', $items->count());

            $stockOk = true;
            foreach ($items as $it) {
                $prod = $it->product;
                if ($prod && (int) ($prod->stock ?? 0) < (int) ($it->qty ?? 0)) {
                    $stockOk = false;
                    break;
                }
            }

            $order->setAttribute('stock_ok', $stockOk);
        }

        $filters = [
            'status' => $status !== '' ? $status : null,
            'sort_field' => 'created_at',
            'sort_direction' => 'desc',
        ];

        return $this->prepareAndRenderModuleView(
            view: 'orders/list',
            request: $request,
            additionalData: [
                'orders' => $orders,
                'filters' => $filters,
            ],
            routeSuffix: 'orders.list'
        );
    }

    /**
     * Renderiza el detalle de una orden específica.
     */
    public function detail(Request $request, SalesOrder $order): InertiaResponse
    {
        $order->load(['items.product', 'staffUser', 'deliveredByUser']);

        $idRaw = $order->getKey();
        $idStr = is_scalar($idRaw) ? (string) $idRaw : '';

        return $this->prepareAndRenderModuleView(
            view: 'orders/detail',
            request: $request,
            additionalData: [
                'order' => $order,
            ],
            routeSuffix: 'orders.detail',
            routeParams: ['order' => $order->getKey()],
            dynamicTitleData: ['dynamicTitle' => 'Orden #'.$idStr]
        );
    }
}
