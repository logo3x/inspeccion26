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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportRange')
                    ->visible(fn () => auth()->user()?->can('Download:Vehicle') ?? false)
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
                            ->send();

                        $this->redirect(BulkSheetExportResource::getUrl('view', ['record' => $export]));
                    }),

                Action::make('exportAll')
                    ->visible(fn () => auth()->user()?->can('Download:Vehicle') ?? false)
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

            Action::make('importPhotos')
                ->visible(fn () => auth()->user()?->can('Download:Vehicle') ?? false)
                ->label('Actualizar fotos')
                ->icon('heroicon-o-photo')
                ->color('info')
                ->modalHeading('Emparejar fotos con vehículos')
                ->modalDescription(function () {
                    $count = $this->countPhotosToImport();

                    return $count > 0
                        ? "Hay {$count} fotos pendientes en photos-import/. Esta corrida procesa hasta 500 (puedes repetir el botón hasta vaciar). Sube los archivos por FTP/cPanel a storage/app/private/photos-import/."
                        : 'No hay fotos pendientes en photos-import/. Sube archivos por FTP/cPanel a storage/app/private/photos-import/ con el nombre = placa.';
                })
                ->modalSubmitActionLabel('Emparejar ahora')
                ->action(function () {
                    @set_time_limit(0);
                    @ini_set('memory_limit', '1G');

                    Artisan::call('vehicles:import-photos', ['--limit' => 500]);
                    $output = Artisan::output();

                    preg_match('/Asociados\s*:\s*(\d+)/u', $output, $a);
                    preg_match('/Hu(?:é|e)rfanos\s*:\s*(\d+)/u', $output, $h);
                    preg_match('/Procesados\s*:\s*(\d+)/u', $output, $p);
                    preg_match('/Disponibles\s*:\s*(\d+)/u', $output, $d);

                    $asociados = (int) ($a[1] ?? 0);
                    $huerfanos = (int) ($h[1] ?? 0);
                    $procesados = (int) ($p[1] ?? 0);
                    $totalDispOriginal = (int) ($d[1] ?? 0);
                    $restantes = max(0, $totalDispOriginal - $procesados);

                    Notification::make()
                        ->title("Asociadas: {$asociados}  ·  Huérfanas: {$huerfanos}")
                        ->body($restantes > 0
                            ? "Quedan {$restantes} fotos por procesar. Vuelve a presionar 'Actualizar fotos' para seguir."
                            : 'No quedan fotos pendientes.')
                        ->success()
                        ->persistent()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    private function countPhotosToImport(): int
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('photos-import')) {
            return 0;
        }
        $dir = $disk->path('photos-import');
        $entries = @scandir($dir) ?: [];
        $count = 0;
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $count++;
            }
        }

        return $count;
    }
}
