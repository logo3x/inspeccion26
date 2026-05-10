<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Domain\InspectionSheets\Actions\StartBulkSheetExportAction;
use App\Filament\Resources\BulkSheetExports\BulkSheetExportResource;
use App\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportRange')
                    ->label('Exportar rango (ZIP)')
                    ->icon('heroicon-o-archive-box')
                    ->color('success')
                    ->modalHeading('Exportar fichas por rango de inventario DTB')
                    ->modalDescription('Genera un ZIP con las fichas técnicas de los vehículos en el rango indicado. La generación corre en cola y se notifica al finalizar.')
                    ->modalSubmitActionLabel('Encolar generación')
                    ->schema([
                        TextInput::make('from')
                            ->label('# Inventario DTB desde')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('to')
                            ->label('# Inventario DTB hasta')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->gte('from'),
                    ])
                    ->action(function (array $data, StartBulkSheetExportAction $starter) {
                        $criteria = [
                            'kind' => 'range',
                            'from' => (int) $data['from'],
                            'to' => (int) $data['to'],
                        ];

                        $count = $starter->countMatching($criteria);
                        if ($count === 0) {
                            Notification::make()
                                ->title('Sin vehículos en el rango')
                                ->warning()
                                ->send();

                            return;
                        }

                        $export = $starter($criteria);

                        Notification::make()
                            ->title("Exportación encolada: {$count} vehículos")
                            ->body('Se generará en background. Puedes monitorear el progreso.')
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Ver progreso')
                                    ->url(BulkSheetExportResource::getUrl('view', ['record' => $export]))
                                    ->markAsRead(),
                            ])
                            ->send();

                        $this->redirect(BulkSheetExportResource::getUrl('view', ['record' => $export]));
                    }),

                Action::make('exportAll')
                    ->label('Exportar TODOS (ZIP)')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Exportar todas las fichas')
                    ->modalDescription(fn (StartBulkSheetExportAction $starter) => 'Esto generará un ZIP con '.number_format($starter->countMatching(['kind' => 'all'])).' vehículos. La generación corre en cola y puede tardar varios minutos. ¿Continuar?')
                    ->modalSubmitActionLabel('Sí, encolar')
                    ->action(function (StartBulkSheetExportAction $starter) {
                        $criteria = ['kind' => 'all'];
                        $count = $starter->countMatching($criteria);
                        if ($count === 0) {
                            Notification::make()
                                ->title('No hay vehículos para exportar')
                                ->warning()
                                ->send();

                            return;
                        }

                        $export = $starter($criteria);

                        Notification::make()
                            ->title("Exportación masiva encolada: {$count} vehículos")
                            ->success()
                            ->send();

                        $this->redirect(BulkSheetExportResource::getUrl('view', ['record' => $export]));
                    }),
            ])
                ->label('Exportar Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->button(),

            CreateAction::make(),
        ];
    }
}
