<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit" class="flex flex-wrap gap-4 items-end">
            {{ $this->form }}
            <x-filament::button type="submit">
                {{ __('app.actions.generate_report') }}
            </x-filament::button>
        </form>

        @if ($report)
            <div class="grid md:grid-cols-3 gap-4">
                @foreach ([
                    ['label' => __('app.reports.new_patients'), 'value' => $report['totals']['new_patients'] ?? 0],
                    ['label' => __('app.reports.total_visits'), 'value' => $report['totals']['total_visits'] ?? 0],
                    ['label' => __('app.reports.total_hospitalizations'), 'value' => $report['totals']['total_hospitalizations'] ?? 0],
                    ['label' => __('app.reports.total_surgeries'), 'value' => $report['totals']['total_surgeries'] ?? 0],
                    ['label' => __('app.reports.total_discharges'), 'value' => $report['totals']['total_discharges'] ?? 0],
                ] as $stat)
                    <x-filament::section>
                        <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                        <div class="text-2xl font-semibold text-slate-900">{{ $stat['value'] }}</div>
                    </x-filament::section>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold mb-2">{{ __('app.labels.most_common_diagnoses') }}</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($report['common_diagnoses'] ?? [] as $row)
                            <li class="flex justify-between">
                                <span>{{ $row['diagnosis'] }}</span>
                                <span class="font-semibold">{{ $row['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold mb-2">{{ __('app.labels.doctors_by_patients') }}</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($report['doctors_by_patients'] ?? [] as $doctor => $rows)
                            @php
                                $total = collect($rows)->sum('count');
                            @endphp
                            <li class="flex justify-between">
                                <span>{{ $doctor }}</span>
                                <span class="font-semibold">{{ $total }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
