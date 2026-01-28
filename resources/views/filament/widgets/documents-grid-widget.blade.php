<x-filament-widgets::widget>
    <x-filament::section>
        @if($record)
            @livewire('documents-grid', ['encounter' => $record], key('documents-grid-' . $record->id))
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
