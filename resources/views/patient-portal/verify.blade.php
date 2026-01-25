@extends('patient-portal.layouts.portal')

@section('content')
    <div class="p-8">
        <h2 class="text-xl font-semibold mb-2">{{ __('app.portal.verify_title') }}</h2>
        <p class="text-sm text-slate-600 mb-6">{{ __('app.portal.verify_text') }}</p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('patient.verify.submit', ['token' => $token]) }}" class="space-y-4">
            @csrf
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">{{ __('app.labels.date_of_birth') }}</label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth') }}"
                    required
                    class="w-full rounded-md border border-slate-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                >
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[var(--color-primary)] px-4 py-2 text-white font-semibold hover:opacity-90 transition">
                {{ __('app.portal.view_records') }}
            </button>
        </form>
    </div>
@endsection
