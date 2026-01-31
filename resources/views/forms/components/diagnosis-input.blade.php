<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            suggestions: [],
            showSuggestions: false,
            highlightedIndex: -1,
            loading: false,
            minLength: {{ $getMinSearchLength() }},
            maxSuggestions: {{ $getMaxSuggestions() }},

            async search() {
                const query = (this.state || '').trim();

                if (query.length < this.minLength) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }

                this.loading = true;

                try {
                    const response = await fetch(`/api/diagnoses/search?q=${encodeURIComponent(query)}&limit=${this.maxSuggestions}`);
                    const data = await response.json();
                    this.suggestions = data.suggestions || [];
                    this.showSuggestions = this.suggestions.length > 0;
                    this.highlightedIndex = -1;
                } catch (error) {
                    this.suggestions = [];
                } finally {
                    this.loading = false;
                }
            },

            justSelected: false,

            select(suggestion) {
                this.justSelected = true;
                this.state = suggestion;
                this.showSuggestions = false;
                this.suggestions = [];
                this.$refs.input.focus();
            },

            handleKeydown(event) {
                if (!this.showSuggestions) return;

                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault();
                        this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.suggestions.length - 1);
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
                        break;
                    case 'Enter':
                        if (this.highlightedIndex >= 0) {
                            event.preventDefault();
                            this.select(this.suggestions[this.highlightedIndex]);
                        }
                        break;
                    case 'Escape':
                        this.showSuggestions = false;
                        break;
                }
            },

            debounceTimer: null,

            debouncedSearch() {
                if (this.justSelected) {
                    this.justSelected = false;
                    return;
                }
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.search(), 250);
            },

            handleBlur() {
                setTimeout(() => {
                    this.showSuggestions = false;
                }, 200);
            }
        }"
        x-init="$watch('state', () => debouncedSearch())"
        class="relative"
    >
        {{-- Filament-styled input wrapper --}}
        <div
            @class([
                'fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75',
                'ring-gray-950/10 dark:ring-white/20',
                'focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500',
                'bg-white dark:bg-white/5',
            ])
        >
            <input
                x-ref="input"
                x-model="state"
                @keydown="handleKeydown"
                @focus="if ((state || '').length >= minLength) search()"
                @blur="handleBlur"
                type="text"
                autocomplete="off"
                @if($getId()) id="{{ $getId() }}" @endif
                @class([
                    'fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6',
                ])
                @disabled($isDisabled())
            />

            {{-- Loading Indicator --}}
            <div
                x-show="loading"
                x-cloak
                class="flex items-center pe-3"
            >
                <x-filament::loading-indicator class="h-5 w-5 text-gray-400" />
            </div>
        </div>

        {{-- Suggestions Dropdown --}}
        <div
            x-show="showSuggestions && suggestions.length > 0"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <ul class="max-h-60 overflow-auto rounded-lg py-1 text-sm">
                <template x-for="(suggestion, index) in suggestions" :key="index">
                    <li
                        @click="select(suggestion)"
                        @mouseenter="highlightedIndex = index"
                        :class="{
                            'bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400': highlightedIndex === index,
                        }"
                        class="cursor-pointer px-3 py-2 text-gray-900 transition hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                    >
                        <span x-text="suggestion"></span>
                    </li>
                </template>
            </ul>
            <div class="border-t border-gray-200 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500">
                {{ __('app.helpers.diagnosis_suggestions') }}
            </div>
        </div>
    </div>
</x-dynamic-component>
