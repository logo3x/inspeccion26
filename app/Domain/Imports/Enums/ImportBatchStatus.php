<?php

namespace App\Domain\Imports\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ImportBatchStatus: string implements HasColor, HasIcon, HasLabel
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Processing => 'Procesando',
            self::Completed => 'Completado',
            self::Partial => 'Parcial (con errores)',
            self::Failed => 'Fallido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Partial => 'warning',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Queued => 'heroicon-o-clock',
            self::Processing => 'heroicon-o-arrow-path',
            self::Completed => 'heroicon-o-check-circle',
            self::Partial => 'heroicon-o-exclamation-triangle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Partial, self::Failed], true);
    }
}
