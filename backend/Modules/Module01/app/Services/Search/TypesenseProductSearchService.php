<?php

declare(strict_types=1);

namespace Modules\Module01\App\Services\Search;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Module01\App\Interfaces\ProductSearchInterface;
use Throwable;

final class TypesenseProductSearchService implements ProductSearchInterface
{
    /** @var string[] */
    private const array ALLOWED_SORTS = ['relevance', 'created_at', 'updated_at', 'price', 'stock', 'rating'];

    public function search(array $params = [], int $perPage = 10): LengthAwarePaginator
    {
        $q = (string) ($params['q'] ?? $params['search'] ?? '');
        $sortField = (string) (
            $params['sort_field']
            ?? $params['sort_by'] ?? 'created_at');
        $sortDirection = (string) (
            $params['sort_direction']
            ?? $params['sort_dir'] ?? 'desc');
        $sortDirection = mb_strtolower(
            $sortDirection
        ) === 'asc' ? 'asc' : 'desc';

        $builder = Product::search($q);

        if (array_key_exists('is_active', $params) && $params['is_active'] !== null) {
            $builder->where('is_active', (bool) $params['is_active']);
        }

        if (! empty($params['brand'])) {
            $builder->where('brand', (string) $params['brand']);
        }

        if (! empty($params['model'])) {
            $builder->where('model', (string) $params['model']);
        }

        // Advanced filters via Typesense filter_by
        /** @var list<string> $filterParts */
        $filterParts = [];
        $categoryRaw = $params['category'] ?? null;
        if (is_string($categoryRaw) && $categoryRaw !== '') {
            $escaped = str_replace('"', '\\"', $categoryRaw);
            $filterParts[] = sprintf('metadata.category:="%s"', $escaped);
        }

        $priceMin = $params['price_min'] ?? null;
        if (is_numeric($priceMin)) {
            $filterParts[] = sprintf('price:>=%s', (string) $priceMin);
        }

        $priceMax = $params['price_max'] ?? null;
        if (is_numeric($priceMax)) {
            $filterParts[] = sprintf('price:<=%s', (string) $priceMax);
        }

        // attributes as key=value or key=[v1,v2]
        $attributes = $params['attributes'] ?? null;
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $keyStr = (string) $key;
                if ($keyStr === '') {
                    continue;
                }

                if (is_array($value)) {
                    /** @var list<string> $vals */
                    $vals = [];
                    foreach ($value as $v) {
                        if (is_string($v) || is_numeric($v)) {
                            $vals[] = sprintf('\"%s\"', (string) $v);
                        }
                    }

                    if ($vals !== []) {
                        $filterParts[] = sprintf('metadata.attributes.%s:=[%s]', $keyStr, implode(',', $vals));
                    }
                } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                    $valStr = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                    if (is_string($value)) {
                        $valStr = sprintf('\"%s\"', str_replace('"', '\\"', $value));
                    }

                    $filterParts[] = sprintf('metadata.attributes.%s:=%s', $keyStr, $valStr);
                }
            }
        }

        if ($filterParts !== []) {
            $builder->options['filter_by'] = implode(' && ', $filterParts);
        }

        if (! in_array($sortField, self::ALLOWED_SORTS, true)) {
            $sortField = 'created_at';
        }

        // When relevance selected, avoid forcing sort_by so Typesense uses text relevance
        if ($sortField !== 'relevance') {
            // Map rating to nested metadata.rating if requested
            $field = $sortField === 'rating' ? 'metadata.rating' : $sortField;
            $builder->orderBy($field, $sortDirection);
        }

        $perPage = (int) ($params['per_page'] ?? $perPage);

        // Intentar búsqueda con Typesense; si falla, hacer fallback a SQLite
        try {
            $paginator = $builder->paginate($perPage);

            // Si no hay resultados y el driver de Scout no es Typesense, hacer fallback
            $scoutDriver = mb_strtolower((string) env('SCOUT_DRIVER'));
            if ($q !== '' && ($paginator->total() === 0 || count($paginator->items()) === 0) && $scoutDriver !== 'typesense') {
                Log::info('Typesense returned empty and driver not typesense, falling back to SQLite', [
                    'scout_driver' => $scoutDriver,
                    'q' => $q,
                ]);
                $fallback = new SqliteProductSearchService();

                return $fallback->search($params, $perPage);
            }

            return $paginator;
        } catch (Throwable $throwable) {
            Log::warning('Typesense search failed, falling back to SQLite', [
                'error' => $throwable->getMessage(),
                'exception' => $throwable::class,
            ]);

            $fallback = new SqliteProductSearchService();

            return $fallback->search($params, $perPage);
        }
    }
}
