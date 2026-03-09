@php
    $encounter = $encounter ?? null;
    $files = [];

    if ($encounter) {
        if ($encounter->lab_results_file_path) {
            $files[] = [
                'label' => __('app.labels.lab_results'),
                'filename' => $encounter->lab_results_original_filename ?? 'lab-results',
                'url' => route('encounters.lab-results', $encounter),
                'icon' => 'heroicon-o-beaker',
                'color' => 'text-emerald-500',
            ];
        }
        if ($encounter->operative_work_file_path) {
            $files[] = [
                'label' => __('app.labels.operative_procedure'),
                'filename' => $encounter->operative_work_original_filename ?? 'operative-work',
                'url' => route('encounters.operative-work', $encounter),
                'icon' => 'heroicon-o-scissors',
                'color' => 'text-blue-500',
            ];
        }
        if ($encounter->surgical_notes_file_path) {
            $files[] = [
                'label' => __('app.labels.imaging_rtg'),
                'filename' => $encounter->surgical_notes_original_filename ?? 'surgical-notes',
                'url' => route('encounters.surgical-notes', $encounter),
                'icon' => 'heroicon-o-photo',
                'color' => 'text-purple-500',
            ];
        }
    }
@endphp

@if(count($files) > 0)
    <div class="space-y-2 pb-3">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ __('app.labels.file_uploads') }}
        </p>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($files as $file)
                <a
                    href="{{ $file['url'] }}"
                    target="_blank"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-750"
                >
                    <div class="shrink-0 {{ $file['color'] }}">
                        <x-filament::icon :icon="$file['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $file['label'] }}</p>
                        <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">{{ $file['filename'] }}</p>
                    </div>
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 shrink-0 text-gray-400" />
                </a>
            @endforeach
        </div>
    </div>
@endif
