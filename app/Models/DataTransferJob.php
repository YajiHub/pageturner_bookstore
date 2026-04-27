<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTransferJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'original_filename',
        'stored_path',
        'result_path',
        'options',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'progress_percentage',
        'failures',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options' => 'array',
        'failures' => 'array',
        'progress_percentage' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
