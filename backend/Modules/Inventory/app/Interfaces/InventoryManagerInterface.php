<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\App\Models\Product;

/**
 * Interfaz para la gestión de inventario.
 * Define operaciones esenciales para administrar productos.
 */
interface InventoryManagerInterface
{
    /**
     * Campos permitidos para ordenación de listados.
     */
    public const ALLOWED_SORT_FIELDS = [
        'id',
        'sku',
        'name',
        'price',
        'stock',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Obtiene una lista paginada de productos.
     *
     * @param  array<string, mixed>  $params  Filtros y ordenación (search, sort_field, sort_direction, per_page, is_active)
     * @param  int  $perPage  Elementos por página por defecto
     * @return LengthAwarePaginator<int, Product>
     */
    public function getAllProducts(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator;

    /**
     * Crea un nuevo producto.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProduct(array $data): Product;

    /**
     * Obtiene un producto por su ID.
     */
    public function getProductById(int $id): ?Product;

    /**
     * Actualiza un producto existente.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(int $id, array $data): ?Product;

    /**
     * Elimina (soft delete) un producto por su ID.
     */
    public function deleteProduct(int $id): bool;

    /**
     * Total de productos registrados.
     */
    public function getTotalProducts(): int;
}
