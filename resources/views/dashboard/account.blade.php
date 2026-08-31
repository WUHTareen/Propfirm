@php
    $equity = $account->displayEquity();
    $statusColor = match ($account->status) {
        'active' => 'bg-brand-500/15 text-brand-300',
        'passed', 'funded' => 'bg-emerald-500/15 text-emerald-300',
        'breached', 'disabled' => 'bg-red-500/15 text-red-300',
        default => 'bg-amber-500/15 text-amber-300',
    };
    $bar = function (float $pct, string $tone) {
        $color = match ($tone) {
            'good' => 'bg-brand-500',
            'warn' => 'bg-amber-500',
            'bad' => 'bg-red-500',
            default => 'bg-slate-500',
        };
        return '<div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-ink-900"><div class="h-full '.$color.'" style="width: '.min(100, $pct).'%"></div></div>';
    };
    $ddTone = fn (float $p) => $p >= 80 ? 'bad' : ($p >= 50 ? 'warn' : 'good');
@endphp

<x-dashboard title="Account">
    <x-slot:header>
        <a href="{{ route('dashboard') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-400 transition hover:text-slate-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Overview
        </a>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-2xl font-bold text-white">${{ number_format($account->account_size) }} Account</h1>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColor }}">{{ str_replace('_', ' ', ucfirst($account->status)) }}</span>
        </div>
        <p class="mt-1 text-sm text-slate-400">{{ strtoupper($account->platform) }} · {{ str_replace('_', '-', ucfirst($account->challenge_type)) }} · Phase {{ $account->current_phase }}</p>
    </x-slot:header>

    {{-- Metric tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Equity</p>
            <p class="mt-1 font-display text-xl font-bold text-white tabular-nums">{{ $equity !== null ? '$'.number_format($equity, 2) : '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">Start ${{ number_format($account->starting_balance ?? $account->account_size) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Profit</p>
            <p class="mt-1 font-display text-xl font-bold tabular-nums {{ $account->profitAmount() >= 0 ? 'text-brand-300' : 'text-red-400' }}">
                {{ $account->hasLiveMetrics() ? ($account->profitAmount() >= 0 ? '+' : '').'$'.number_format($account->profitAmount(), 2) : '—' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ $account->hasLiveMetrics() ? $account->profitPercent().'%' : 'awaiting data' }}</p>
        </div>
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Profit target</p>
            <p class="mt-1 font-display text-xl font-bold text-white tabular-nums">{{ $account->profit_target_amount ? '$'.number_format($account->profit_target_amount) : '—' }}</p>
            {!! $bar($account->profitTargetProgress(), 'good') !!}
            <p class="mt-1 text-xs text-slate-500">{{ $account->profitTargetProgress() }}% reached</p>
        </div>
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Trading days</p>
            <p class="mt-1 font-display text-xl font-bold text-white tabular-nums">{{ $account->trading_days_count }} <span class="text-sm font-normal text-slate-500">/ {{ $account->requiredTradingDays() }}</span></p>
        </div>
    </div>

    {{-- Risk + chart --}}
    <div class="mt-4 grid gap-4 lg:grid-cols-[1fr,20rem]">
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <h2 class="font-display text-base font-semibold text-white">Equity curve</h2>
            <div class="mt-4">
                <x-equity-chart :snapshots="$snapshots" :starting-balance="$account->starting_balance" />
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-300">Daily loss used</p>
                    <p class="text-sm tabular-nums text-slate-400">{{ $account->dailyDrawdownUsedPercent() }}%</p>
                </div>
                {!! $bar($account->dailyDrawdownUsedPercent(), $ddTone($account->dailyDrawdownUsedPercent())) !!}
                <p class="mt-2 text-xs text-slate-500">Limit ${{ number_format($account->daily_drawdown_limit ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-300">Max loss used</p>
                    <p class="text-sm tabular-nums text-slate-400">{{ $account->maxDrawdownUsedPercent() }}%</p>
                </div>
                {!! $bar($account->maxDrawdownUsedPercent(), $ddTone($account->maxDrawdownUsedPercent())) !!}
                <p class="mt-2 text-xs text-slate-500">Limit ${{ number_format($account->max_drawdown_limit ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Credentials --}}
    <div class="mt-4 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Login credentials</h2>
        @if ($account->isCredentialed())
            <div class="mt-4 grid gap-4 sm:grid-cols-3" x-data="{ show: false }">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Login</p>
                    <p class="mt-1 font-mono text-slate-200">{{ $account->login }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Server</p>
                    <p class="mt-1 font-mono text-slate-200">{{ $account->server ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Password</p>
                    <p class="mt-1 flex items-center gap-2">
                        <span class="font-mono text-slate-200" x-text="show ? @js($account->password) : '••••••••'"></span>
                        <button type="button" class="text-xs text-brand-400 hover:text-brand-300" x-on:click="show = !show" x-text="show ? 'Hide' : 'Show'"></button>
                    </p>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">Use these to log in on the MetaTrader {{ $account->platform === 'mt5' ? '5' : '4' }} app. Download it from the Downloads section.</p>
        @else
            <p class="mt-3 rounded-lg bg-ink-900 px-4 py-3 text-sm text-slate-400">Your credentials are being prepared. You'll be notified when your MT5/MT4 login is ready.</p>
        @endif
    </div>
</x-dashboard>
