@php
    $flag = function (?string $country) {
        if (! $country || strlen($country) !== 2) {
            return '🌐';
        }
        $code = strtoupper($country);

        return mb_convert_encoding('&#'.(127397 + ord($code[0])).';', 'UTF-8', 'HTML-ENTITIES')
            .mb_convert_encoding('&#'.(127397 + ord($code[1])).';', 'UTF-8', 'HTML-ENTITIES');
    };
    $medal = ['1' => 'text-amber-300', '2' => 'text-slate-300', '3' => 'text-orange-400'];
@endphp

<x-dashboard title="Leaderboard">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Leaderboard</h1>
        <p class="mt-1 text-sm text-slate-400">Top traders ranked by profit across their evaluation and funded accounts.</p>
    </x-slot:header>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Funded traders', 'value' => number_format($stats['funded_traders'])],
                ['label' => 'Top profit', 'value' => '$'.number_format($stats['top_profit'], 2)],
                ['label' => 'Reward points awarded', 'value' => number_format($stats['points_awarded'])],
                ['label' => 'Ranked traders', 'value' => number_format($stats['ranked_traders'])],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-bold text-white tabular-nums">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Account-size filter --}}
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('dashboard.leaderboard') }}"
           class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ ! $activeSize ? 'bg-brand-500 text-ink-950' : 'border border-ink-600 text-slate-300 hover:bg-ink-700' }}">All</a>
        @foreach ($sizes as $size)
            <a href="{{ route('dashboard.leaderboard', ['size' => $size]) }}"
               class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ (string) $activeSize === (string) $size ? 'bg-brand-500 text-ink-950' : 'border border-ink-600 text-slate-300 hover:bg-ink-700' }}">
                ${{ number_format($size / 1000, $size >= 1000 && $size % 1000 === 0 ? 0 : 1) }}K
            </a>
        @endforeach
    </div>

    {{-- Rankings --}}
    <div class="mt-4 overflow-x-auto rounded-2xl border border-ink-600 bg-ink-800">
        @if ($ranking->isEmpty())
            <div class="p-10 text-center">
                <h2 class="font-display text-lg font-semibold text-white">No ranked traders yet</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Rankings appear once traders start making profit on their accounts.</p>
            </div>
        @else
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-ink-700 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 font-medium">Rank</th>
                        <th class="px-4 py-3 font-medium">Trader</th>
                        <th class="px-4 py-3 font-medium">Country</th>
                        <th class="px-4 py-3 text-right font-medium">Profit</th>
                        <th class="px-4 py-3 text-right font-medium">Profit %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @foreach ($ranking as $i => $row)
                        @php $rank = $i + 1; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-display text-base font-bold tabular-nums {{ $medal[(string) $rank] ?? 'text-slate-400' }}">#{{ $rank }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-200">{{ $row['user']->name }}</td>
                            <td class="px-4 py-3 text-slate-400">
                                <span class="mr-1">{{ $flag($row['user']->country ?? null) }}</span>{{ $row['user']->country ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $row['profit'] >= 0 ? 'text-brand-400' : 'text-red-400' }}">
                                {{ $row['profit'] >= 0 ? '+' : '' }}${{ number_format($row['profit'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-300">{{ number_format($row['profit_pct'], 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-dashboard>
