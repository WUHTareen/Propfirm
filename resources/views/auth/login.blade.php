<x-guest-layout title="Sign in">
    <div class="auth-card">
        <h1 class="font-display text-2xl font-bold text-white">Welcome back</h1>
        <p class="mt-1 mb-6 text-sm text-slate-400">Sign in to your trader dashboard.</p>

        <x-auth-errors />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username" class="field-input" placeholder="you@example.com">
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="field-label">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs link-muted">Forgot password?</a>
                </div>
                <input id="password" name="password" type="password"
                       required autocomplete="current-password" class="field-input" placeholder="••••••••">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500">
                Remember me
            </label>

            <button type="submit" class="btn-primary">Sign in</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            New here? <a href="{{ route('register') }}" class="link-muted font-medium">Create an account</a>
        </p>
    </div>
</x-guest-layout>
