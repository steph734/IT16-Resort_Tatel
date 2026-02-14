<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Dark mode detection --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';
            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Fallback background color --}}
    <style>
        html {
            background-color: #ffffff; /* light mode fallback */
        }
        html.dark {
            background-color: #24292f; /* dark mode fallback */
        }
    </style>

    <title>Jara's Palm Beach Resort</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('icons/logo.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Google Fonts - Poppins and Crimson Text/Pro --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Crimson+Text:wght@400;600;700&family=Crimson+Pro:wght@400;600;700;800&display=swap" rel="stylesheet">

    {{-- Alpine.js for interactive components --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Tailwind + JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Additional styles from child views --}}
    @stack('styles')
</head>
<body class="font-sans antialiased">
    @yield('content')

    {{-- Alerts --}}
    @include('alerts.error')
</body>
</html>
