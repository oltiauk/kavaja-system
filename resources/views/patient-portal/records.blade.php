@extends('patient-portal.layouts.portal')

@section('content')
    <div class="p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600">{{ __('app.portal.welcome') }}</p>
                <h2 class="text-2xl font-semibold">{{ $patient->full_name }}</h2>
            </div>
            <div class="text-sm text-[var(--color-secondary)]">
                {{ __('app.labels.token') }}: {{ substr($token, 0, 8) }}…
            </div>
        </div>

        <section class="grid md:grid-cols-2 gap-4">
            <div class="rounded-lg border border-slate-100 p-4">
                <h3 class="text-lg font-semibold mb-2">{{ __('app.labels.personal_information') }}</h3>
                <dl class="space-y-1 text-sm text-slate-700">
                    <div class="flex justify-between"><dt>{{ __('app.labels.name') }}</dt><dd>{{ $patient->full_name }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.date_of_birth') }}</dt><dd>{{ $patient->date_of_birth->toDateString() }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.gender') }}</dt><dd class="capitalize">{{ __('app.gender.' . $patient->gender) }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.phone') }}</dt><dd>{{ $patient->phone_number }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.national_id') }}</dt><dd>{{ $patient->national_id }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.residency') }}</dt><dd>{{ $patient->residency }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.emergency_contact') }}</dt><dd>{{ $patient->emergency_contact_name }} ({{ $patient->emergency_contact_relationship }}) - {{ $patient->emergency_contact_phone }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('app.labels.insurance') }}</dt><dd>{{ $patient->health_insurance_number }}</dd></div>
                </dl>
            </div>

            @if ($patient->medicalInfo)
                <div class="rounded-lg border border-slate-100 p-4">
                    <h3 class="text-lg font-semibold mb-2">{{ __('app.labels.medical_information') }}</h3>
                    <dl class="space-y-1 text-sm text-slate-700">
                        <div class="flex justify-between"><dt>{{ __('app.labels.blood_type') }}</dt><dd>{{ $patient->medicalInfo->blood_type }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.height_cm') }}</dt><dd>{{ $patient->medicalInfo->height_cm }} cm</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.weight_kg') }}</dt><dd>{{ $patient->medicalInfo->weight_kg }} kg</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.allergies') }}</dt><dd class="font-semibold text-[var(--color-primary)]">{{ $patient->medicalInfo->allergies }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.smoking') }}</dt><dd>{{ $patient->medicalInfo->smoking_status ? __('app.smoking_status.' . $patient->medicalInfo->smoking_status) : '' }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.alcohol') }}</dt><dd>{{ $patient->medicalInfo->alcohol_use }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.drug_history') }}</dt><dd>{{ $patient->medicalInfo->drug_use_history }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.pacemaker_implants') }}</dt><dd>{{ $patient->medicalInfo->pacemaker_implants }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.anesthesia_reactions') }}</dt><dd>{{ $patient->medicalInfo->anesthesia_reactions }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('app.labels.current_medications') }}</dt><dd>{{ $patient->medicalInfo->current_medications }}</dd></div>
                    </dl>
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-slate-100 p-4">
            <h3 class="text-lg font-semibold mb-4">{{ __('app.labels.visit_history') }}</h3>
            <div class="space-y-3">
                @foreach ($patient->encounters as $encounter)
                    <div class="border border-slate-100 rounded-lg p-3">
                        <div class="flex items-center justify-between text-sm text-slate-600 mb-2">
                            <span class="uppercase tracking-wide font-semibold">
                                {{ $encounter->type === 'visit' ? __('app.labels.visit') : __('app.labels.hospitalization') }}
                            </span>
                            <span>{{ $encounter->admission_date?->toDateString() }}</span>
                        </div>
                        <p class="font-semibold text-slate-900">{{ $encounter->main_complaint }}</p>
                        <p class="text-sm text-slate-700">{{ __('app.labels.doctor') }}: {{ $encounter->doctor_name }}</p>
                        @if ($encounter->diagnosis)
                            <p class="text-sm text-slate-700">{{ __('app.labels.diagnosis') }}: {{ $encounter->diagnosis }}</p>
                        @endif
                        @if ($encounter->treatment)
                            <p class="text-sm text-slate-700">{{ __('app.labels.treatment') }}: {{ $encounter->treatment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-slate-100 p-4">
            <h3 class="text-lg font-semibold mb-4">{{ __('app.labels.documents_section') }}</h3>
            @if ($documents->isEmpty())
                <p class="text-sm text-slate-600">{{ __('app.portal.no_documents') }}</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($documents as $document)
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900">{{ $document->original_filename }}</p>
                                <p class="text-sm text-slate-600">{{ $document->created_at->toDateTimeString() }}</p>
                            </div>
                            <a
                                href="{{ route('patient.documents.download', ['token' => $token, 'document' => $document->id]) }}"
                                class="text-[var(--color-primary)] font-semibold text-sm"
                            >{{ __('app.actions.download') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
