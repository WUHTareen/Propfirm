<x-guest-layout title="Verify email">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Verify your email</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">
            Thanks for signing up! Please click the link we just emailed you to activate your account.
            Didn't get it? Request another below.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded-lg border border-brand-600/40 bg-brand-600/10 px-4 py-3 text-sm text-brand-300">
                A fresh verification link has been sent to your email address.
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary">Resend verification email</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-center text-sm text-slate-400 transition hover:text-slate-200">Log out</button>
            </form>
        </div>
    </div>
</x-guest-layout>
