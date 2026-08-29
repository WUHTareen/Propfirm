<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} · Funded Trading Challenges</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-ink-950 text-slate-200">
    <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
        <div class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 font-display text-lg font-extrabold text-ink-950">P</span>
            <span class="font-display text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
        </div>
        <nav class="flex items-center gap-2">
            @auth
                <a href="{{ url('/dashboard') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm text-slate-300 transition hover:text-white">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Get funded</a>
            @endauth
        </nav>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-24 text-center sm:py-32">
        <span class="inline-block rounded-full border border-ink-600 bg-ink-800 px-3 py-1 font-mono text-xs uppercase tracking-wide text-brand-300">
            Trade. Pass. Get funded.
        </span>
        <h1 class="mt-6 font-display text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-6xl" style="text-wrap: balance;">
            Prove your edge.<br>Trade our capital.
        </h1>
        <p class="mx-auto mt-6 max-w-xl text-lg text-slate-400">
            Buy an evaluation, hit the target while respecting the rules, and earn a funded account with up to an 80% profit split. All trading on MetaTrader 5 &amp; 4.
        </p>
        <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-6 py-3 font-semibold text-ink-950 transition hover:bg-brand-400">Start a challenge</a>
            <a href="{{ route('login') }}" class="rounded-lg border border-ink-600 px-6 py-3 font-medium text-slate-200 transition hover:bg-ink-800">Sign in</a>
        </div>
        <p class="mt-8 font-mono text-xs text-slate-600">Full marketing site &amp; live pricing arrive in a later build phase.</p>
    </main>
</body>
</html>
