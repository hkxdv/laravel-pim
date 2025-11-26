<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Services\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\App\Interfaces\ProductSearchInterface;
use Modules\Inventory\App\Models\Product;
use Throwable;

final class TypesenseProductSearchService implements ProductSearchInterface
{
    /** @var string[] */
    private const array ALLOWED_SORTS = [
        'relevance',
        'created_at',
        'updated_at',
        'price',
        'stock',
        'rating',
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
            ? $params['q']
            : (is_string($params['search'] ?? null)
                ? $params['search'] : ''
            );

        $sortField = is_string($params['sort_field'] ?? null)
            ? $params['sort_field']
            : (is_string($params['sort_by'] ?? null)
                ? $params['sort_by'] : 'created_at'
            );

        $sortDirection = is_string($params['sort_direction'] ?? null)
            ? $params['sort_direction']
            : (is_string($params['sort_dir'] ?? null)
                ? $params['sort_dir'] : 'desc'
            );

        $sortDirection = mb_strtolower($sortDirection) === 'asc'
            ? 'asc' : 'desc';

        $builder = Product::search($q);

        if (
            array_key_exists('is_active', $params)
            && $params['is_active'] !== null
        ) {
            $builder->where('is_active', (bool) $params['is_active']);
        }

        $brandRaw = $params['brand'] ?? null;
        if (is_string($brandRaw) && $brandRaw !== '') {
            $builder->where('brand', $brandRaw);
        }

        $modelRaw = $params['model'] ?? null;
        if (is_string($modelRaw) && $modelRaw !== '') {
            $builder->where('model', $modelRaw);
        }

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
                        $filterParts[] = sprintf(
                            'metadata.attributes.%s:=[%s]',
                            $keyStr,
                            implode(',', $vals)
                        );
                    }
                } elseif (
                    is_string($value) || is_numeric($value) || is_bool($value)
                ) {
                    $valStr = is_bool($value)
                        ? ($value ? 'true' : 'false') : (string) $value;
                    if (is_string($value)) {
                        $valStr = sprintf(
                            '\"%s\"',
                            str_replace('"', '\\"', $value)
                        );
                    }

                    $filterParts[] = sprintf(
                        'metadata.attributes.%s:=%s',
                        $keyStr,
                        $valStr
                    );
                }
            }
        }

        if ($filterParts !== []) {
            $builder->options['filter_by'] = implode(' && ', $filterParts);
        }

        if (! in_array($sortField, self::ALLOWED_SORTS, true)) {
            $sortField = 'created_at';
        }

        // Cuando se ordena por relevancia, evitar forzar sort_by para que Typesense use la relevancia de texto
        if ($sortField !== 'relevance') {
            // Mapear rating a metadata.rating si se solicita
            $field = $sortField === 'rating'
                ? 'metadata.rating' : $sortField;
            $builder->orderBy($field, $sortDirection);
        }

        $perPage = is_numeric(
            $params['per_page'] ?? null
        ) ? (int) $params['per_page'] : $perPage;

        // Intentar búsqueda con Typesense; si falla, hacer fallback a SQLite
        try {
            $paginatorContract = $builder->paginate($perPage);

            // Si no hay resultados y el driver de Scout no es Typesense, hacer fallback
            $scoutDriverRaw = \Illuminate\Support\Facades\Config::get('scout.driver', '');
            $scoutDriver = is_string($scoutDriverRaw)
                ? mb_strtolower($scoutDriverRaw) : '';

            if (
                $q !== ''
                && (
                    $paginatorContract->total() === 0
                    || count($paginatorContract->items()) === 0
                )
                && $scoutDriver !== 'typesense'
            ) {
                Log::info(
                    'Typesense returned empty and driver not typesense, falling back to SQLite',
                    [
                        'scout_driver' => $scoutDriver,
                        'q' => $q,
                    ]
                );

                $fallback = new SqliteProductSearchService();

                return $fallback->search($params, $perPage);
            }

            /** @var array<int, Product> $items */
            $items = $paginatorContract->items();

            return new LengthAwarePaginator(
                items: $items,
                total: $paginatorContract->total(),
                perPage: $paginatorContract->perPage(),
                currentPage: $paginatorContract->currentPage(),
                options: [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        } catch (Throwable $throwable) {
            Log::warning(
                'Typesense search failed, falling back to SQLite',
                [
                    'error' => $throwable->getMessage(),
                    'exception' => $throwable::class,
                ]
            );

            $fallback = new SqliteProductSearchService();

            return $fallback->search($params, $perPage);
        }
    }
}
