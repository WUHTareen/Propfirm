<x-dashboard title="Economic Calendar">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Economic calendar</h1>
        <p class="mt-1 text-sm text-slate-400">Upcoming market-moving events. Data by TradingView.</p>
    </x-slot:header>

    <div class="overflow-hidden rounded-2xl border border-ink-600 bg-ink-800 p-2">
        {{-- TradingView Economic Calendar widget --}}
        <div class="tradingview-widget-container" style="height:660px;width:100%">
            <div class="tradingview-widget-container__widget" style="height:100%;width:100%"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-events.js" async>
            {
                "colorTheme": "dark",
                "isTransparent": true,
                "locale": "en",
                "countryFilter": "us,eu,gb,jp,ch,au,ca,nz,cn",
                "importanceFilter": "0,1",
                "width": "100%",
                "height": "100%"
            }
            </script>
        </div>
    </div>
</x-dashboard>
