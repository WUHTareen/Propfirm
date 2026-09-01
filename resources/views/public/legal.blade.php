<x-marketing :title="$title" :description="$title">
    <section class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="font-display text-3xl font-extrabold text-white sm:text-4xl">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('F Y') }}</p>

        <div class="mt-8 space-y-4">
            @forelse (preg_split('/\n\s*\n/', trim($body)) as $para)
                <p class="leading-relaxed text-slate-400">{{ $para }}</p>
            @empty
                <p class="text-slate-500">This document has not been published yet.</p>
            @endforelse
        </div>

        @if (str_contains($body, '[Replace'))
            <div class="mt-10 rounded-xl border border-amber-500/30 bg-amber-500/5 px-5 py-4 text-sm text-amber-200/90">
                This is placeholder legal text. Replace it with your firm's reviewed policy before going live.
            </div>
        @endif
    </section>
</x-marketing>
