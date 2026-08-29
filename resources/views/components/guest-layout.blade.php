<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-ink-950 px-4 py-12">
    <div class="mb-8 flex flex-col items-center">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 font-display text-lg font-extrabold text-ink-950">P</span>
            <span class="font-display text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
        </a>
    </div>

    {{ $slot }}

    <p class="mt-8 text-center text-xs text-slate-600">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Trading involves risk.
    </p>
</body>
</html>
