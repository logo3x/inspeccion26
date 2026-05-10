<?php

namespace App\Filament\Resources\BulkSheetExports\Pages;

use App\Filament\Resources\BulkSheetExports\BulkSheetExportResource;
use Filament\Resources\Pages\ListRecords;

class ListBulkSheetExports extends ListRecords
{
    protected static string $resource = BulkSheetExportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
