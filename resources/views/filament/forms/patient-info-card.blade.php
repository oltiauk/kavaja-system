@php
    $emergencyContact = trim(collect([
        $patient?->emergency_contact_name,
        $patient?->emergency_contact_phone,
    ])->filter()->join(' • '));
@endphp

@if (! $patient)
    <div class="text-sm text-gray-500">—</div>
@else
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.name') }}</div>
                <div class="font-medium">{{ $patient->full_name }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.date_of_birth') }}</div>
                <div class="font-medium">{{ $patient->date_of_birth?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.phone') }}</div>
                <div class="font-medium">{{ $patient->phone_number ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.national_id') }}</div>
                <div class="font-medium">{{ $patient->national_id ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.residency') }}</div>
                <div class="font-medium">{{ $patient->residency ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.emergency_contact') }}</div>
                <div class="font-medium">{{ $emergencyContact ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('app.labels.insurance') }}</div>
                <div class="font-medium">{{ $patient->health_insurance_number ?? '—' }}</div>
            </div>
        </div>
    </div>
@endif
