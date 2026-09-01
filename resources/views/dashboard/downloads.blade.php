@php
    // Official MetaQuotes download endpoints. These are the canonical public links.
    $platforms = [
        'mt5' => [
            'label' => 'MetaTrader 5',
            'tagline' => 'Recommended — faster execution, more timeframes and order types.',
            'links' => [
                'Windows' => 'https://download.mql5.com/cdn/web/metaquotes.software.corp/mt5/mt5setup.exe',
                'macOS' => 'https://download.mql5.com/cdn/web/metaquotes.software.corp/mt5/MetaTrader5.dmg',
                'Android' => 'https://download.mql5.com/cdn/mobile/mt5/android?server=MetaQuotes-Demo',
                'iOS' => 'https://apps.apple.com/us/app/metatrader-5/id413251709',
            ],
        ],
        'mt4' => [
            'label' => 'MetaTrader 4',
            'tagline' => 'Classic platform — widely supported and lightweight.',
            'links' => [
                'Windows' => 'https://download.mql5.com/cdn/web/metaquotes.software.corp/mt4/mt4setup.exe',
                'macOS' => 'https://download.mql5.com/cdn/web/metaquotes.software.corp/mt4/MetaTrader4.dmg',
                'Android' => 'https://download.mql5.com/cdn/mobile/mt4/android?server=MetaQuotes-Demo',
                'iOS' => 'https://apps.apple.com/us/app/metatrader-4/id496212596',
            ],
        ],
    ];
    $osIcons = [
        'Windows' => 'M3 5.1 10.5 4v7.5H3V5.1ZM10.5 12.5V20L3 18.9V12.5h7.5ZM11.5 3.85 21 2.5v9H11.5V3.85ZM21 12.5V21.5l-9.5-1.35V12.5H21Z',
        'macOS' => 'M16.5 3c.1 1-.3 2-1 2.8-.7.8-1.7 1.4-2.7 1.3-.1-1 .4-2 1-2.7C14.6 3.6 15.6 3.1 16.5 3ZM18.8 17c-.5 1.2-.8 1.7-1.5 2.7-.9 1.4-2.2 3.1-3.8 3.1-1.4 0-1.8-.9-3.7-.9s-2.3.9-3.7.9c-1.6 0-2.8-1.5-3.7-2.9-2.5-3.9-2.8-8.5-1.2-11 1.1-1.7 2.9-2.8 4.5-2.8 1.7 0 2.7 1 4.1 1 1.3 0 2.1-1 4.1-1 1.4 0 3 .8 4 2.2-3.6 2-3 7.1.6 8.7Z',
        'Android' => 'M6 9v7.5A1.5 1.5 0 0 0 7.5 18H9v3a1.5 1.5 0 0 0 3 0v-3h0v3a1.5 1.5 0 0 0 3 0v-3h1.5A1.5 1.5 0 0 0 18 16.5V9H6ZM4.5 9A1.5 1.5 0 0 0 3 10.5v4a1.5 1.5 0 0 0 3 0v-4A1.5 1.5 0 0 0 4.5 9ZM19.5 9A1.5 1.5 0 0 0 18 10.5v4a1.5 1.5 0 0 0 3 0v-4A1.5 1.5 0 0 0 19.5 9ZM16 3.5l1-1.6-.7-.4-1.1 1.7A6.9 6.9 0 0 0 12 2.6c-1.1 0-2.2.2-3.2.6L7.7 1.5l-.7.4 1 1.6A6 6 0 0 0 6 8h12a6 6 0 0 0-2-4.5ZM9.5 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm5 0a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z',
        'iOS' => 'M16.5 3c.1 1-.3 2-1 2.8-.7.8-1.7 1.4-2.7 1.3-.1-1 .4-2 1-2.7C14.6 3.6 15.6 3.1 16.5 3ZM18.8 17c-.5 1.2-.8 1.7-1.5 2.7-.9 1.4-2.2 3.1-3.8 3.1-1.4 0-1.8-.9-3.7-.9s-2.3.9-3.7.9c-1.6 0-2.8-1.5-3.7-2.9-2.5-3.9-2.8-8.5-1.2-11 1.1-1.7 2.9-2.8 4.5-2.8 1.7 0 2.7 1 4.1 1 1.3 0 2.1-1 4.1-1 1.4 0 3 .8 4 2.2-3.6 2-3 7.1.6 8.7Z',
    ];
@endphp

<x-dashboard title="Downloads">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Downloads</h1>
        <p class="mt-1 text-sm text-slate-400">Install the trading terminal, then log in with the credentials issued for your account.</p>
    </x-slot:header>

    <div x-data="{ tab: 'mt5' }">
        {{-- Platform toggle --}}
        <div class="inline-flex rounded-xl border border-ink-600 bg-ink-800 p-1">
            @foreach ($platforms as $key => $platform)
                <button type="button" x-on:click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-brand-500 text-ink-950' : 'text-slate-300 hover:text-white'"
                        class="rounded-lg px-5 py-2 text-sm font-semibold transition">{{ $platform['label'] }}</button>
            @endforeach
        </div>

        @foreach ($platforms as $key => $platform)
            <div x-show="tab === '{{ $key }}'" x-cloak class="mt-5">
                <p class="mb-4 text-sm text-slate-400">{{ $platform['tagline'] }}</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($platform['links'] as $os => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="group flex flex-col items-center gap-3 rounded-2xl border border-ink-600 bg-ink-800 p-6 text-center transition hover:border-brand-500/50 hover:bg-ink-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-9 w-9 text-slate-400 transition group-hover:text-brand-300">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $osIcons[$os] }}" />
                            </svg>
                            <div>
                                <p class="font-medium text-slate-100">{{ $os }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">Download for {{ $os }}</p>
                            </div>
                            <span class="mt-1 inline-flex items-center gap-1 rounded-lg bg-brand-500/15 px-3 py-1.5 text-xs font-semibold text-brand-300 transition group-hover:bg-brand-500 group-hover:text-ink-950">
                                Get {{ $platform['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">How to log in</h2>
        <ol class="mt-3 list-inside list-decimal space-y-1.5 text-sm text-slate-400">
            <li>Install the platform for your device above.</li>
            <li>Open it and choose <span class="text-slate-200">Login to an existing account</span>.</li>
            <li>Enter the <span class="text-slate-200">server</span>, <span class="text-slate-200">login</span> and <span class="text-slate-200">password</span> shown on your account page.</li>
            <li>Your trades and balance sync back here automatically.</li>
        </ol>
        <a href="{{ route('dashboard') }}" class="mt-4 inline-flex text-sm text-brand-300 hover:text-brand-200">Go to my accounts →</a>
    </div>
</x-dashboard>
