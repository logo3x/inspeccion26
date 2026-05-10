<?php

namespace App\Domain\Imports\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ImportRowAction: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Created = 'created';
    case Updated = 'updated';
    case Skipped = 'skipped';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Created => 'Creado',
            self::Updated => 'Actualizado',
            self::Skipped => 'Omitido',
            self::Failed => 'Fallido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Created => 'success',
            self::Updated => 'info',
            self::Skipped => 'warning',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Created => 'heroicon-o-plus-circle',
            self::Updated => 'heroicon-o-arrow-path-rounded-square',
            self::Skipped => 'heroicon-o-minus-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }
}
