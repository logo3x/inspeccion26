<?php

namespace App\Models;

use App\Domain\InspectionSheets\Enums\BulkSheetExportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'label',
    'criteria',
    'total_count',
    'processed_count',
    'failed_count',
    'status',
    'zip_path',
    'zip_size_bytes',
    'error_message',
    'started_at',
    'finished_at',
])]
class BulkSheetExport extends Model
{
    protected function casts(): array
    {
        return [
            'status' => BulkSheetExportStatus::class,
            'criteria' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_count' => 'integer',
            'processed_count' => 'integer',
            'failed_count' => 'integer',
            'zip_size_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercentage(): int
    {
        if ($this->total_count <= 0) {
            return 0;
        }

        return (int) round(min(100, ($this->processed_count / $this->total_count) * 100));
    }
}
