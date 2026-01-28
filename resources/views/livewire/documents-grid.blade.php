<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
            {{ __('app.labels.documents') }}
        </h3>
        @if(auth()->user()?->isAdmin() || auth()->user()?->isStaff())
            <button
                wire:click="openUploadModal"
                type="button"
                class="fi-btn fi-btn-size-md fi-btn-color-primary inline-flex items-center justify-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors bg-primary-600 hover:bg-primary-500"
            >
                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-5 w-5" />
                {{ __('app.actions.upload_document') }}
            </button>
        @endif
    </div>

    {{-- Documents Grid --}}
    @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-12 dark:border-gray-700">
            <x-filament::icon icon="heroicon-o-document" class="mb-4 h-12 w-12 text-gray-400" />
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.messages.no_documents') }}</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($documents as $document)
                <x-document-card :document="$document" :show-delete="true" />
            @endforeach
        </div>
    @endif

    {{-- Upload Modal --}}
    <div
        x-data="{ show: @entangle('showUploadModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex min-h-screen items-center justify-center p-4">
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50"
                @click="$wire.closeUploadModal()"
            ></div>

            {{-- Modal --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"
            >
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('app.actions.upload_document') }}
                </h3>

                <form wire:submit="save" class="space-y-4">
                    {{-- Type Select --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.document_type') }}
                        </label>
                        <select
                            wire:model="type"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="diagnostic_image">{{ __('app.labels.diagnostic_image') }}</option>
                            <option value="report">{{ __('app.labels.report') }}</option>
                            <option value="other">{{ __('app.labels.other') }}</option>
                        </select>
                        @error('type') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- File Upload --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.file') }}
                        </label>
                        <input
                            type="file"
                            wire:model="upload"
                            accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-primary-50 file:px-3 file:py-1 file:text-sm file:font-medium file:text-primary-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        @error('upload') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="upload" class="mt-2 text-sm text-gray-500">
                            {{ __('app.messages.uploading') }}...
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            wire:click="closeUploadModal"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            {{ __('app.actions.cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="save">{{ __('app.actions.save') }}</span>
                            <span wire:loading wire:target="save">{{ __('app.messages.saving') }}...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
