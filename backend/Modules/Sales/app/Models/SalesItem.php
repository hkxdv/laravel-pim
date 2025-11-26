<?php

declare(strict_types=1);

namespace Modules\Sales\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\App\Models\Product;

final class SalesItem extends Model
{
    protected $table = 'sales_items';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'qty',
        'price',
    ];

    protected $casts = [
        'sales_order_id' => 'integer',
        'product_id' => 'integer',
        'qty' => 'integer',
        'price' => 'float',
    ];

    /**
     * @phpstan-return BelongsTo<SalesOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * @phpstan-return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
