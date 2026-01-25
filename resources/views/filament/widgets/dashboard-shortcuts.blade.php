<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap gap-3">
            @foreach ($this->getShortcuts() as $shortcut)
                <x-filament::button
                    tag="a"
                    href="{{ $shortcut['url'] }}"
                    color="primary"
                >
                    {{ $shortcut['label'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
