<x-dashboard title="Heatmap">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Market heatmap</h1>
        <p class="mt-1 text-sm text-slate-400">Live crypto market performance. Data by TradingView.</p>
    </x-slot:header>

    <div class="overflow-hidden rounded-2xl border border-ink-600 bg-ink-800 p-2">
        {{-- TradingView Crypto Coins Heatmap widget --}}
        <div class="tradingview-widget-container" style="height:640px;width:100%">
            <div class="tradingview-widget-container__widget" style="height:100%;width:100%"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-crypto-coins-heatmap.js" async>
            {
                "dataSource": "Crypto",
                "blockSize": "market_cap_calc",
                "blockColor": "change",
                "locale": "en",
                "symbolUrl": "",
                "colorTheme": "dark",
                "hasTopBar": false,
                "isDataSetEnabled": false,
                "isZoomEnabled": true,
                "hasSymbolTooltip": true,
                "isMonoSize": false,
                "width": "100%",
                "height": "100%"
            }
            </script>
        </div>
    </div>
</x-dashboard>
