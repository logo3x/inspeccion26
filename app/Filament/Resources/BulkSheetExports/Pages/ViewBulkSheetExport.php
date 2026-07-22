<?php

namespace App\Filament\Resources\BulkSheetExports\Pages;

use App\Domain\InspectionSheets\Enums\BulkSheetExportStatus;
use App\Filament\Resources\BulkSheetExports\BulkSheetExportResource;
use App\Models\BulkSheetExport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewBulkSheetExport extends ViewRecord
{
    protected static string $resource = BulkSheetExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Descargar ZIP')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->getRecord()->status === BulkSheetExportStatus::Completed
                    && (auth()->user()?->can('Download:Vehicle') ?? false))
                ->action(function () {
                    /** @var BulkSheetExport $record */
                    $record = $this->getRecord();

                    return response()->download(
                        Storage::disk('local')->path($record->zip_path),
                        'fichas_'.$record->id.'_'.now()->format('Ymd').'.zip'
                    )->deleteFileAfterSend(false);
                }),
        ];
    }
}
