<?php

namespace App\Domain\Imports\Actions;

use App\Domain\Imports\Enums\ImportRowAction;
use App\Models\ImportRow;
use App\Models\Owner;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessImportRowAction
{
    /**
     * Procesa una fila ya mapeada: crea u actualiza Vehicle (y su Owner si aplica),
     * registra el resultado en la fila y devuelve la acción ejecutada.
     *
     * @param  array{vehicle: array<string, mixed>, owner: ?array<string, mixed>, placa: ?string}  $mapped
     */
    public function __invoke(ImportRow $row, array $mapped): ImportRowAction
    {
        $row->update([
            'placa' => $mapped['placa'],
            'raw_data' => $row->raw_data ?? [],
        ]);

        if (blank($mapped['placa'])) {
            return $this->markFailed($row, 'Placa vacía o inválida');
        }

        try {
            $action = DB::transaction(function () use ($row, $mapped): ImportRowAction {
                $ownerId = $this->resolveOwner($mapped['owner']);

                $payload = $mapped['vehicle'];
                if ($ownerId !== null) {
                    $payload['owner_id'] = $ownerId;
                }

                $existing = Vehicle::query()->where('placa', $mapped['placa'])->first();

                if ($existing) {
                    $existing->fill($payload)->save();
                    $vehicleId = $existing->id;
                    $resolved = ImportRowAction::Updated;
                } else {
                    $created = Vehicle::create(array_merge($payload, [
                        'placa' => $mapped['placa'],
                        'estado' => 'pending_review',
                    ]));
                    $vehicleId = $created->id;
                    $resolved = ImportRowAction::Created;
                }

                $row->forceFill([
                    'vehicle_id' => $vehicleId,
                    'action' => $resolved->value,
                    'error_message' => null,
                    'processed_at' => now(),
                ])->save();

                return $resolved;
            });

            return $action;
        } catch (Throwable $e) {
            return $this->markFailed($row, $e->getMessage());
        }
    }

    private function resolveOwner(?array $ownerData): ?int
    {
        if ($ownerData === null) {
            return null;
        }

        $doc = $ownerData['document_number'] ?? null;
        $type = $ownerData['document_type'] ?? 'CC';

        if (blank($doc)) {
            return null;
        }

        $owner = Owner::firstOrCreate(
            ['document_type' => $type, 'document_number' => (string) $doc],
            ['full_name' => $ownerData['full_name'] ?? 'Sin nombre']
        );

        if (filled($ownerData['full_name'] ?? null) && $owner->full_name !== $ownerData['full_name']) {
            $owner->update(['full_name' => $ownerData['full_name']]);
        }

        return $owner->id;
    }

    private function markFailed(ImportRow $row, string $message): ImportRowAction
    {
        $row->forceFill([
            'action' => ImportRowAction::Failed->value,
            'error_message' => mb_substr($message, 0, 5000),
            'processed_at' => now(),
        ])->save();

        return ImportRowAction::Failed;
    }
}
