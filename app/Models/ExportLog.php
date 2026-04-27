<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'data_transfer_job_id',
        'format',
        'filters',
        'selected_columns',
        'status',
        'download_link',
        'rows_exported',
    ];

    protected $casts = [
        'filters' => 'array',
        'selected_columns' => 'array',
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
