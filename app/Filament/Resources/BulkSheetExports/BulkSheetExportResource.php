<?php

namespace App\Filament\Resources\BulkSheetExports;

use App\Filament\Resources\BulkSheetExports\Pages\ListBulkSheetExports;
use App\Filament\Resources\BulkSheetExports\Pages\ViewBulkSheetExport;
use App\Filament\Resources\BulkSheetExports\Schemas\BulkSheetExportInfolist;
use App\Filament\Resources\BulkSheetExports\Tables\BulkSheetExportsTable;
use App\Models\BulkSheetExport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BulkSheetExportResource extends Resource
{
    protected static ?string $model = BulkSheetExport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $modelLabel = 'Exportación masiva';

    protected static ?string $pluralModelLabel = 'Exportaciones masivas';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 35;

    public static function infolist(Schema $schema): Schema
    {
        return BulkSheetExportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BulkSheetExportsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBulkSheetExports::route('/'),
            'view' => ViewBulkSheetExport::route('/{record}'),
        ];
    }
}
