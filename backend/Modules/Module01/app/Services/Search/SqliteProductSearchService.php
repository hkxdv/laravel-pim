<?php

declare(strict_types=1);

namespace Modules\Module01\App\Services\Search;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Module01\App\Interfaces\ProductSearchInterface;

final class SqliteProductSearchService implements ProductSearchInterface
{
    /** @var string[] */
    private const array ALLOWED_SORTS = ['name', 'sku', 'brand', 'model', 'stock', 'price', 'created_at', 'updated_at'];

    public function search(array $params = [], int $perPage = 10): LengthAwarePaginator
    {
        $q = (string) ($params['q'] ?? $params['search'] ?? '');
        $sortField = (string) ($params['sort_field'] ?? $params['sort_by'] ?? 'created_at');
        $sortDirection = (string) ($params['sort_direction'] ?? $params['sort_dir'] ?? 'desc');
        $sortDirection = mb_strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query = Product::query()
            ->select(['id', 'sku', 'name', 'brand', 'model', 'barcode', 'price', 'stock', 'is_active', 'created_at', 'updated_at']);

        if ($q !== '') {
            $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', sprintf('%%%s%%', $q))
                    ->orWhere('sku', 'like', sprintf('%%%s%%', $q))
                    ->orWhere('brand', 'like', sprintf('%%%s%%', $q))
                    ->orWhere('model', 'like', sprintf('%%%s%%', $q))
                    ->orWhere('barcode', 'like', sprintf('%%%s%%', $q));
            });
        }

        if (array_key_exists('is_active', $params) && $params['is_active'] !== null) {
            $query->where('is_active', (bool) $params['is_active']);
        }

        if (! empty($params['brand'])) {
            $query->where('brand', (string) $params['brand']);
        }

        if (! empty($params['model'])) {
            $query->where('model', (string) $params['model']);
        }

        if (! in_array($sortField, self::ALLOWED_SORTS, true)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = (int) ($params['per_page'] ?? $perPage);

        return $query->paginate($perPage);
    }
}
