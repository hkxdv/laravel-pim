<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
