<?php

namespace App\Models;

use App\Domain\Imports\Enums\ImportRowAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'batch_id',
    'row_number',
    'placa',
    'raw_data',
    'action',
    'vehicle_id',
    'error_message',
    'processed_at',
])]
class ImportRow extends Model
{
    protected function casts(): array
    {
        return [
            'action' => ImportRowAction::class,
            'raw_data' => 'array',
            'processed_at' => 'datetime',
            'row_number' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
