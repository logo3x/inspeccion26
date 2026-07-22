<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Domain\InspectionSheets\Actions\GenerateSheetAction;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSheet')
                ->label('Imprimir ficha')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn () => auth()->user()?->can('Download:Vehicle') ?? false)
                ->action(function (GenerateSheetAction $generator) {
                    /** @var Vehicle $record */
                    $record = $this->getRecord();
                    $path = $generator($record);

                    return response()->download($path, $generator->suggestedDownloadName($record))
                        ->deleteFileAfterSend(false);
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
