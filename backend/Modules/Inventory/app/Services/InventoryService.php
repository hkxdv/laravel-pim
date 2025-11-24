<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\App\Interfaces\InventoryManagerInterface;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Services\Search\ProductSearchResolver;

/**
 * Servicio para la gestión de inventario.
 * Implementa las operaciones definidas en InventoryManagerInterface.
 */
final class InventoryService implements InventoryManagerInterface
{
    /**
     * {@inheritDoc}
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getAllProducts(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        // Usar el motor de búsqueda unificado (Typesense o SQLite) según el modo actual
        // Esto garantiza que el listado refleje Typesense cuando esté habilitado
        $engine = ProductSearchResolver::resolve();

        // Normalizar per_page para el motor
        $perPage = is_numeric(
            $params['per_page'] ?? null
        ) ? (int) $params['per_page'] : $perPage;

        // El motor acepta 'search' o 'q' indistintamente
        return $engine->search($params, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function createProduct(array $data): Product
    {
        return Product::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductById(int $id): ?Product
    {
        return Product::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        $product = Product::query()->find($id);
        if (! $product) {
            return null;
        }

        $product->update($data);

        return $product;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteProduct(int $id): bool
    {
        $product = Product::query()->find($id);
        if (! $product) {
            return false;
        }

        return (bool) $product->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalProducts(): int
    {
        return Product::query()->count();
    }
}
