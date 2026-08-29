<x-guest-layout title="Reset password">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Forgot your password?</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">Enter your email and we'll send you a reset link.</p>

        <x-auth-errors />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username" class="field-input" placeholder="you@example.com">
            </div>

            <button type="submit" class="btn-primary">Email reset link</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" class="link-muted font-medium">Back to sign in</a>
        </p>
    </div>
</x-guest-layout>
