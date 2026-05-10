<?php

namespace App\Filament\Resources\ImportBatches\Pages;

use App\Domain\Imports\Actions\StartImportBatchAction;
use App\Domain\Imports\Enums\ImportBatchStatus;
use App\Filament\Resources\ImportBatches\ImportBatchResource;
use App\Models\ImportBatch;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateImportBatch extends CreateRecord
{
    protected static string $resource = ImportBatchResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $files = $data['file'] ?? null;
        if (is_array($files)) {
            $files = array_values($files);
            $storedPath = $files[0] ?? null;
        } else {
            $storedPath = $files;
        }

        if (! is_string($storedPath) || $storedPath === '') {
            throw new \RuntimeException('No se recibió el archivo cargado.');
        }

        $original = $data['original_filename'] ?? null;
        if (is_array($original)) {
            $original = array_values($original)[0] ?? null;
        }

        return ImportBatch::create([
            'user_id' => Auth::id(),
            'original_filename' => $original ?: basename($storedPath),
            'stored_path' => $storedPath,
            'status' => ImportBatchStatus::Queued->value,
        ]);
    }

    protected function afterCreate(): void
    {
        /** @var ImportBatch $batch */
        $batch = $this->record;

        try {
            app(StartImportBatchAction::class)($batch);
        } catch (\Throwable $e) {
            $batch->forceFill([
                'status' => ImportBatchStatus::Failed->value,
                'finished_at' => now(),
            ])->save();

            Notification::make()
                ->title('Error iniciando importación')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Importación encolada')
            ->body("Se programaron {$batch->fresh()->total_rows} filas para procesamiento.")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
