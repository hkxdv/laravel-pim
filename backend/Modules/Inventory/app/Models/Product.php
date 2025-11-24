<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property string $sku
 * @property string $name
 * @property string $brand
 * @property string $model
 * @property string $barcode
 * @property string $price
 * @property int $stock
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProductFactory>
 */
final class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'brand',
        'model',
        'barcode',
        'price',
        'stock',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Nombre del índice/colección en el buscador.
     */
    public function searchableAs(): string
    {
        return 'products';
    }

    /**
     * Mapeo del documento que se indexará en Typesense.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $createdAt = $this->created_at
            ? $this->created_at->getTimestamp() : null;
        $updatedAt = $this->updated_at
            ? $this->updated_at->getTimestamp() : null;

        $idRaw = $this->getKey();
        $id = is_string($idRaw)
            ? $idRaw : (is_int($idRaw)
                ? (string) $idRaw : ''
            );

        return [
            'id' => $id,
            'sku' => $this->sku ?? '',
            'name' => $this->name ?? '',
            'brand' => $this->brand ?? '',
            'model' => $this->model ?? '',
            'barcode' => $this->barcode ?? '',
            'price' => (float) $this->price,
            'stock' => (int) ($this->stock ?? 0),
            'is_active' => (bool) ($this->is_active ?? true),
            'created_at' => $createdAt ?? 0,
            'updated_at' => $updatedAt ?? 0,
            'metadata' => is_array($this->metadata) ? $this->metadata : [],
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }
}
