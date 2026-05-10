<?php

namespace App\Models;

use App\Domain\Imports\Enums\ImportBatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'original_filename',
    'stored_path',
    'total_rows',
    'processed_rows',
    'created_count',
    'updated_count',
    'failed_count',
    'skipped_count',
    'status',
    'column_mapping',
    'started_at',
    'finished_at',
])]
class ImportBatch extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'column_mapping' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'batch_id');
    }

    public function progressPercentage(): int
    {
        if ($this->total_rows <= 0) {
            return 0;
        }

        return (int) round(min(100, ($this->processed_rows / $this->total_rows) * 100));
    }
}
