<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Domain\InspectionSheets\Actions\GenerateSheetAction;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicle extends ViewRecord
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
                        ->deleteFileAfterSend(true);
                }),
            EditAction::make(),
        ];
    }
}
