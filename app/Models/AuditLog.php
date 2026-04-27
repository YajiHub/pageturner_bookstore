<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_type',
        'actor_identifier',
        'actor_role',
        'action',
        'event_category',
        'auditable_type',
        'auditable_id',
        'target_identifier',
        'description',
        'severity',
        'outcome',
        'risk_score',
        'is_suspicious',
        'detection_tags',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_url',
        'request_method',
        'http_status_code',
        'request_source',
        'correlation_id',
        'request_id',
        'session_id',
        'checksum',
        'previous_checksum',
        'created_at',
        'archived_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'risk_score' => 'integer',
        'is_suspicious' => 'boolean',
        'detection_tags' => 'array',
        'http_status_code' => 'integer',
        'created_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
