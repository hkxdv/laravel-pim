<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'new_stock',
        'notes',
        'performed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsTo<\App\Models\StaffUsers, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StaffUsers::class, 'user_id');
    }
}
