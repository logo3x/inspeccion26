<?php

namespace App\Filament\Resources\BulkSheetExports\Tables;

use App\Domain\InspectionSheets\Enums\BulkSheetExportStatus;
use App\Models\BulkSheetExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BulkSheetExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('label')->label('Descripción')->wrap()->limit(50),
                TextColumn::make('user.name')->label('Por')->placeholder('—'),
                TextColumn::make('total_count')->label('Total')->numeric(),
                TextColumn::make('progress')
                    ->label('Progreso')
                    ->state(fn (BulkSheetExport $record) => $record->progressPercentage().'%')
                    ->badge()
                    ->color(fn (BulkSheetExport $record) => match (true) {
                        $record->progressPercentage() >= 100 => 'success',
                        $record->progressPercentage() > 0 => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('failed_count')->label('Errores')->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('status')->badge(),
                TextColumn::make('zip_size_bytes')
                    ->label('Tamaño')
                    ->formatStateUsing(fn (?int $state) => $state ? round($state / 1024 / 1024, 1).' MB' : '—'),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(BulkSheetExportStatus::class),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (BulkSheetExport $record) => $record->status === BulkSheetExportStatus::Completed)
                    ->action(fn (BulkSheetExport $record) => response()->download(
                        Storage::disk('local')->path($record->zip_path),
                        'fichas_'.($record->id).'_'.now()->format('Ymd').'.zip'
                    )->deleteFileAfterSend(false)),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
