@php
    $report = $this->report;
    $summary = $report['summary'];
    $outcomes = $report['outcomes'];

    $money = fn ($n) => '$'.number_format((float) $n, 2);

    $typeLabel = fn (string $t) => match ($t) {
        'two_step' => '2-Step',
        'one_step' => '1-Step',
        'instant' => 'Instant',
        default => $t,
    };

    $tiles = [
        ['label' => 'Revenue', 'value' => $money($summary['revenue']), 'hint' => 'Paid orders only'],
        ['label' => 'Paid orders', 'value' => number_format($summary['orders_paid']), 'hint' => number_format($summary['orders_total']).' placed'],
        ['label' => 'Average order', 'value' => $money($summary['average_order']), 'hint' => 'Per paid order'],
        ['label' => 'New traders', 'value' => number_format($summary['new_traders']), 'hint' => 'Signed up in range'],
    ];
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($tiles as $tile)
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $tile['label'] }}</div>
                <div class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $tile['value'] }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $tile['hint'] }}</div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- What the firm owes --}}
    <x-filament::section>
        <x-slot name="heading">Payout obligations</x-slot>
        <x-slot name="description">Money promised to funded traders. "Owed" is every request approved or awaiting review.</x-slot>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">Currently owed</div>
                <div class="mt-1 text-xl font-semibold {{ $summary['payouts_pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-950 dark:text-white' }}">
                    {{ $money($summary['payouts_pending']) }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">Paid in this range</div>
                <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $money($summary['payouts_paid']) }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- Evaluation outcomes --}}
    <x-filament::section>
        <x-slot name="heading">Evaluation outcomes</x-slot>
        <x-slot name="description">All accounts ever assigned — a pass rate over a single date range would be misleading, since evaluations rarely start and finish inside one.</x-slot>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'Active' => $outcomes['active'],
                'Passed or funded' => $outcomes['passed'],
                'Breached' => $outcomes['breached'],
                'Pass rate' => $outcomes['pass_rate'] === null ? 'n/a' : $outcomes['pass_rate'].'%',
            ] as $label => $value)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Breakdowns --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">By challenge type</x-slot>

            @if ($report['byType']->isEmpty())
                <p class="text-sm text-gray-500">No paid orders in this range.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="pb-2 font-medium">Type</th>
                            <th class="pb-2 font-medium">Orders</th>
                            <th class="pb-2 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($report['byType'] as $row)
                            <tr>
                                <td class="py-2 text-gray-950 dark:text-white">{{ $typeLabel($row->challenge_type) }}</td>
                                <td class="py-2 text-gray-500">{{ number_format($row->orders) }}</td>
                                <td class="py-2 text-right font-medium text-gray-950 dark:text-white">{{ $money($row->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">By account size</x-slot>

            @if ($report['bySize']->isEmpty())
                <p class="text-sm text-gray-500">No paid orders in this range.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="pb-2 font-medium">Size</th>
                            <th class="pb-2 font-medium">Orders</th>
                            <th class="pb-2 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($report['bySize'] as $row)
                            <tr>
                                <td class="py-2 text-gray-950 dark:text-white">${{ number_format((float) $row->account_size) }}</td>
                                <td class="py-2 text-gray-500">{{ number_format($row->orders) }}</td>
                                <td class="py-2 text-right font-medium text-gray-950 dark:text-white">{{ $money($row->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
