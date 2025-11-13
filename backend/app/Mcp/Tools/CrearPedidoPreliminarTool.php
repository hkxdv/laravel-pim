<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class CrearPedidoPreliminarTool extends Tool
{
    /**
     * Descripción del tool para el LLM.
     */
    protected string $description = 'Crea un borrador de pedido a partir de un mapa SKU→cantidad, aplicando un multiplicador opcional.';

    public function inputSchema(): JsonSchema
    {
        return JsonSchema::object([
            'items' => JsonSchema::object()->description(
                'Mapa SKU → cantidad'
            )->required(),
            'multiplier' => JsonSchema::string()->description(
                'Multiplicador de precio del usuario (ej: 0.9 para 10% descuento)'
            )->nullable(),
            'redondear' => JsonSchema::boolean()->description(
                'Si true, redondea a 2 decimales'
            )->default(true),
            'ignorar_inactivos' => JsonSchema::boolean()->description(
                'Si true, excluye productos inactivos del borrador'
            )->default(true),
        ])->withoutAdditionalProperties()->description('Parámetros para crear un pedido preliminar');
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'multiplier' => ['nullable', 'numeric'],
            'redondear' => ['sometimes', 'boolean'],
            'ignorar_inactivos' => ['sometimes', 'boolean'],
        ]);

        /** @var array{items: array<string, int|numeric-string>, multiplier?: float|int|numeric-string|null, redondear?: bool, ignorar_inactivos?: bool} $validated */
        $items = $validated['items'];
        $multiplierRaw = $validated['multiplier'] ?? null;
        $multiplier = $multiplierRaw === null ? 1.0 : (float) $multiplierRaw;
        $redondear = $validated['redondear'] ?? true;
        $ignorarInactivos = $validated['ignorar_inactivos'] ?? true;

        $lineas = [];
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

            if ($ignorarInactivos && ! (bool) $product->is_active) {
                // Lo tratamos como faltante si está inactivo y se deben ignorar
                $missing[] = $sku;

                continue;
            }

            $unitBasePrice = (float) $product->price;
            $unitFinalPrice = $unitBasePrice * $multiplier;
            $lineTotal = $unitFinalPrice * $qty;

            if ($redondear) {
                $unitBasePrice = round($unitBasePrice, 2);
                $unitFinalPrice = round($unitFinalPrice, 2);
                $lineTotal = round($lineTotal, 2);
            }

            $lineas[] = [
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

        if ($redondear) {
            $total = round($total, 2);
        }

        $payload = [
            'status' => 'ok',
            'summary' => [
                'lines_count' => count($lineas),
                'missing_count' => count($missing),
                'total' => $total,
            ],
            'lines' => $lineas,
            'missing_items' => $missing,
            'message' => 'Borrador de pedido generado',
        ];

        return Response::json($payload)->asAssistant();
    }
}
