<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interfaz para motores de búsqueda de productos.
 * Debe devolver un paginador consistente para el controlador.
 */
interface ProductSearchInterface
{
    /**
     * Realiza la búsqueda de productos según parámetros.
     *
     * Params soportados:
     * - q | search: término de búsqueda
     * - is_active?: bool
     * - brand?: string
     * - model?: string
     * - sort_field?: string
     * - sort_direction?: 'asc'|'desc'
     * - per_page?: int
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, \App\Models\Product>
     */
    public function search(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator;
}
