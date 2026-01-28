<div
    x-data="{
        floor: null,
        room: null,
        bed: null,
        td: false,
        floors: @js(config('hospital.floors')),

        get selectedRoom() {
            if (!this.floor || !this.room) return null;
            return this.floors[this.floor]?.rooms[this.room] || null;
        },

        get needsBedSelection() {
            return this.selectedRoom && this.selectedRoom.beds > 1;
        },

        get roomValue() {
            if (this.td) return 'TD';
            if (!this.floor || !this.room) return null;
            if (this.needsBedSelection && !this.bed) return null;
            return this.floor + '-' + this.room + (this.bed ? '-' + this.bed : '');
        },

        selectFloor(f) {
            this.floor = f;
            this.room = null;
            this.bed = null;
        },

        selectRoom(r) {
            this.room = r;
            this.bed = null;
            // If only 1 bed, auto-select it
            if (this.selectedRoom && this.selectedRoom.beds === 1) {
                this.bed = '1';
                this.updateValue();
            }
        },

        selectBed(b) {
            this.bed = b;
            this.updateValue();
        },

        updateValue() {
            $wire.set('{{ $getStatePath() }}', this.roomValue);
        },

        parseInitial() {
            const val = $wire.get('{{ $getStatePath() }}');
            if (val === 'TD') {
                this.td = true;
            } else if (val) {
                const parts = val.split('-');
                if (parts[0]) this.floor = parseInt(parts[0]);
                if (parts[1]) this.room = parseInt(parts[1]);
                if (parts[2]) this.bed = parts[2];
            }
        }
    }"
    x-init="parseInitial()"
    class="space-y-4"
>
    {{-- Floor Selection --}}
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-400 dark:text-gray-500 w-10">{{ __('app.labels.floor') }}</span>
        <div class="inline-flex gap-1.5">
            @foreach(config('hospital.floors') as $floorNum => $floorData)
                <button
                    type="button"
                    @click="selectFloor({{ $floorNum }}); td = false"
                    :class="{
                        'border-primary-500 bg-white': floor === {{ $floorNum }} && !td,
                        'border-gray-200 hover:border-gray-300': floor !== {{ $floorNum }} || td
                    }"
                    class="px-4 py-1.5 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
                >{{ $floorData['name'] }}</button>
            @endforeach
            <button
                type="button"
                @click="td = true; floor = null; room = null; bed = null; $wire.set('{{ $getStatePath() }}', 'TD')"
                :class="{
                    'border-primary-500 bg-white': td,
                    'border-gray-200 hover:border-gray-300': !td
                }"
                class="px-4 py-1.5 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
            >TD</button>
        </div>
    </div>

    {{-- Room Selection (appears when floor selected) --}}
    <div
        x-show="floor && !td"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="flex items-center gap-3"
    >
        <span class="text-xs text-gray-400 dark:text-gray-500 w-10">{{ __('app.labels.room') }}</span>
        <div class="inline-flex flex-wrap gap-1.5">
            @foreach(range(1, 7) as $roomNum)
                <button
                    type="button"
                    x-show="floor && floors[floor]?.rooms[{{ $roomNum }}]"
                    @click="selectRoom({{ $roomNum }})"
                    :class="{
                        'border-primary-500 bg-white': room === {{ $roomNum }},
                        'border-gray-200 hover:border-gray-300': room !== {{ $roomNum }}
                    }"
                    class="w-9 h-9 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
                >{{ $roomNum }}</button>
            @endforeach
        </div>
    </div>

    {{-- Bed Selection (appears when room has multiple beds) --}}
    <div
        x-show="room && needsBedSelection && !td"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="flex items-center gap-3"
    >
        <span class="text-xs text-gray-400 dark:text-gray-500 w-10">{{ __('app.labels.bed') }}</span>
        <div class="inline-flex gap-1.5">
            <button
                type="button"
                @click="selectBed('1')"
                :class="{
                    'border-primary-500 bg-white': bed === '1',
                    'border-gray-200 hover:border-gray-300': bed !== '1'
                }"
                class="w-9 h-9 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
            >1</button>
            <button
                type="button"
                @click="selectBed('2')"
                :class="{
                    'border-primary-500 bg-white': bed === '2',
                    'border-gray-200 hover:border-gray-300': bed !== '2'
                }"
                class="w-9 h-9 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
            >2</button>
            <button
                type="button"
                x-show="selectedRoom && selectedRoom.beds >= 3"
                @click="selectBed('3')"
                :class="{
                    'border-primary-500 bg-white': bed === '3',
                    'border-gray-200 hover:border-gray-300': bed !== '3'
                }"
                class="w-9 h-9 text-sm text-gray-700 border rounded-lg transition-all cursor-pointer"
            >3</button>
        </div>
    </div>

    {{-- Selected display --}}
    <div x-show="roomValue" x-transition class="flex items-center gap-2 pt-1">
        <span class="text-xs text-gray-400">{{ __('app.labels.selected') }}:</span>
        <span
            class="text-sm text-gray-600 dark:text-gray-300"
            x-text="td ? 'TD' : (floor && room ? floors[floor].name + ' / {{ __('app.labels.room') }} ' + room + (bed ? ' / ' + bed : '') : '')"
        ></span>
    </div>
</div>
