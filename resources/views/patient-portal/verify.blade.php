@extends('patient-portal.layouts.portal')

@section('content')
    <div class="p-6 sm:p-10">
        <!-- Title -->
        <div class="text-center mb-8 animate-fade-in-up">
            <h2 class="text-2xl font-display font-bold text-slate-900 mb-2">{{ __('app.portal.verify_title') }}</h2>
            <p class="text-slate-500">{{ __('app.portal.verify_text') }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 animate-fade-in">
                <div class="rounded-xl bg-coral-50 text-coral-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('patient.verify.submit', ['token' => $token]) }}" class="space-y-5 animate-fade-in-up animation-delay-100">
            @csrf
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-600 mb-2">
                    {{ __('app.labels.date_of_birth') }}
                </label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth') }}"
                    required
                    class="w-full rounded-lg border border-slate-200 px-4 py-3 text-slate-900 transition-colors hover:border-slate-300 focus:border-primary-500"
                >
            </div>

            <button type="submit" class="btn-primary w-full rounded-lg px-6 py-3 text-white font-medium">
                {{ __('app.portal.view_records') }}
            </button>
        </form>

        <!-- Minimal trust indicator -->
        <p class="mt-8 text-center text-xs text-slate-400 animate-fade-in animation-delay-200">
            Lidhje e sigurt dhe e enkriptuar
        </p>
    </div>
@endsection
