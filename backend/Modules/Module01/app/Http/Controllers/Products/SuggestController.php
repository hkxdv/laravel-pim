<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers\Products;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Scout\EngineManager;
use Modules\Module01\App\Http\Controllers\Module01BaseController;
use Modules\Module01\App\Services\Search\ProductSearchResolver;

/**
 * Devuelve sugerencias de productos para autocompletado.
 */
final class SuggestController extends Module01BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $perPage = (int) ($request->query('per_page', 5));

        $mode = ProductSearchResolver::currentMode();
        $suggestions = [];

        if ($mode === 'typesense' && $q !== '') {
            // Use Typesense engine directly to retrieve highlight snippets for suggestions
            $builder = Product::search($q);
            $builder->options['highlight_fields'] = 'name,sku,brand,model';
            $builder->options['highlight_start_tag'] = '<mark>';
            $builder->options['highlight_end_tag'] = '</mark>';

            /** @var EngineManager $manager */
            $manager = app(EngineManager::class);
            /** @var \App\Scout\TypesenseEngine $engine */
            $engine = $manager->engine('typesense');

            /** @var array<string, mixed> $response */
            $response = $engine->paginate($builder, $perPage, 1);
            /** @var array<int, array<string, mixed>> $hits */
            $hits = (array) ($response['hits'] ?? []);

            foreach ($hits as $hit) {
                /** @var array<string, mixed> $doc */
                $doc = (array) ($hit['document'] ?? []);
                /** @var array<int, array<string, mixed>> $highlights */
                $highlights = (array) ($hit['highlights'] ?? []);

                /** @var array<string, string> $hlMap */
                $hlMap = [];
                foreach ($highlights as $hl) {
                    $field = $hl['field'] ?? null;
                    $snippet = $hl['snippet'] ?? ($hl['value'] ?? null);
                    if (is_string($field) && is_string($snippet) && $field !== '') {
                        $hlMap[$field] = $snippet;
                    }
                }

                $metadata = (array) ($doc['metadata'] ?? []);
                $suggestions[] = [
                    'id' => $doc['id'] ?? null,
                    'name' => (string) ($doc['name'] ?? ''),
                    'sku' => $doc['sku'] ?? '',
                    'brand' => $doc['brand'] ?? null,
                    'model' => $doc['model'] ?? null,
                    'price' => $doc['price'] ?? null,
                    'stock' => $doc['stock'] ?? null,
                    'image_url' => $metadata['image_url'] ?? null,
                    'highlight' => [
                        'name' => $hlMap['name'] ?? null,
                        'sku' => $hlMap['sku'] ?? null,
                        'brand' => $hlMap['brand'] ?? null,
                        'model' => $hlMap['model'] ?? null,
                    ],
                ];
            }
        } else {
            // Fallback: use unified inventory manager (SQLite or non-highlight search)
            $params = [
                'search' => $q,
                'per_page' => $perPage,
                'sort_field' => 'relevance',
                'sort_direction' => 'desc',
            ];

            $results = $this->inventoryManager->getAllProducts($params, $perPage);

            foreach ($results->items() as $item) {
                /** @var array<string, mixed> $row */
                $row = (array) $item;
                $suggestions[] = [
                    'id' => $row['id'] ?? null,
                    'name' => (string) ($row['name'] ?? ''),
                    'sku' => $row['sku'] ?? '',
                    'brand' => $row['brand'] ?? null,
                    'model' => $row['model'] ?? null,
                    'price' => $row['price'] ?? null,
                    'stock' => $row['stock'] ?? null,
                    'image_url' => $row['metadata']['image_url'] ?? null,
                ];
            }
        }

        return response()->json([
            'mode' => $mode,
            'q' => $q,
            'count' => count($suggestions),
            'items' => $suggestions,
        ]);
    }
}
