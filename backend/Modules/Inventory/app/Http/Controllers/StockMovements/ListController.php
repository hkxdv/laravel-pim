<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers\StockMovements;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Models\StockMovement;

/**
 * Controlador para la lista de movimientos de stock.
 */
final class ListController extends InventoryBaseController
{
    /**
     * Muestra el listado paginado de movimientos de stock.
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $params = [
            'product_id' => $request->query('product_id'),
            'per_page' => $request->query('per_page'),
        ];

        $perPage = (int) ($params['per_page'] ?? 10);
        $productId = (int) ($params['product_id'] ?? 0);

        $query = StockMovement::query()->with('product');
        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        $movements = $query->latest('performed_at')->paginate($perPage);

        $additionalData = [
            'movements' => $movements,
            'filters' => $params,
        ];

        return $this->prepareAndRenderModuleView(
            view: 'stock-movement/list',
            request: $request,
            additionalData: $additionalData,
        );
    }
}
