<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers\Products;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Services\Search\ProductSearchResolver;

/**
 * Controlador para la lista de productos.
 */
final class ListController extends InventoryBaseController
{
    /**
     * Muestra el listado paginado de productos.
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $params = [
            'search' => $request->query('search'),
            'sort_field' => $request->query('sort_field'),
            'sort_direction' => $request->query('sort_direction'),
            'per_page' => $request->query('per_page'),
            'is_active' => $request->query('is_active'),
        ];

        $perPage = (int) ($params['per_page'] ?? 10);
        $products = $this->inventoryManager->getAllProducts($params, $perPage);

        $additionalData = [
            'products' => $products,
            'filters' => $params,
            'debug' => [
                'search_mode' => ProductSearchResolver::currentMode(),
            ],
        ];

        return $this->prepareAndRenderModuleView(
            view: 'product/list',
            request: $request,
            additionalData: $additionalData
        );
    }
}
