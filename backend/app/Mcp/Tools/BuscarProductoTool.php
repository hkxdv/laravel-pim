<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Modules\Module01\App\Interfaces\InventoryManagerInterface;

final class BuscarProductoTool extends Tool
{
    /**
     * Descripción del tool para el LLM.
     */
    protected string $description = 'Busca productos en el catálogo por término y filtros básicos. Devuelve un resumen legible con SKU, nombre, precio y stock.';

    /**
     * Esquema JSON de entrada para validar los argumentos.
     */
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

    /**
     * Ejecuta la búsqueda y devuelve un resumen textual.
     */
    public function handle(Request $request, InventoryManagerInterface $inventory): Response
    {
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

        $paginator = $inventory->getAllProducts($params);

        $header = sprintf(
            'Resultados: %d productos encontrados. Mostrando %d por página.\n',
            $paginator->total(),
            $paginator->perPage()
        );

        $lines = [];
        foreach ($paginator->items() as $product) {
            /** @var \App\Models\Product $product */
            $lines[] = sprintf(
                '- SKU %s | %s (%s %s) | Precio: %s | Stock: %d%s',
                (string) $product->sku,
                (string) $product->name,
                (string) ($product->brand ?? 'N/A'),
                (string) ($product->model ?? ''),
                number_format((float) $product->price, 2, '.', ''),
                (int) $product->stock,
                $product->is_active ? '' : ' | Inactivo'
            );
        }

        $body = $lines === []
            ? 'No se encontraron productos para los criterios proporcionados.'
            : implode("\n", $lines);

        return Response::text($header.$body)->asAssistant();
    }
}
