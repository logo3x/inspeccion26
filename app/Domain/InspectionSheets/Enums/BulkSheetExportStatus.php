<?php

namespace App\Domain\InspectionSheets\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BulkSheetExportStatus: string implements HasColor, HasIcon, HasLabel
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Processing => 'Generando',
            self::Completed => 'Listo',
            self::Failed => 'Fallido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Queued => 'heroicon-o-clock',
            self::Processing => 'heroicon-o-arrow-path',
            self::Completed => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }

    public function isDownloadable(): bool
    {
        return $this === self::Completed;
    }
}
