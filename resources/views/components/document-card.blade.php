@props([
    'document',
    'showDelete' => false,
])

@php
    $mimeType = $document->mime_type ?? '';
    $isImage = str_starts_with($mimeType, 'image/');
    $isPdf = $mimeType === 'application/pdf';
    $isWord = in_array($mimeType, [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ], true);
    $previewUrl = $isImage ? route('documents.preview', $document) : null;
    $fileExtension = strtoupper(pathinfo($document->original_filename, PATHINFO_EXTENSION) ?: '?');

    // Document type configuration
    $typeLabel = match ($document->type ?? 'other') {
        'diagnostic_image' => __('app.labels.diagnostic_image'),
        'report' => __('app.labels.report'),
        default => __('app.labels.other'),
    };

    $typeConfig = match ($document->type ?? 'other') {
        'diagnostic_image' => [
            'icon' => 'heroicon-o-photo',
            'color' => 'text-sky-600 dark:text-sky-400',
            'bg' => 'bg-sky-50 dark:bg-sky-900/30',
            'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300',
        ],
        'report' => [
            'icon' => 'heroicon-o-document-chart-bar',
            'color' => 'text-emerald-600 dark:text-emerald-400',
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
            'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
        ],
        default => [
            'icon' => 'heroicon-o-document',
            'color' => 'text-gray-500 dark:text-gray-400',
            'bg' => 'bg-gray-100 dark:bg-gray-800',
            'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        ],
    };

    // File type icon configuration
    $docIcon = match (true) {
        $isPdf => 'heroicon-o-document-duplicate',
        $isWord => 'heroicon-o-document-text',
        $isImage => 'heroicon-o-photo',
        default => 'heroicon-o-document',
    };

    $iconGradient = match (true) {
        $isPdf => 'from-red-500 to-orange-500',
        $isWord => 'from-blue-500 to-indigo-500',
        $isImage => 'from-purple-500 to-pink-500',
        default => 'from-gray-400 to-gray-500',
    };
@endphp

{{-- Google Drive-style card: rectangular with preview on top, filename below --}}
<div class="group relative flex w-full flex-col overflow-hidden rounded border border-gray-200 bg-white transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
    {{-- Preview Area (top portion) - fills most of card --}}
    <div class="relative flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
        <a href="{{ route('documents.download', $document) }}" class="block h-full w-full">
            @if ($isImage)
                {{-- Image preview --}}
                <img
                    src="{{ $previewUrl }}"
                    alt="{{ $document->original_filename }}"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                />
                {{-- Fallback placeholder (hidden by default, shown on image error) --}}
                <div class="hidden h-full w-full flex-col items-center justify-center {{ $typeConfig['bg'] }}">
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br {{ $iconGradient }}">
                        <x-filament::icon :icon="$docIcon" class="h-8 w-8 text-white" />
                    </div>
                    <span class="mt-2 font-mono text-xs font-semibold {{ $typeConfig['color'] }}">.{{ $fileExtension }}</span>
                </div>
            @else
                {{-- Non-image placeholder --}}
                <div class="flex h-full w-full flex-col items-center justify-center {{ $typeConfig['bg'] }}">
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br {{ $iconGradient }}">
                        <x-filament::icon :icon="$docIcon" class="h-8 w-8 text-white" />
                    </div>
                    <span class="mt-2 font-mono text-xs font-semibold {{ $typeConfig['color'] }}">.{{ $fileExtension }}</span>
                </div>
            @endif
        </a>

        {{-- Kebab Menu (top-right) - Google Drive style --}}
        <div class="absolute right-2 top-2 z-20 opacity-0 transition-opacity duration-200 group-hover:opacity-100" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                class="flex h-7 w-7 items-center justify-center rounded-full bg-white/95 text-gray-600 shadow-sm backdrop-blur-sm transition hover:bg-white hover:text-gray-900 dark:bg-gray-800/95 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                @click.stop="open = !open"
            >
                <x-filament::icon icon="heroicon-m-ellipsis-vertical" class="h-4 w-4" />
            </button>
            
            {{-- Dropdown Menu --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 top-9 z-10 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                style="display: none;"
            >
                <a
                    href="{{ route('documents.download', $document) }}"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click.stop
                >
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                    {{ __('app.actions.download') }}
                </a>
                @if($showDelete && (auth()->user()?->isAdmin() || auth()->user()?->isStaff()))
                    <button
                        wire:click="deleteDocument({{ $document->id }})"
                        wire:confirm="{{ __('app.messages.confirm_delete') }}"
                        type="button"
                        class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        @click.stop
                    >
                        <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                        {{ __('app.actions.delete') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Filename Bar (bottom) - Google Drive style --}}
    <div class="flex items-center gap-2 border-t border-gray-100 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800">
        <a href="{{ route('documents.download', $document) }}" class="flex-1 min-w-0">
            <p class="truncate text-xs font-medium leading-tight text-gray-900 dark:text-gray-100" title="{{ $document->original_filename }}">
                {{ $document->original_filename }}
            </p>
            <div class="mt-1 flex items-center gap-1.5 text-[10px] leading-tight text-gray-500 dark:text-gray-400">
                <span class="font-mono">.{{ strtolower($fileExtension) }}</span>
                @if($document->uploadedBy ?? null)
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span class="truncate">{{ Str::limit($document->uploadedBy->name, 12) }}</span>
                @endif
            </div>
        </a>
    </div>
</div>
