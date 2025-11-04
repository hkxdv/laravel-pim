<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AgentLog extends Model
{
    /** @use HasFactory<\Database\Factories\AgentLogFactory> */
    use HasFactory;

    protected $table = 'agent_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agent_name',
        'user_id',
        'module',
        'action',
        'status',
        'duration_ms',
        'request_payload',
        'response_payload',
        'meta',
        'ip_address',
        'user_agent',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'meta' => 'array',
        'duration_ms' => 'integer',
    ];

    /**
     * @return BelongsTo<StaffUsers, $this>
     */
    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUsers::class, 'user_id');
    }
}
