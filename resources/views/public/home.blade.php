@php
    use App\Models\Setting;
    $money = fn ($n) => $n >= 1000 && $n % 1000 === 0 ? '$'.($n / 1000).'K' : '$'.number_format($n);
    // Show the 2-step column on the homepage pricing preview when present.
    $previewType = array_key_first($types) ?? null;
@endphp

<x-marketing>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_60%_at_50%_0%,rgba(45,212,191,0.12),transparent)]"></div>
        <div class="relative mx-auto max-w-3xl px-6 py-24 text-center sm:py-32">
            <span class="inline-block rounded-full border border-ink-700 bg-ink-800/80 px-3 py-1 font-mono text-xs uppercase tracking-wide text-brand-300">
                {{ Setting::get('hero_badge', 'Trade. Pass. Get funded.') }}
            </span>
            <h1 class="mt-6 font-display text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-6xl" style="text-wrap: balance;">
                {{ Setting::get('hero_title', 'Prove your edge. Trade our capital.') }}
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-lg text-slate-400">
                {{ Setting::get('hero_subtitle', '') }}
            </p>
            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-6 py-3 font-semibold text-ink-950 transition hover:bg-brand-400">Start a challenge</a>
                <a href="{{ route('pricing') }}" class="rounded-lg border border-ink-600 px-6 py-3 font-medium text-slate-200 transition hover:bg-ink-800">View pricing</a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    @if (!empty($features))
        <section class="mx-auto max-w-6xl px-6 py-12">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $f)
                    <div class="rounded-2xl border border-ink-700 bg-ink-900 p-5">
                        <h3 class="font-display text-base font-semibold text-white">{{ $f['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-slate-400">{{ $f['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- How it works --}}
    @if (!empty($howItWorks))
        <section class="mx-auto max-w-6xl px-6 py-14">
            <div class="mb-8 text-center">
                <h2 class="font-display text-3xl font-bold text-white">How it works</h2>
                <p class="mt-2 text-slate-400">From checkout to funded in four steps.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($howItWorks as $step)
                    <div class="relative rounded-2xl border border-ink-700 bg-ink-900 p-6">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-500/15 font-display text-lg font-bold text-brand-300">{{ $step['step'] }}</span>
                        <h3 class="mt-4 font-display text-base font-semibold text-white">{{ $step['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-slate-400">{{ $step['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Pricing preview --}}
    @if ($previewType && !empty($plans['byType'][$previewType]))
        <section class="mx-auto max-w-6xl px-6 py-14">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-3xl font-bold text-white">{{ $types[$previewType] }} pricing</h2>
                    <p class="mt-2 text-slate-400">One-time fee per account size. All prices in USD.</p>
                </div>
                <a href="{{ route('pricing') }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">Compare all plans →</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans['byType'][$previewType] as $size => $plan)
                    <div class="flex flex-col rounded-2xl border border-ink-700 bg-ink-900 p-5">
                        <p class="font-display text-2xl font-bold text-white">{{ $money($size) }}</p>
                        <p class="text-xs text-slate-500">account size</p>
                        <p class="mt-4 font-display text-xl font-bold text-brand-300">${{ number_format($plan->price, 0) }}</p>
                        <ul class="mt-4 flex-1 space-y-1.5 text-sm text-slate-400">
                            <li>Profit target {{ rtrim(rtrim((string) $plan->phase1_target_percent, '0'), '.') }}%@if($plan->phase2_target_percent) / {{ rtrim(rtrim((string) $plan->phase2_target_percent, '0'), '.') }}%@endif</li>
                            <li>Daily loss {{ rtrim(rtrim((string) $plan->daily_drawdown_percent, '0'), '.') }}%</li>
                            <li>Max loss {{ rtrim(rtrim((string) $plan->max_drawdown_percent, '0'), '.') }}%</li>
                            <li>{{ $plan->profit_split_percent ? rtrim(rtrim((string) $plan->profit_split_percent, '0'), '.').'% profit split' : '' }}</li>
                        </ul>
                        <a href="{{ route('register') }}" class="mt-5 rounded-lg bg-brand-500 px-4 py-2.5 text-center text-sm font-semibold text-ink-950 transition hover:bg-brand-400">Get funded</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="mx-auto max-w-6xl px-6 py-14">
            <div class="mb-8 text-center">
                <h2 class="font-display text-3xl font-bold text-white">Trusted by traders</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonials as $t)
                    <figure class="rounded-2xl border border-ink-700 bg-ink-900 p-6">
                        <div class="flex gap-0.5 text-brand-400">
                            @for ($i = 0; $i < 5; $i++)
                                <svg class="h-4 w-4 {{ $i < $t->rating ? 'fill-current' : 'fill-ink-600' }}" viewBox="0 0 20 20"><path d="M10 15.27 16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/></svg>
                            @endfor
                        </div>
                        <blockquote class="mt-3 text-sm leading-relaxed text-slate-300">"{{ $t->body }}"</blockquote>
                        <figcaption class="mt-4 text-sm font-medium text-white">{{ $t->author_name }}@if($t->author_country)<span class="text-slate-500"> · {{ $t->author_country }}</span>@endif</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- FAQ preview --}}
    @if ($faqs->isNotEmpty())
        <section class="mx-auto max-w-3xl px-6 py-14">
            <div class="mb-8 text-center">
                <h2 class="font-display text-3xl font-bold text-white">Frequently asked</h2>
            </div>
            <div class="divide-y divide-ink-800 overflow-hidden rounded-2xl border border-ink-700 bg-ink-900">
                @foreach ($faqs as $faq)
                    <details class="group px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-medium text-slate-100">
                            {{ $faq->question }}
                            <svg class="h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </summary>
                        <p class="mt-2 text-sm text-slate-400">{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </div>
            <div class="mt-5 text-center">
                <a href="{{ route('faq') }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">See all FAQs →</a>
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-6 py-16">
        <div class="rounded-3xl border border-brand-500/30 bg-gradient-to-b from-brand-500/10 to-transparent p-10 text-center">
            <h2 class="font-display text-3xl font-bold text-white">Ready to get funded?</h2>
            <p class="mx-auto mt-3 max-w-lg text-slate-400">Buy a challenge, prove your edge, and start trading our capital.</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-6 py-3 font-semibold text-ink-950 transition hover:bg-brand-400">Start a challenge</a>
        </div>
    </section>
</x-marketing>
