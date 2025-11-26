<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Event;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Assistant\App\Events\McpToolDebug;
use Modules\Inventory\App\Models\Product;

#[IsReadOnly]
#[IsIdempotent]
final class CreatePreOrderTool extends Tool
{
    protected string $description = 'Crea un borrador de pedido a partir de un mapa SKU→cantidad, aplicando un multiplicador opcional.';

    public function inputSchema(): JsonSchema
    {
        return JsonSchema::object([
            'items' => JsonSchema::object()->description('Mapa SKU → cantidad')->required(),
            'multiplier' => JsonSchema::string()->description('Multiplicador de precio del usuario (ej: 0.9 para 10% descuento)')->nullable(),
            'round' => JsonSchema::boolean()->description('Si true, redondea a 2 decimales')->default(true),
            'ignore_inactive' => JsonSchema::boolean()->description('Si true, excluye productos inactivos del borrador')->default(true),
        ])->withoutAdditionalProperties()->description('Parámetros para crear un pedido preliminar');
    }

    public function handle(Request $request): Response
    {
        Event::dispatch(new McpToolDebug('CreatePreOrderTool', 'start'));
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'multiplier' => ['nullable', 'numeric'],
            'round' => ['sometimes', 'boolean'],
            'ignore_inactive' => ['sometimes', 'boolean'],
        ]);

        /** @var array{items: array<string, int|numeric-string>, multiplier?: float|int|numeric-string|null, round?: bool, ignore_inactive?: bool} $validated */
        $items = $validated['items'];
        $multiplierRaw = $validated['multiplier'] ?? null;
        $multiplier = $multiplierRaw === null ? 1.0 : (float) $multiplierRaw;
        $round = ($validated['round'] ?? null) ?? true;
        $ignoreInactive = ($validated['ignore_inactive'] ?? null) ?? true;

        $lines = [];
        $missing = [];
        $total = 0.0;

        foreach ($items as $sku => $qtyRaw) {
            $qty = (int) $qtyRaw;
            if ($qty < 1) {
                $qty = 1;
            }

            $product = Product::query()->where('sku', $sku)->first();
            if (! $product) {
                $missing[] = $sku;

                continue;
            }

            if ($ignoreInactive && ! (bool) $product->is_active) {
                $missing[] = $sku;

                continue;
            }

            $unitBasePrice = (float) $product->price;
            $unitFinalPrice = $unitBasePrice * $multiplier;
            $lineTotal = $unitFinalPrice * $qty;

            if ($round) {
                $unitBasePrice = round($unitBasePrice, 2);
                $unitFinalPrice = round($unitFinalPrice, 2);
                $lineTotal = round($lineTotal, 2);
            }

            $lines[] = [
                'sku' => $sku,
                'product_name' => $product->name,
                'is_active' => (bool) $product->is_active,
                'quantity' => $qty,
                'unit_base_price' => $unitBasePrice,
                'multiplier' => $multiplier,
                'unit_final_price' => $unitFinalPrice,
                'line_total' => $lineTotal,
            ];

            $total += $lineTotal;
        }

        if ($round) {
            $total = round($total, 2);
        }

        $payload = [
            'summary' => [
                'lines_count' => count($lines),
                'missing_count' => count($missing),
                'total' => $total,
            ],
            'lines' => $lines,
            'missing_items' => $missing,
        ];

        Event::dispatch(new McpToolDebug('CreatePreOrderTool', 'end', [
            'lines_count' => count($lines),
            'missing_count' => count($missing),
            'total' => $total,
        ]));

        return mcp_ok($payload, 'Borrador de pedido generado');
    }
}
