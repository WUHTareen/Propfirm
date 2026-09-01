<x-marketing title="About" description="Who we are and how our funded-trader evaluation works.">
    <section class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="font-display text-4xl font-extrabold text-white">{{ $heading }}</h1>
        <div class="prose-invert mt-6 space-y-4 text-slate-300">
            @foreach (preg_split('/\n\s*\n/', trim($body)) as $para)
                <p class="leading-relaxed text-slate-400">{{ $para }}</p>
            @endforeach
        </div>

        @if (!empty($howItWorks))
            <div class="mt-12">
                <h2 class="font-display text-2xl font-bold text-white">How it works</h2>
                <ol class="mt-5 space-y-4">
                    @foreach ($howItWorks as $step)
                        <li class="flex gap-4">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-500/15 font-display text-sm font-bold text-brand-300">{{ $step['step'] }}</span>
                            <div>
                                <p class="font-medium text-white">{{ $step['title'] }}</p>
                                <p class="text-sm text-slate-400">{{ $step['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="mt-12 rounded-2xl border border-brand-500/30 bg-brand-500/5 p-8 text-center">
            <h2 class="font-display text-2xl font-bold text-white">Get funded today</h2>
            <a href="{{ route('register') }}" class="mt-4 inline-flex rounded-lg bg-brand-500 px-6 py-3 font-semibold text-ink-950 transition hover:bg-brand-400">Start a challenge</a>
        </div>
    </section>
</x-marketing>
