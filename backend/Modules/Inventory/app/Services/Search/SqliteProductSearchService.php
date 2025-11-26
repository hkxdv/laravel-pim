<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Services\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\App\Interfaces\ProductSearchInterface;
use Modules\Inventory\App\Models\Product;

final class SqliteProductSearchService implements ProductSearchInterface
{
    /** @var string[] */
    private const array ALLOWED_SORTS = [
        'name',
        'sku',
        'brand',
        'model',
        'stock',
        'price',
        'created_at',
        'updated_at',
    ];

    /**
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $q = is_string($params['q'] ?? null)
            ? $params['q'] : (is_string($params['search'] ?? null)
                ? $params['search'] : ''
            );

        $sortField = is_string($params['sort_field'] ?? null)
            ? $params['sort_field'] : (is_string($params['sort_by'] ?? null)
                ? $params['sort_by'] : 'created_at'
            );

        $sortDirection = is_string($params['sort_direction'] ?? null)
            ? $params['sort_direction'] : (is_string($params['sort_dir'] ?? null)
                ? $params['sort_dir'] : 'desc'
            );

        $sortDirection = mb_strtolower($sortDirection) === 'asc'
            ? 'asc' : 'desc';

        $query = Product::query()
            ->select([
                'id',
                'sku',
                'name',
                'brand',
                'model',
                'barcode',
                'price',
                'stock',
                'is_active',
                'created_at',
                'updated_at',
            ]);

        if ($q !== '') {
            $tokens = explode(' ', $q);
            $query->where(function ($inner) use ($tokens): void {
                foreach ($tokens as $token) {
                    $inner->where(function ($deeper) use ($token): void {
                        $deeper->where('name', 'like', sprintf('%%%s%%', $token))
                            ->orWhere('sku', 'like', sprintf('%%%s%%', $token))
                            ->orWhere('brand', 'like', sprintf('%%%s%%', $token))
                            ->orWhere('model', 'like', sprintf('%%%s%%', $token))
                            ->orWhere('barcode', 'like', sprintf('%%%s%%', $token));
                    });
                }
            });
        }

        if (
            array_key_exists('is_active', $params)
            && $params['is_active'] !== null
        ) {
            $query->where('is_active', (bool) $params['is_active']);
        }

        $brandRaw = $params['brand'] ?? null;
        if (is_string($brandRaw) && $brandRaw !== '') {
            $query->where('brand', $brandRaw);
        }

        $modelRaw = $params['model'] ?? null;
        if (is_string($modelRaw) && $modelRaw !== '') {
            $query->where('model', $modelRaw);
        }

        if (! in_array($sortField, self::ALLOWED_SORTS, true)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = is_numeric($params['per_page'] ?? null)
            ? (int) $params['per_page'] : $perPage;

        return $query->paginate($perPage);
    }
}
