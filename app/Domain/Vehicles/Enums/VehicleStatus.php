<?php

namespace App\Domain\Vehicles\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum VehicleStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingReview => 'En revisión',
            self::Approved => 'Aprobado',
            self::Archived => 'Archivado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingReview => 'warning',
            self::Approved => 'success',
            self::Archived => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::PendingReview => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-badge',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
