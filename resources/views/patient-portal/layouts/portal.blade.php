<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.portal.title') }}</title>
    <style>
        @import "tailwindcss";

        :root {
            --color-primary: #F12359;
            --color-secondary: #6B7280;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <header class="flex items-center gap-4 mb-8">
            <div class="h-12 w-12 rounded-full bg-white border border-slate-200 flex items-center justify-center overflow-hidden">
                <img src="{{ asset('images/kavaja-logo.png') }}" alt="Kavaja Hospital" class="h-10 w-10 object-contain">
            </div>
            <div>
                <p class="text-sm text-[var(--color-secondary)]">{{ __('app.portal.subtitle') }}</p>
                <h1 class="text-2xl font-semibold text-slate-900">{{ __('app.portal.title') }}</h1>
            </div>
        </header>

        <main class="bg-white shadow-sm rounded-xl border border-slate-100">
            @yield('content')
        </main>

        <footer class="mt-8 text-sm text-[var(--color-secondary)] text-center">
            {{ __('app.portal.footer') }}
        </footer>
    </div>
</body>
</html>
