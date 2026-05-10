@php
    $record = $this->getRecord();
    $count = $record?->getMedia(\App\Models\Vehicle::PHOTOS_COLLECTION)->count() ?? 0;
    $max = \App\Models\Vehicle::MAX_PHOTOS;
@endphp

<div @class([
    'rounded-lg p-3 border text-sm flex items-start gap-2',
    'bg-warning-50 border-warning-300 text-warning-700 dark:bg-warning-500/10 dark:border-warning-500/30 dark:text-warning-300' => $count === 0,
    'bg-success-50 border-success-300 text-success-700 dark:bg-success-500/10 dark:border-success-500/30 dark:text-success-300' => $count > 0,
])>
    @if ($count === 0)
        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5 shrink-0" />
        <div>
            <div class="font-semibold">Sin fotografías</div>
            <div class="text-xs">Sube al menos 1 foto en la pestaña <strong>Fotografías</strong> para completar la ficha.</div>
        </div>
    @else
        <x-filament::icon icon="heroicon-o-photo" class="size-5 shrink-0" />
        <div>
            <div class="font-semibold">{{ $count }} de {{ $max }} fotografías</div>
            <div class="text-xs">{{ $count >= $max ? 'Galería al máximo permitido.' : ($max - $count) . ' espacios disponibles.' }}</div>
        </div>
    @endif
</div>
