<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;
use Modules\Inventory\App\Models\Product;

final class DescribeProductPrompt extends Prompt
{
    protected string $description = 'Redacta un resumen claro y útil de un producto para el usuario final.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('sku', 'SKU del producto a describir', true),
            new Argument('show_price', 'Incluir precio en la descripción', false),
            new Argument('include_specifications', 'Incluir especificaciones básicas si existen', false),
        ];
    }

    /**
     * @return iterable<int, Response>
     */
    public function handle(Request $request): iterable
    {
        $data = $request->validate([
            'sku' => ['required', 'string'],
            'show_price' => ['nullable', 'boolean'],
            'include_specifications' => ['nullable', 'boolean'],
        ]);

        /** @var array{sku: string, show_price?: bool|null, include_specifications?: bool|null} $data */
        $sku = $data['sku'];
        $showPrice = $data['show_price'] ?? false;
        $includeSpecifications = $data['include_specifications'] ?? false;

        $product = Product::query()->where('sku', $sku)->first();
        if (! $product) {
            return [
                Response::text(sprintf('No se encontró el producto con SKU %s.', $sku))->asAssistant(),
            ];
        }

        $price = isset($product->price)
            ? number_format((float) $product->price, 2, '.', '')
            : null;

        $specifications = [];
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        foreach (['color', 'capacidad', 'tamaño', 'peso'] as $key) {
            $value = $metadata[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $specifications[] = ucfirst($key).': '.$value;
            }
        }

        $lines = [];
        $lines[] = sprintf(
            'Producto: %s (SKU %s). Marca: %s. Modelo: %s.',
            $product->name,
            $product->sku,
            $product->brand ?? 'N/A',
            $product->model ?? '—'
        );

        if ($showPrice && $price !== null) {
            $lines[] = 'Precio: $'.$price;
        }

        $lines[] = 'Stock disponible: '.(int) ($product->stock ?? 0).($product->is_active ? '' : ' (inactivo)');

        if ($includeSpecifications && $specifications !== []) {
            $lines[] = 'Características clave: '.implode('; ', $specifications);
        }

        $lines[] = 'Redacta un resumen objetivo en 2–4 frases, evitando jerga técnica innecesaria. Si faltan datos, no lo inventes.';

        $message = implode("\n", $lines);

        return [
            Response::text($message)->asAssistant(),
        ];
    }
}
