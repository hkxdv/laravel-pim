<?php

declare(strict_types=1);

namespace Modules\Sales\App\Models;

use App\Models\StaffUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'client_id',
        'user_id',
        'status',
        'total',
        'delivered_at',
        'delivered_by',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'user_id' => 'integer',
        'total' => 'float',
        'delivered_by' => 'integer',
        'delivered_at' => 'datetime',
    ];

    /**
     * @phpstan-return HasMany<SalesItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'sales_order_id');
    }

    /**
     * @phpstan-return BelongsTo<StaffUsers, $this>
     */
    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUsers::class, 'user_id');
    }

    /**
     * @phpstan-return BelongsTo<StaffUsers, $this>
     */
    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(StaffUsers::class, 'delivered_by');
    }
}
