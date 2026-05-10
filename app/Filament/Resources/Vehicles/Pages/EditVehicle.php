<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Domain\Vehicles\Enums\VehicleStatus;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make($this->buildStateActions())
                ->label('Cambiar estado')
                ->icon('heroicon-o-flag')
                ->color('primary')
                ->button(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function buildStateActions(): array
    {
        return collect(VehicleStatus::cases())
            ->map(fn (VehicleStatus $status) => Action::make('setStatus_'.$status->value)
                ->label($status->getLabel())
                ->icon($status->getIcon())
                ->color($status->getColor())
                ->visible(fn () => $this->getRecord()->estado !== $status)
                ->requiresConfirmation()
                ->modalHeading("Marcar como {$status->getLabel()}")
                ->modalDescription('El cambio quedará registrado en el log de auditoría.')
                ->action(function () use ($status) {
                    /** @var Vehicle $record */
                    $record = $this->getRecord();
                    $record->update(['estado' => $status->value]);

                    Notification::make()
                        ->title("Estado cambiado a {$status->getLabel()}")
                        ->success()
                        ->send();

                    $this->fillForm();
                }))
            ->all();
    }
}
