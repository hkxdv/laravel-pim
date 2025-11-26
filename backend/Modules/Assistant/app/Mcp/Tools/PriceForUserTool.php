<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
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
final class PriceForUserTool extends Tool
{
    protected string $description = 'Calcula el precio para un usuario a partir del SKU, cantidad y multiplicador.';

    public function inputSchema(): JsonSchema
    {
        return JsonSchema::object(
            properties: [
                'sku' => JsonSchema::string()->description(
                    'SKU del producto para el cual se desea consultar el precio.'
                )->required(true),
                'quantity' => JsonSchema::integer()->description(
                    'Cantidad solicitada'
                )->default(1),
                'multiplier' => JsonSchema::string()->description(
                    'Multiplicador de precio del usuario (por ejemplo, 0.8 para 20% descuento)'
                )->nullable(),
                'round' => JsonSchema::boolean()->description(
                    'Si true, redondea a 2 decimales'
                )->default(true),
            ],
        )
            ->withoutAdditionalProperties()
            ->description('Parámetros para calcular el precio por usuario');
    }

    public function handle(Request $request): Response
    {
        Event::dispatch(new McpToolDebug('PriceForUserTool', 'start'));
        $validated = $request->validate([
            'sku' => ['required', 'string'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'multiplier' => ['nullable'],
            'round' => ['sometimes', 'boolean'],
        ]);

        $skuVal = Arr::get($validated, 'sku');
        if (! is_string($skuVal)) {
            return mcp_error('SKU inválido');
        }

        $sku = $skuVal;

        $quantity = 1;
        if (array_key_exists('quantity', $validated)) {
            $qVal = $validated['quantity'];
            $quantity = is_int($qVal) ? $qVal : 1;
        }

        $multiplierRaw = Arr::get($validated, 'multiplier');
        $multiplier = 1.0;
        if ($multiplierRaw !== null) {
            if (is_float($multiplierRaw)) {
                $multiplier = $multiplierRaw;
            } elseif (is_int($multiplierRaw)) {
                $multiplier = (float) $multiplierRaw;
            } elseif (is_string($multiplierRaw)) {
                $multiplier = is_numeric($multiplierRaw) ? (float) $multiplierRaw : 1.0;
            }
        }

        $round = true;
        if (array_key_exists('round', $validated)) {
            $rVal = $validated['round'];
            $round = is_bool($rVal) ? $rVal : true;
        }

        $product = Product::query()->where('sku', $sku)->first();
        if (! $product) {
            return mcp_error('Producto no encontrado', ['sku' => $sku]);
        }

        $unitBasePriceRaw = $product->price;
        $unitBasePrice = (float) $unitBasePriceRaw;

        $unitFinalPrice = $unitBasePrice * $multiplier;
        $subtotal = $unitFinalPrice * $quantity;

        if ($round) {
            $unitBasePrice = round($unitBasePrice, 2);
            $unitFinalPrice = round($unitFinalPrice, 2);
            $subtotal = round($subtotal, 2);
        }

        $payload = [
            'sku' => $sku,
            'product_name' => $product->name,
            'is_active' => (bool) $product->is_active,
            'quantity' => $quantity,
            'unit_base_price' => $unitBasePrice,
            'multiplier' => $multiplier,
            'unit_final_price' => $unitFinalPrice,
            'subtotal' => $subtotal,
        ];

        Event::dispatch(new McpToolDebug('PriceForUserTool', 'end', [
            'sku' => $sku,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]));

        return mcp_ok($payload, 'Precio calculado correctamente');
    }
}
