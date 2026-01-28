<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.portal.title') }} — Spitali Kavajë</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fef2f4',
                            100: '#fde6ea',
                            200: '#fad1d9',
                            300: '#f5a9b8',
                            400: '#ef7691',
                            500: '#e4476b',
                            600: '#F12359',
                            700: '#c41745',
                            800: '#a4163c',
                            900: '#8c1638',
                        },
                        coral: {
                            50: '#fff5f5',
                            100: '#ffe0e0',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                        },
                        sand: {
                            50: '#fdfcfb',
                            100: '#f9f7f4',
                            200: '#f3efe9',
                            300: '#e8e2d9',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        display: ['Fraunces', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Smooth fade-in animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .animate-slide-in {
            animation: slideInRight 0.5s ease-out forwards;
        }

        .animation-delay-100 { animation-delay: 0.1s; opacity: 0; }
        .animation-delay-200 { animation-delay: 0.2s; opacity: 0; }
        .animation-delay-300 { animation-delay: 0.3s; opacity: 0; }
        .animation-delay-400 { animation-delay: 0.4s; opacity: 0; }
        .animation-delay-500 { animation-delay: 0.5s; opacity: 0; }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f3efe9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #fad1d9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #f5a9b8;
        }

        /* Input focus ring */
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(241, 35, 89, 0.15);
        }

        /* Button hover effect */
        .btn-primary {
            background: linear-gradient(135deg, #F12359 0%, #c41745 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(241, 35, 89, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="max-w-xl mx-auto px-4 py-8 sm:py-12">
        <!-- Header -->
        <header class="flex items-center gap-3 mb-6 animate-fade-in-up">
            <div class="h-12 w-12 rounded-xl bg-white shadow-sm flex items-center justify-center overflow-hidden p-1.5">
                <img src="{{ asset('images/kavaja-logo.png') }}" alt="Spitali Kavajë" class="h-full w-full object-contain">
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900">{{ __('app.portal.title') }}</h1>
                <p class="text-sm text-slate-400">{{ __('app.portal.subtitle') }}</p>
            </div>
        </header>

        <!-- Main content card -->
        <main class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-fade-in-up animation-delay-100">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-6 text-center animate-fade-in animation-delay-200">
            <p class="text-xs text-slate-400">{{ __('app.portal.footer') }}</p>
        </footer>
    </div>
</body>
</html>
