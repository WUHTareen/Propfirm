@props(['title' => null, 'description' => null])

@php
    use App\Models\Setting;

    $siteName = config('app.name');
    $metaDescription = $description ?? Setting::get('hero_subtitle', 'Funded trading challenges on MetaTrader 5 & 4.');

    $nav = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Pricing', 'route' => 'pricing'],
        ['label' => 'Trading Rules', 'route' => 'rules'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'FAQ', 'route' => 'faq'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];

    $fbPixel = Setting::get('facebook_pixel_id');
    $gaId = Setting::get('google_analytics_id');
    $tawk = Setting::get('tawk_to_id');
    $contactEmail = Setting::get('contact_email', 'support@example.com');
    $telegram = Setting::get('contact_telegram');
    $trustpilot = Setting::get('trustpilot_url');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · '.$siteName : $siteName.' · Funded Trading Challenges' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Google Analytics (only when an ID is configured) --}}
    @if ($gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif

    {{-- Facebook Pixel (only when an ID is configured) --}}
    @if ($fbPixel)
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
            document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $fbPixel }}'); fbq('track', 'PageView');
        </script>
    @endif
</head>
<body class="min-h-full bg-ink-950 text-slate-200 antialiased">
    {{-- Header --}}
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-ink-800/80 bg-ink-950/85 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 font-display text-lg font-extrabold text-ink-950">P</span>
                <span class="font-display text-xl font-bold tracking-tight text-white">{{ $siteName }}</span>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden items-center gap-1 lg:flex">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($item['route']) ? 'text-brand-300' : 'text-slate-300 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-4 py-2 text-sm text-slate-300 transition hover:text-white sm:inline">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Get funded</a>
                @endauth
                <button type="button" x-on:click="open = !open" class="rounded-lg p-2 text-slate-300 hover:bg-ink-800 lg:hidden" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>
        </div>

        {{-- Mobile nav --}}
        <nav x-show="open" x-cloak class="border-t border-ink-800 px-6 py-3 lg:hidden">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs($item['route']) ? 'text-brand-300' : 'text-slate-300 hover:bg-ink-800' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </header>

    {{ $slot }}

    {{-- Footer --}}
    <footer class="border-t border-ink-800 bg-ink-950">
        <div class="mx-auto grid max-w-6xl gap-8 px-6 py-12 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500 font-display text-base font-extrabold text-ink-950">P</span>
                    <span class="font-display text-lg font-bold text-white">{{ $siteName }}</span>
                </div>
                <p class="mt-3 max-w-xs text-sm text-slate-500">Funded trading challenges evaluated on MetaTrader 5 & 4.</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Platform</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-400">
                    <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                    <li><a href="{{ route('rules') }}" class="hover:text-white">Trading Rules</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white">Get funded</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-400">
                    <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
                    @if ($trustpilot)
                        <li><a href="{{ $trustpilot }}" target="_blank" rel="noopener" class="hover:text-white">Trustpilot</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Legal</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-400">
                    <li><a href="{{ route('legal.terms') }}" class="hover:text-white">Terms of Service</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.refund') }}" class="hover:text-white">Refund & Risk Disclosure</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-ink-800/70">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-5 text-xs text-slate-500 sm:flex-row">
                <p>© {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <p class="max-w-md text-center sm:text-right">Trading involves substantial risk. Evaluation accounts are simulated.</p>
            </div>
        </div>
    </footer>

    @livewireScripts

    {{-- Tawk.to live chat (only when configured) --}}
    @if ($tawk)
        <script type="text/javascript">
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true; s1.src='https://embed.tawk.to/{{ $tawk }}';
                s1.charset='UTF-8'; s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
        </script>
    @endif
</body>
</html>
