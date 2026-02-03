@extends('patient-portal.layouts.portal')

@section('content')
    <div x-data="{ imageModal: { open: false, src: '', name: '' } }" class="divide-y divide-slate-100">
        <!-- Patient header -->
        <div class="p-6 sm:p-8 border-b border-slate-100">
            <div class="animate-fade-in-up">
                <p class="text-sm text-primary-600 font-medium mb-1">{{ __('app.portal.welcome') }}</p>
                <h2 class="text-2xl sm:text-3xl font-display font-bold text-slate-900">{{ $patient->full_name }}</h2>
            </div>
        </div>

        <!-- Personal & Medical Info -->
        <div class="p-6 sm:p-8">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="animate-fade-in-up animation-delay-100">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">{{ __('app.labels.personal_information') }}</h3>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-3">
                        @php
                            $personalFields = [
                                ['label' => __('app.labels.name'), 'value' => $patient->full_name],
                                ['label' => __('app.labels.date_of_birth'), 'value' => $patient->date_of_birth->format('d/m/Y')],
                                ['label' => __('app.labels.gender'), 'value' => __('app.gender.' . $patient->gender)],
                                ['label' => __('app.labels.phone'), 'value' => $patient->phone_number],
                                ['label' => __('app.labels.national_id'), 'value' => $patient->national_id],
                                ['label' => __('app.labels.residency'), 'value' => $patient->residency],
                                ['label' => __('app.labels.emergency_contact'), 'value' => $patient->emergency_contact_name . ' (' . $patient->emergency_contact_relationship . ') - ' . $patient->emergency_contact_phone],
                                ['label' => __('app.labels.insurance'), 'value' => $patient->health_insurance_number],
                            ];
                        @endphp
                        @foreach ($personalFields as $field)
                            @if ($field['value'])
                                <div class="text-sm">
                                    <dt class="text-slate-500 text-xs mb-0.5">{{ $field['label'] }}</dt>
                                    <dd class="text-slate-900 font-medium">{{ $field['value'] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Medical Information -->
                @if ($patient->medicalInfo)
                    <div class="animate-fade-in-up animation-delay-200">
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">{{ __('app.labels.medical_information') }}</h3>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-3">
                            <!-- Blood type badge -->
                            @if ($patient->medicalInfo->blood_type)
                                <div class="text-sm">
                                    <dt class="text-slate-500 text-xs mb-0.5">{{ __('app.labels.blood_type') }}</dt>
                                    <dd class="inline-flex items-center px-2.5 py-1 rounded-lg bg-coral-100 text-coral-700 text-sm font-bold">
                                        {{ $patient->medicalInfo->blood_type }}
                                    </dd>
                                </div>
                            @endif

                            @if ($patient->medicalInfo->height_cm || $patient->medicalInfo->weight_kg)
                                <div class="flex gap-4 text-sm">
                                    @if ($patient->medicalInfo->height_cm)
                                        <div class="flex-1 text-center p-2 rounded-lg bg-white border border-slate-100">
                                            <p class="text-lg font-semibold text-slate-900">{{ $patient->medicalInfo->height_cm }}<span class="text-xs text-slate-400 ml-0.5">cm</span></p>
                                            <p class="text-xs text-slate-500">{{ __('app.labels.height_cm') }}</p>
                                        </div>
                                    @endif
                                    @if ($patient->medicalInfo->weight_kg)
                                        <div class="flex-1 text-center p-2 rounded-lg bg-white border border-slate-100">
                                            <p class="text-lg font-semibold text-slate-900">{{ $patient->medicalInfo->weight_kg }}<span class="text-xs text-slate-400 ml-0.5">kg</span></p>
                                            <p class="text-xs text-slate-500">{{ __('app.labels.weight_kg') }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Allergies - highlighted -->
                            @if ($patient->medicalInfo->allergies)
                                <div class="rounded-xl bg-coral-50 border border-coral-100 p-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-4 h-4 text-coral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span class="text-xs font-semibold text-coral-700 uppercase tracking-wide">{{ __('app.labels.allergies') }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-coral-800">{{ $patient->medicalInfo->allergies }}</p>
                                </div>
                            @endif

                            @php
                                $medicalFields = [
                                    ['label' => __('app.labels.smoking'), 'value' => $patient->medicalInfo->smoking_status ? __('app.smoking_status.' . $patient->medicalInfo->smoking_status) : null],
                                    ['label' => __('app.labels.alcohol'), 'value' => $patient->medicalInfo->alcohol_use],
                                    ['label' => __('app.labels.pacemaker_implants'), 'value' => $patient->medicalInfo->pacemaker_implants],
                                    ['label' => __('app.labels.anesthesia_reactions'), 'value' => $patient->medicalInfo->anesthesia_reactions],
                                    ['label' => __('app.labels.current_medications'), 'value' => $patient->medicalInfo->current_medications],
                                ];
                            @endphp
                            @foreach ($medicalFields as $field)
                                @if ($field['value'])
                                    <div class="text-sm">
                                        <dt class="text-slate-500 text-xs mb-0.5">{{ $field['label'] }}</dt>
                                        <dd class="text-slate-900 font-medium">{{ $field['value'] }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Visit History with Documents -->
        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between mb-5 animate-fade-in-up animation-delay-200">
                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">{{ __('app.labels.visit_history') }}</h3>
                <span class="text-xs text-slate-400">{{ $patient->encounters->count() }}</span>
            </div>

            <div class="space-y-3">
                @forelse ($patient->encounters as $index => $encounter)
                    <div
                        x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }"
                        class="rounded-xl border border-slate-100 overflow-hidden animate-fade-in-up"
                        style="animation-delay: {{ 0.1 + $index * 0.05 }}s; opacity: 0;"
                    >
                        <!-- Encounter Header - Clickable -->
                        <button
                            @click="open = !open"
                            class="w-full p-4 text-left hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2">
                                    @if ($encounter->type === 'hospitalization')
                                        <span class="px-2 py-0.5 rounded-md bg-primary-50 text-primary-600 text-xs font-medium">
                                            {{ __('app.labels.hospitalization') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-xs font-medium">
                                            {{ __('app.labels.visit') }}
                                        </span>
                                    @endif
                                    @if ($encounter->documents->count() > 0)
                                        <span class="text-xs text-slate-400">{{ $encounter->documents->count() }} {{ __('app.labels.documents') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <time class="text-xs text-slate-400">
                                        {{ $encounter->admission_date?->format('d/m/Y') }}
                                    </time>
                                    <svg
                                        class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>

                            <h4 class="font-medium text-slate-900 mb-1">
                                {{ $encounter->main_complaint }}
                            </h4>

                            <p class="text-sm text-slate-500">{{ $encounter->doctor_name }}</p>
                        </button>

                        <!-- Expandable Content -->
                        <div
                            x-show="open"
                            x-collapse
                            class="border-t border-slate-100"
                        >
                            <div class="p-4 space-y-4">
                                <!-- Diagnosis & Treatment -->
                                @if ($encounter->diagnosis || $encounter->treatment)
                                    <div class="space-y-2 text-sm">
                                        @if ($encounter->diagnosis)
                                            <p><span class="text-slate-400">{{ __('app.labels.diagnosis') }}:</span> <span class="text-slate-600">{{ $encounter->diagnosis }}</span></p>
                                        @endif
                                        @if ($encounter->treatment)
                                            <p><span class="text-slate-400">{{ __('app.labels.treatment') }}:</span> <span class="text-slate-600">{{ $encounter->treatment }}</span></p>
                                        @endif
                                    </div>
                                @endif

                                <!-- Encounter files (lab results, operative work, imaging, discharge) -->
                                @php
                                    $encounterFiles = collect();
                                    if ($encounter->lab_results_file_path) {
                                        $mime = pathinfo($encounter->lab_results_file_path, PATHINFO_EXTENSION);
                                        $encounterFiles->push([
                                            'label' => __('app.labels.lab_results'),
                                            'url' => route('patient.encounters.lab-results', ['token' => $token, 'encounter' => $encounter->id]),
                                            'preview_url' => route('patient.encounters.preview', ['token' => $token, 'encounter' => $encounter->id, 'fileType' => 'lab-results']),
                                            'filename' => $encounter->lab_results_original_filename ?? 'lab-results.pdf',
                                            'is_image' => in_array($mime, ['jpg', 'jpeg', 'png', 'gif', 'webp']),
                                            'is_pdf' => $mime === 'pdf',
                                        ]);
                                    }
                                    if ($encounter->operative_work_file_path) {
                                        $mime = pathinfo($encounter->operative_work_file_path, PATHINFO_EXTENSION);
                                        $encounterFiles->push([
                                            'label' => __('app.labels.operative_procedure'),
                                            'url' => route('patient.encounters.operative-work', ['token' => $token, 'encounter' => $encounter->id]),
                                            'preview_url' => route('patient.encounters.preview', ['token' => $token, 'encounter' => $encounter->id, 'fileType' => 'operative-work']),
                                            'filename' => $encounter->operative_work_original_filename ?? 'operative-work.pdf',
                                            'is_image' => in_array($mime, ['jpg', 'jpeg', 'png', 'gif', 'webp']),
                                            'is_pdf' => $mime === 'pdf',
                                        ]);
                                    }
                                    if ($encounter->surgical_notes_file_path) {
                                        $mime = pathinfo($encounter->surgical_notes_file_path, PATHINFO_EXTENSION);
                                        $encounterFiles->push([
                                            'label' => __('app.labels.imaging_rtg'),
                                            'url' => route('patient.encounters.surgical-notes', ['token' => $token, 'encounter' => $encounter->id]),
                                            'preview_url' => route('patient.encounters.preview', ['token' => $token, 'encounter' => $encounter->id, 'fileType' => 'surgical-notes']),
                                            'filename' => $encounter->surgical_notes_original_filename ?? 'imaging.pdf',
                                            'is_image' => in_array($mime, ['jpg', 'jpeg', 'png', 'gif', 'webp']),
                                            'is_pdf' => $mime === 'pdf',
                                        ]);
                                    }
                                    if ($encounter->dischargePaper) {
                                        $encounterFiles->push([
                                            'label' => __('app.labels.discharge_paper'),
                                            'url' => route('patient.encounters.discharge-paper', ['token' => $token, 'encounter' => $encounter->id]),
                                            'preview_url' => null,
                                            'filename' => $encounter->dischargePaper->original_filename ?? 'discharge.pdf',
                                            'is_image' => false,
                                            'is_pdf' => false,
                                        ]);
                                    }
                                    $hasAnyFiles = $encounterFiles->isNotEmpty() || $encounter->documents->count() > 0;
                                @endphp

                                @if ($hasAnyFiles)
                                    <div class="pt-3 border-t border-slate-50">
                                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-2">{{ __('app.labels.documents') }}</p>
                                        <div class="space-y-1">
                                            {{-- Encounter-level files --}}
                                            @foreach ($encounterFiles as $file)
                                                <div class="group flex items-center gap-2 p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-slate-700">{{ $file['label'] }}</p>
                                                        <p class="text-xs text-slate-400 truncate">{{ $file['filename'] }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        @if ($file['is_image'] && $file['preview_url'])
                                                            <button
                                                                type="button"
                                                                @click="imageModal = { open: true, src: '{{ $file['preview_url'] }}', name: '{{ $file['label'] }}' }"
                                                                class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                                title="{{ __('app.actions.preview') }}"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                            </button>
                                                        @elseif ($file['is_pdf'] && $file['preview_url'])
                                                            <a
                                                                href="{{ $file['preview_url'] }}"
                                                                target="_blank"
                                                                class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                                title="{{ __('app.actions.preview') }}"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                            </a>
                                                        @endif
                                                        <a
                                                            href="{{ $file['url'] }}"
                                                            class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                            title="{{ __('app.actions.download') }}"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Regular documents --}}
                                            @foreach ($encounter->documents as $document)
                                                @php
                                                    $isImage = str_starts_with($document->mime_type, 'image/');
                                                    $isPdf = $document->mime_type === 'application/pdf';
                                                @endphp
                                                <div class="group flex items-center gap-2 p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-slate-700 truncate">
                                                            {{ $document->original_filename }}
                                                        </p>
                                                        <p class="text-xs text-slate-400">
                                                            {{ $document->created_at->format('d/m/Y') }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        @if ($isImage)
                                                            <button
                                                                type="button"
                                                                @click="imageModal = { open: true, src: '{{ route('patient.documents.preview', ['token' => $token, 'document' => $document->id]) }}', name: '{{ $document->original_filename }}' }"
                                                                class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                                title="{{ __('app.actions.preview') }}"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                            </button>
                                                        @elseif ($isPdf)
                                                            <a
                                                                href="{{ route('patient.documents.preview', ['token' => $token, 'document' => $document->id]) }}"
                                                                target="_blank"
                                                                class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                                title="{{ __('app.actions.preview') }}"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                            </a>
                                                        @endif
                                                        <a
                                                            href="{{ route('patient.documents.download', ['token' => $token, 'document' => $document->id]) }}"
                                                            class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                                                            title="{{ __('app.actions.download') }}"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 pt-2">{{ __('app.portal.no_documents') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-4">{{ __('app.empty.no_visits') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Image Preview Modal -->
        <div
            x-show="imageModal.open"
            x-cloak
            @click="imageModal.open = false"
            @keydown.escape.window="imageModal.open = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50"
        >
            <div @click.stop class="relative bg-white rounded-lg shadow-xl max-w-2xl max-h-[80vh] overflow-hidden">
                <button
                    @click="imageModal.open = false"
                    class="absolute top-2 right-2 p-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <img
                    :src="imageModal.src"
                    :alt="imageModal.name"
                    class="max-w-full max-h-[80vh] object-contain"
                />
            </div>
        </div>
    </div>
@endsection
