@php
    $pointsToDollars = fn ($p) => '$'.number_format($p / 100, 2);
    $rewardCards = [
        'video_review' => ['label' => 'Video review', 'points' => $videoPoints, 'desc' => 'Record a 30s video review (face visible) and share the link.', 'platform' => false],
        'social_media' => ['label' => 'Social media', 'points' => $socialPoints, 'desc' => 'Post a 30s video on Instagram/TikTok/Facebook and share the post link.', 'platform' => true],
    ];
@endphp

<x-dashboard title="Affiliation">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">Affiliation &amp; rewards</h1>
        <p class="mt-1 text-sm text-slate-400">Earn points, share them, and invite traders.</p>
    </x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Wallet + referral --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Points balance</p>
            <p class="mt-1 font-display text-2xl font-bold text-white tabular-nums">{{ number_format($user->points_balance) }}</p>
            <p class="mt-1 text-sm text-slate-500">= {{ $pointsToDollars($user->points_balance) }}</p>
        </div>
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-5 lg:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Your referral link</p>
            <div class="mt-2 flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-900 p-2.5">
                <code class="flex-1 break-all font-mono text-sm text-brand-300">{{ $referralUrl }}</code>
                <button type="button" class="shrink-0 rounded-md bg-brand-500 px-3 py-1.5 text-xs font-semibold text-ink-950 hover:bg-brand-400"
                        onclick="navigator.clipboard.writeText('{{ $referralUrl }}'); this.textContent='Copied'">Copy</button>
            </div>
            <div class="mt-4 grid grid-cols-4 gap-3 text-center">
                @foreach (['Clicks' => $affiliate->clicks, 'Signups' => $affiliate->signups, 'Conversions' => $affiliate->conversions, 'Commission' => '$'.number_format($affiliate->available_commission, 2)] as $label => $value)
                    <div class="rounded-lg bg-ink-900 p-3">
                        <p class="font-display text-lg font-bold text-white tabular-nums">{{ $value }}</p>
                        <p class="text-xs text-slate-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        {{-- Share points --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <h2 class="font-display text-base font-semibold text-white">Share points with a friend</h2>
            <p class="mt-1 mb-4 text-sm text-slate-400">Send points to another trader by their email.</p>
            <form method="POST" action="{{ route('dashboard.affiliation.share') }}" class="grid gap-3 sm:grid-cols-[1fr,8rem,auto] sm:items-end">
                @csrf
                <div>
                    <label for="recipient" class="field-label">Recipient email</label>
                    <input id="recipient" name="recipient" type="email" class="field-input" placeholder="friend@example.com" required>
                </div>
                <div>
                    <label for="points" class="field-label">Points</label>
                    <input id="points" name="points" type="number" min="1" class="field-input" placeholder="100" required>
                </div>
                <button type="submit" class="btn-primary sm:w-auto sm:px-5">Send</button>
            </form>
        </section>

        {{-- Ledger --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
            <h2 class="font-display text-base font-semibold text-white">Points activity</h2>
            @if ($ledger->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No points activity yet.</p>
            @else
                <div class="mt-3 divide-y divide-ink-700">
                    @foreach ($ledger as $row)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <p class="text-slate-200">{{ $row->description }}</p>
                                <p class="text-xs text-slate-500">{{ $row->created_at->format('d M Y') }} · {{ str_replace('_', ' ', $row->source) }}</p>
                            </div>
                            <span class="tabular-nums font-medium {{ $row->points >= 0 ? 'text-brand-400' : 'text-red-400' }}">{{ $row->points >= 0 ? '+' : '' }}{{ number_format($row->points) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Reward submissions --}}
    <section class="mt-4 rounded-2xl border border-ink-600 bg-ink-800 p-5">
        <h2 class="font-display text-base font-semibold text-white">Earn reward points</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($rewardCards as $type => $card)
                @php $existing = $submissions[$type] ?? null; @endphp
                <div class="rounded-xl border border-ink-700 bg-ink-900 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-slate-100">{{ $card['label'] }}</h3>
                        <span class="rounded-full bg-brand-500/15 px-2.5 py-0.5 font-mono text-xs text-brand-300">+{{ $card['points'] }} pts</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-400">{{ $card['desc'] }}</p>

                    @if ($existing)
                        <div class="mt-3 rounded-lg bg-ink-800 px-3 py-2 text-sm">
                            <span class="text-slate-400">Status:</span>
                            <span class="font-medium {{ $existing->status === 'approved' ? 'text-brand-300' : ($existing->status === 'rejected' ? 'text-red-400' : 'text-amber-300') }}">{{ ucfirst($existing->status) }}</span>
                            @if ($existing->status === 'rejected' && $existing->remarks)
                                <p class="mt-1 text-xs text-red-400">{{ $existing->remarks }}</p>
                            @endif
                        </div>
                    @else
                        <form method="POST" action="{{ route('dashboard.affiliation.reward') }}" class="mt-3 space-y-2">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            @if ($card['platform'])
                                <select name="platform" class="field-input" required>
                                    <option value="instagram">Instagram</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="facebook">Facebook</option>
                                </select>
                            @endif
                            <input name="link" type="url" class="field-input" placeholder="https://link-to-your-video" required>
                            <button type="submit" class="btn-primary">Submit for review</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</x-dashboard>
