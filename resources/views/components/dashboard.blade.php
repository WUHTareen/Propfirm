@props(['title' => 'Dashboard'])

@php
    $nav = [
        ['label' => 'Overview', 'route' => 'dashboard', 'icon' => 'M3 13.5 12 4l9 9.5M4.5 12v7.5h15V12'],
        ['label' => 'Buy Challenge', 'route' => 'dashboard.buynow', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'],
        ['label' => 'Orders', 'route' => 'dashboard.orders', 'icon' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z'],
        ['label' => 'Withdrawals', 'route' => 'dashboard.withdrawal', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['label' => 'KYC', 'route' => 'dashboard.kyc', 'icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0ZM10.5 15.75a3 3 0 0 0-6 0v.75h6v-.75Z'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-ink-900 text-slate-200">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="hidden w-60 shrink-0 flex-col border-r border-ink-700 bg-ink-800 lg:flex">
            <div class="flex h-16 items-center gap-2.5 border-b border-ink-700 px-5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500 font-display text-base font-extrabold text-ink-950">P</span>
                <span class="font-display text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</span>
            </div>
            <nav class="flex flex-1 flex-col gap-1 p-3">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                              {{ $active ? 'bg-brand-500/15 text-brand-300' : 'text-slate-300 hover:bg-ink-700 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between gap-4 border-b border-ink-700 bg-ink-800 px-5">
                <a href="{{ route('dashboard.buynow') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-3.5 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Order
                </a>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-slate-400 sm:inline">{{ auth()->user()->name }}</span>
                    <a href="{{ route('profile') }}" class="rounded-lg px-3 py-1.5 text-sm text-slate-300 transition hover:bg-ink-700 hover:text-white">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-ink-600 px-3 py-1.5 text-sm text-slate-300 transition hover:bg-ink-700 hover:text-white">Log out</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-5 py-8 sm:px-8">
                <div class="mx-auto max-w-5xl">
                    @isset($header)
                        <div class="mb-6">{{ $header }}</div>
                    @endisset
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
