<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Assistant\App\Events\McpToolDebug;
use Modules\Inventory\App\Interfaces\InventoryManagerInterface;
use Throwable;

#[IsReadOnly]
#[IsIdempotent]
final class SearchProductTool extends Tool
{
    protected string $description = 'Busca productos en el catálogo por término y filtros básicos. Devuelve un resumen legible con SKU, nombre, precio y stock.';

    public function inputSchema(): JsonSchema
    {
        return JsonSchema::object([
            'search' => JsonSchema::string()->description(
                'Término de búsqueda para nombre, SKU, marca, modelo o código de barras.'
            )->nullable(),
            'is_active' => JsonSchema::boolean()->description(
                'Si se especifica, filtra por estado activo/inactivo.'
            )->nullable(),
            'sort_field' => JsonSchema::string()->description(
                'Campo de ordenación (id, sku, name, price, stock, is_active, created_at, updated_at).'
            )->default('created_at'),
            'sort_direction' => JsonSchema::string()->description(
                'Dirección de ordenación: asc o desc.'
            )->default('desc'),
            'per_page' => JsonSchema::integer()->description(
                'Cantidad de resultados a devolver.'
            )->default(10),
        ]);
    }

    public function handle(
        Request $request,
        InventoryManagerInterface $inventory
    ): Response {
        Event::dispatch(new McpToolDebug('SearchProductTool', 'start'));
        $data = $request->validate([
            'search' => ['nullable', 'string', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'sort_field' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $params = [
            'search' => $data['search'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'sort_field' => $data['sort_field'] ?? 'created_at',
            'sort_direction' => $data['sort_direction'] ?? 'desc',
            'per_page' => $data['per_page'] ?? 10,
        ];

        try {
            $paginator = $inventory->getAllProducts($params);
        } catch (Throwable $throwable) {
            Log::error(
                'SearchProductTool inventory retrieval failed',
                [
                    'params' => $params,
                    'exception' => $throwable::class,
                    'message' => $throwable->getMessage(),
                    'trace' => $throwable->getTraceAsString(),
                ]
            );

            throw $throwable;
        }

        $total = (int) $paginator->total();
        $perPage = (int) $paginator->perPage();

        if ($total === 0) {
            $suggestions = [
                'Prueba con menos palabras o un modelo más específico.',
                'Si conoces el SKU, úsalo directamente.',
            ];

            $message = 'No se encontraron productos que coincidan con tu búsqueda.'
                ."\n".mcp_suggestion_text($suggestions);

            return Response::text($message)->asAssistant();
        }

        $header = mcp_list_header($total);

        $lines = [];
        foreach ($paginator->items() as $product) {
            /** @var \Modules\Inventory\App\Models\Product $product */
            $lines[] = mcp_product_line(
                (string) $product->sku,
                (string) $product->name,
                (string) ($product->brand ?? 'N/A'),
                (string) ($product->model ?? ''),
                (float) $product->price,
                (int) $product->stock,
                (bool) $product->is_active
            );
        }

        $text = $header."\n".implode("\n", $lines);

        Event::dispatch(new McpToolDebug('SearchProductTool', 'end', [
            'total' => $total,
            'per_page' => $perPage,
        ]));

        return Response::text($text)->asAssistant();
    }
}
