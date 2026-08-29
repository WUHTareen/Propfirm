<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-ink-900 text-slate-200">
    <header class="border-b border-ink-700 bg-ink-800">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500 font-display text-base font-extrabold text-ink-950">P</span>
                <span class="font-display text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</span>
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <span class="hidden text-sm text-slate-400 sm:inline">{{ auth()->user()->name }}</span>
                    <a href="{{ route('profile') }}" class="rounded-lg px-3 py-1.5 text-sm text-slate-300 transition hover:bg-ink-700 hover:text-white">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-ink-600 px-3 py-1.5 text-sm text-slate-300 transition hover:border-ink-600 hover:bg-ink-700 hover:text-white">Log out</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-8">
        @isset($header)
            <div class="mb-6">{{ $header }}</div>
        @endisset

        {{ $slot }}
    </main>
</body>
</html>
