@php
    $badge = fn (string $s) => match ($s) {
        'approved' => 'bg-brand-500/15 text-brand-300',
        'rejected' => 'bg-red-500/15 text-red-300',
        default => 'bg-amber-500/15 text-amber-300',
    };
    $typeLabels = [
        'id_card' => 'ID card', 'passport' => 'Passport', 'driver_license' => 'Driver license',
        'proof_of_address' => 'Proof of address', 'selfie' => 'Selfie with ID',
    ];
@endphp

<x-dashboard title="KYC">
    <x-slot:header>
        <h1 class="font-display text-2xl font-bold text-white">KYC verification</h1>
        <p class="mt-1 text-sm text-slate-400">Verify your identity to unlock payouts.</p>
    </x-slot:header>

    @if ($locked)
        <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-800/60 p-10 text-center">
            <div class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-xl bg-ink-700 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            </div>
            <h2 class="font-display text-lg font-semibold text-white">KYC is locked</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">You must first have a funded account before applying for KYC.</p>
        </div>
    @else
        @if (session('status'))
            <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[22rem,1fr] lg:items-start">
            {{-- Upload --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="font-display text-base font-semibold text-white">Upload a document</h2>
                <p class="mt-1 mb-4 text-sm text-slate-400">Clear photo or scan. JPG, PNG or PDF, up to 8&nbsp;MB.</p>
                <form method="POST" action="{{ route('dashboard.kyc.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="document_type" class="field-label">Document type</label>
                        <select id="document_type" name="document_type" class="field-input" required>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="document" class="field-label">File</label>
                        <input id="document" name="document" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                               class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:font-semibold file:text-ink-950 hover:file:bg-brand-400">
                    </div>
                    <button type="submit" class="btn-primary">Upload document</button>
                </form>
            </section>

            {{-- History --}}
            <section class="rounded-2xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="font-display text-base font-semibold text-white">Your documents</h2>
                @if ($documents->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">No documents uploaded yet.</p>
                @else
                    <div class="mt-4 divide-y divide-ink-700">
                        @foreach ($documents as $doc)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-200">{{ $typeLabels[$doc->document_type] ?? $doc->document_type }}</p>
                                    <p class="text-xs text-slate-500">{{ $doc->created_at->format('d M Y') }}</p>
                                    @if ($doc->status === 'rejected' && $doc->remarks)
                                        <p class="mt-1 text-xs text-red-400">Reason: {{ $doc->remarks }}</p>
                                    @endif
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($doc->status) }}">{{ ucfirst($doc->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    @endif
</x-dashboard>
