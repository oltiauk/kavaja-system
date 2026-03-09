@props([
    'document',
    'showDelete' => false,
])

@php
    $mimeType = $document->mime_type ?? '';
    $isImage = str_starts_with($mimeType, 'image/');
    $isPdf = $mimeType === 'application/pdf';
    $canPreview = $isImage || $isPdf;
    $clickUrl = $canPreview ? route('documents.preview', $document) : route('documents.download', $document);
    $fileExtension = strtoupper(pathinfo($document->original_filename, PATHINFO_EXTENSION) ?: '?');

    $docIcon = match (true) {
        $isPdf => 'heroicon-o-document-duplicate',
        $isImage => 'heroicon-o-photo',
        default => 'heroicon-o-document',
    };

    $iconColor = match (true) {
        $isPdf => 'text-red-500',
        $isImage => 'text-purple-500',
        default => 'text-gray-400',
    };

    $typeLabel = match ($document->type ?? 'other') {
        'diagnostic_image' => __('app.labels.diagnostic_image'),
        'report' => __('app.labels.report'),
        default => __('app.labels.other'),
    };
@endphp

<div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-750">
    <div class="shrink-0 {{ $iconColor }}">
        <x-filament::icon :icon="$docIcon" class="h-5 w-5" />
    </div>

    <a href="{{ $clickUrl }}" {{ $canPreview ? 'target="_blank"' : '' }} class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" title="{{ $document->original_filename }}">
            {{ $document->original_filename }}
        </p>
    </a>

    <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
        {{ $typeLabel }}
    </span>

    @if($document->uploadedBy ?? null)
        <span class="hidden shrink-0 text-xs text-gray-500 dark:text-gray-400 sm:inline">
            {{ Str::limit($document->uploadedBy->name, 15) }}
        </span>
    @endif

    <div class="flex shrink-0 items-center gap-1">
        @if($canPreview)
            <a
                href="{{ route('documents.preview', $document) }}"
                target="_blank"
                class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                title="{{ __('app.actions.preview') }}"
            >
                <x-filament::icon icon="heroicon-o-eye" class="h-4 w-4" />
            </a>
        @endif
        <a
            href="{{ route('documents.download', $document) }}"
            class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            title="{{ __('app.actions.download') }}"
        >
            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
        </a>
        @if($showDelete && (auth()->user()?->isAdmin() || auth()->user()?->isAdministration() || auth()->user()?->isStaff()))
            <button
                wire:click="deleteDocument({{ $document->id }})"
                wire:confirm="{{ __('app.messages.confirm_delete') }}"
                type="button"
                class="rounded p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                title="{{ __('app.actions.delete') }}"
            >
                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
            </button>
        @endif
    </div>
</div>
