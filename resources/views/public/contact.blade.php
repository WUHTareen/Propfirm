<x-marketing title="Contact" description="Get in touch with our support team.">
    <section class="mx-auto max-w-4xl px-6 py-16">
        <div class="text-center">
            <h1 class="font-display text-4xl font-extrabold text-white">Contact us</h1>
            <p class="mt-3 text-slate-400">We usually reply within a few hours.</p>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1fr,1.4fr]">
            {{-- Contact channels --}}
            <div class="space-y-4">
                <a href="mailto:{{ $email }}" class="flex items-center gap-3 rounded-2xl border border-ink-700 bg-ink-900 p-5 transition hover:border-brand-500/50">
                    <svg class="h-6 w-6 text-brand-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    <div>
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="text-sm font-medium text-white">{{ $email }}</p>
                    </div>
                </a>
                @if ($telegram)
                    <a href="{{ $telegram }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-ink-700 bg-ink-900 p-5 transition hover:border-brand-500/50">
                        <svg class="h-6 w-6 text-brand-300" fill="currentColor" viewBox="0 0 24 24"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        <div>
                            <p class="text-xs text-slate-500">Telegram</p>
                            <p class="text-sm font-medium text-white">Message us</p>
                        </div>
                    </a>
                @endif
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-2xl border border-ink-700 bg-ink-900 p-5 transition hover:border-brand-500/50">
                        <svg class="h-6 w-6 text-brand-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.5A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.9.9-2.8-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-1.9-1.2 7.3 7.3 0 0 1-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.4l-.8-1.9c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3A2.8 2.8 0 0 0 7 8.9a4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c2 .8 2 .5 2.4.5a2.4 2.4 0 0 0 1.6-1.1 2 2 0 0 0 .1-1.1c0-.1-.2-.1-.5-.2z"/></svg>
                        <div>
                            <p class="text-xs text-slate-500">WhatsApp</p>
                            <p class="text-sm font-medium text-white">Chat with us</p>
                        </div>
                    </a>
                @endif
                @if ($address)
                    <div class="rounded-2xl border border-ink-700 bg-ink-900 p-5">
                        <p class="text-xs text-slate-500">Address</p>
                        <p class="mt-1 text-sm text-slate-300">{{ $address }}</p>
                    </div>
                @endif
            </div>

            {{-- Message form --}}
            <div class="rounded-2xl border border-ink-700 bg-ink-900 p-6">
                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                        <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="field-label">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="field-input" required>
                        </div>
                        <div>
                            <label for="email" class="field-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="field-input" required>
                        </div>
                    </div>
                    <div>
                        <label for="subject" class="field-label">Subject <span class="text-slate-600">(optional)</span></label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="field-input">
                    </div>
                    <div>
                        <label for="message" class="field-label">Message</label>
                        <textarea id="message" name="message" rows="5" class="field-input" required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn-primary">Send message</button>
                </form>
            </div>
        </div>
    </section>
</x-marketing>
