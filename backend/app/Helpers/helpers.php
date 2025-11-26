<?php

declare(strict_types=1);

use Laravel\Mcp\Response;

if (! function_exists('mcp_ok')) {
    /**
     * @param  array<string, mixed>  $data
     */
    function mcp_ok(array $data, string $message): Response
    {
        return Response::json([
            'status' => 'ok',
            'data' => $data,
            'message' => $message,
        ])->asAssistant();
    }
}

if (! function_exists('mcp_error')) {
    /**
     * @param  array<string, mixed>  $data
     */
    function mcp_error(string $message, array $data = []): Response
    {
        return Response::json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ])->asAssistant();
    }
}

if (! function_exists('mcp_suggestion_text')) {
    /**
     * @param  array<int, string>  $suggestions
     */
    function mcp_suggestion_text(array $suggestions): string
    {
        $items = array_map(fn (string $s): string => '- '.$s, $suggestions);

        return "Sugerencias:\n".implode("\n", $items);
    }
}

if (! function_exists('mcp_list_header')) {
    function mcp_list_header(int $total): string
    {
        return sprintf(
            'Encontramos %d producto%s.',
            $total,
            $total === 1 ? '' : 's'
        );
    }
}

if (! function_exists('mcp_list_footer')) {
    function mcp_list_footer(int $perPage): string
    {
        return $perPage > 0 ? sprintf(
            "\nMostrando hasta %d resultado%s.",
            $perPage,
            $perPage === 1 ? '' : 's'
        ) : '';
    }
}

if (! function_exists('mcp_product_line')) {
    function mcp_product_line(
        string $sku,
        string $name,
        ?string $brand,
        ?string $model,
        float|int $price,
        int $stock,
        bool $isActive
    ): string {
        return sprintf(
            ' - SKU %s | %s (%s %s) | Precio: %s | Stock: %d%s ',
            $sku,
            $name,
            $brand ?? 'N/A',
            $model ?? '',
            number_format((float) $price, 2, '.', ''),
            $stock,
            $isActive ? '' : ' | Inactivo'
        );
    }
}
