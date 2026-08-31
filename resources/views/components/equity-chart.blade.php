@props(['snapshots', 'startingBalance' => null])

@php
    $points = $snapshots->map(fn ($s) => (float) $s->equity)->values();
    $count = $points->count();
@endphp

@if ($count < 2)
    <div class="flex h-44 items-center justify-center rounded-lg border border-dashed border-ink-600 text-sm text-slate-500">
        Equity curve will appear once your account is active and synced.
    </div>
@else
    @php
        $w = 600; $h = 180; $padX = 6; $padY = 12;
        $min = $points->min();
        $max = $points->max();
        if ($startingBalance !== null) { $min = min($min, (float) $startingBalance); $max = max($max, (float) $startingBalance); }
        $range = ($max - $min) ?: 1;

        $x = fn ($i) => round($padX + ($i / ($count - 1)) * ($w - 2 * $padX), 2);
        $y = fn ($v) => round($h - $padY - (($v - $min) / $range) * ($h - 2 * $padY), 2);

        $coords = $points->map(fn ($v, $i) => $x($i).','.$y($v));
        $line = $coords->implode(' ');
        $area = 'M '.$x(0).','.($h - $padY).' L '.$coords->implode(' L ').' L '.$x($count - 1).','.($h - $padY).' Z';
        $lastUp = $points->last() >= $points->first();
        $stroke = $lastUp ? '#2DD4C0' : '#f87171';
    @endphp

    <div class="overflow-x-auto">
        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-44 w-full" preserveAspectRatio="none" role="img" aria-label="Equity curve">
            <defs>
                <linearGradient id="equityFill" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="{{ $stroke }}" stop-opacity="0.28" />
                    <stop offset="100%" stop-color="{{ $stroke }}" stop-opacity="0" />
                </linearGradient>
            </defs>
            @if ($startingBalance !== null)
                <line x1="{{ $padX }}" x2="{{ $w - $padX }}" y1="{{ $y((float) $startingBalance) }}" y2="{{ $y((float) $startingBalance) }}"
                      stroke="#475569" stroke-width="1" stroke-dasharray="4 4" />
            @endif
            <path d="{{ $area }}" fill="url(#equityFill)" />
            <polyline points="{{ $line }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
            <circle cx="{{ $x($count - 1) }}" cy="{{ $y($points->last()) }}" r="3.5" fill="{{ $stroke }}" />
        </svg>
    </div>
@endif
