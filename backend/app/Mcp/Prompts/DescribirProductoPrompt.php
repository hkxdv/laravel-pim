<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Models\Product;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

final class DescribirProductoPrompt extends Prompt
{
    /**
     * Breve descripción del propósito del prompt.
     */
    protected string $description = 'Redacta un resumen claro y útil de un producto para el usuario final.';

    /**
     * Argumentos que acepta el prompt.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('sku', 'SKU del producto a describir', true),
            new Argument('mostrar_precio', 'Incluir precio en la descripción', false),
            new Argument('incluir_especificaciones', 'Incluir especificaciones básicas si existen', false),
        ];
    }

    /**
     * Genera uno o más mensajes que servirán de entrada al LLM.
     *
     * @return iterable<int, Response>
     */
    public function handle(Request $request): iterable
    {
        $data = $request->validate([
            'sku' => ['required', 'string'],
            'mostrar_precio' => ['nullable', 'boolean'],
            'incluir_especificaciones' => ['nullable', 'boolean'],
        ]);

        /** @var array{sku: string, mostrar_precio?: bool|null, incluir_especificaciones?: bool|null} $data */
        $sku = $data['sku'];
        $mostrarPrecio = $data['mostrar_precio'] ?? false;
        $incluirEspecificaciones = $data['incluir_especificaciones'] ?? false;

        $product = Product::query()->where('sku', $sku)->first();
        if (! $product) {
            return [
                Response::text(sprintf('No se encontró el producto con SKU %s.', $sku))->asAssistant(),
            ];
        }

        $precio = isset($product->price)
            ? number_format((float) $product->price, 2, '.', '')
            : null;

        $especificaciones = [];
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        foreach (['color', 'capacidad', 'tamaño', 'peso'] as $key) {
            $value = $metadata[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $especificaciones[] = ucfirst($key).': '.$value;
            }
        }

        $lineas = [];
        $lineas[] = sprintf(
            'Producto: %s (SKU %s). Marca: %s. Modelo: %s.',
            $product->name,
            $product->sku,
            $product->brand ?? 'N/A',
            $product->model ?? '—'
        );

        if ($mostrarPrecio && $precio !== null) {
            $lineas[] = 'Precio: $'.$precio;
        }

        $lineas[] = 'Stock disponible: '.(int) ($product->stock ?? 0).($product->is_active ? '' : ' (inactivo)');

        if ($incluirEspecificaciones && $especificaciones !== []) {
            $lineas[] = 'Características clave: '.implode('; ', $especificaciones);
        }

        $lineas[] = 'Redacta un resumen objetivo en 2–4 frases, evitando jerga técnica innecesaria. Si faltan datos, no lo inventes.';

        $mensaje = implode("\n", $lineas);

        return [
            Response::text($mensaje)->asAssistant(),
        ];
    }
}
