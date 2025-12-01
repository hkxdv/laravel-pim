<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $phone
 * @property bool $search_enabled
 * @property Carbon|\Carbon\CarbonImmutable|null $muted_until
 * @property array<string, mixed> $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class WhatsAppSession extends Model
{
    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'phone',
        'search_enabled',
        'muted_until',
        'meta',
    ];

    protected $casts = [
        'search_enabled' => 'boolean',
        'muted_until' => 'datetime',
        'meta' => 'array',
    ];
}
