<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'data_transfer_job_id',
        'filename',
        'status',
        'rows_processed',
        'rows_failed',
        'failure_report',
    ];

    protected $casts = [
        'failure_report' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transferJob(): BelongsTo
    {
        return $this->belongsTo(DataTransferJob::class, 'data_transfer_job_id');
    }
}
