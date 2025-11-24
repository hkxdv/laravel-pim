<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Scout\EngineManager;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Services\Search\ProductSearchResolver;

/**
 * Devuelve sugerencias de productos para autocompletado.
 */
final class SuggestController extends InventoryBaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $qRaw = $request->query('q', '');
        $q = is_string($qRaw) ? $qRaw : '';
        $perRaw = $request->query('per_page', 5);
        $perPage = is_numeric($perRaw) ? (int) $perRaw : 5;

        $mode = ProductSearchResolver::currentMode();
        $suggestions = [];

        if ($mode === 'typesense' && $q !== '') {
            // Usa el motor Typesense para obtener sugerencias con resaltado
            $builder = Product::search($q);
            $builder->options['highlight_fields'] = 'name,sku,brand,model';
            $builder->options['highlight_start_tag'] = '<mark>';
            $builder->options['highlight_end_tag'] = '</mark>';

            /** @var EngineManager $manager */
            $manager = app(EngineManager::class);
            /** @var \App\Scout\TypesenseEngine<Product> $engine */
            $engine = $manager->engine('typesense');
            /** @var \Laravel\Scout\Builder<Model> $builderGeneric */
            $builderGeneric = $builder;
            /** @var array<string, mixed> $response */
            $response = $engine->paginate($builderGeneric, $perPage, 1);
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
                    if (
                        is_string($field)
                        && is_string($snippet)
                        && $field !== ''
                    ) {
                        $hlMap[$field] = $snippet;
                    }
                }

                $metadata = (array) ($doc['metadata'] ?? []);
                $name = $doc['name'] ?? null;
                $suggestions[] = [
                    'id' => $doc['id'] ?? null,
                    'name' => is_string($name) ? $name : '',
                    'sku' => $doc['sku'] ?? '',
                    'brand' => $doc['brand'] ?? null,
                    'model' => $doc['model'] ?? null,
                    'price' => $doc['price'] ?? null,
                    'stock' => $doc['stock'] ?? null,
                    'image_url' => (is_string(
                        $metadata['image_url'] ?? null
                    )) ? $metadata['image_url'] : null,
                    'highlight' => [
                        'name' => $hlMap['name'] ?? null,
                        'sku' => $hlMap['sku'] ?? null,
                        'brand' => $hlMap['brand'] ?? null,
                        'model' => $hlMap['model'] ?? null,
                    ],
                ];
            }
        } else {
            $params = [
                'search' => $q,
                'per_page' => $perPage,
                'sort_field' => 'relevance',
                'sort_direction' => 'desc',
            ];

            $results = $this->inventoryManager->getAllProducts(
                $params,
                $perPage
            );

            foreach ($results->items() as $item) {
                /** @var array<string, mixed> $row */
                $row = (array) $item;
                $suggestions[] = [
                    'id' => $row['id'] ?? null,
                    'name' => is_string(
                        $row['name'] ?? null
                    ) ? $row['name'] : '',
                    'sku' => $row['sku'] ?? '',
                    'brand' => $row['brand'] ?? null,
                    'model' => $row['model'] ?? null,
                    'price' => $row['price'] ?? null,
                    'stock' => $row['stock'] ?? null,
                    'image_url' => is_array(
                        $row['metadata'] ?? null
                    ) ? ($row['metadata']['image_url'] ?? null) : null,
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
