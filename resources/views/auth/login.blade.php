<x-guest-layout heading="Log in" subheading="Use your account to continue.">
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <x-input-error class="mb-5" :messages="$errors->get('google')" />

    <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-input-label for="username" value="Username" />
                <x-text-input id="username" class="mt-1.5" type="text" name="username" :value="old('username')" required autofocus
                    autocomplete="username" :aria-invalid="$errors->has('username') ? 'true' : null" />
                <x-input-error class="mt-2" :messages="$errors->get('username')" />
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between">
                    <x-input-label for="password" value="Password" />
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link text-sm">Forgot password?</a>
                    @endif
                </div>
                <x-text-input id="password" class="mt-1.5" type="password" name="password" required
                    autocomplete="current-password" :aria-invalid="$errors->has('password') ? 'true' : null" />
                <x-input-error class="mt-2" :messages="$errors->get('password')" />
            </div>

            <div class="mt-5">
                <label for="remember_me" class="inline-flex items-center gap-2">
                    <input id="remember_me" type="checkbox" name="remember" class="check">
                    <span class="text-sm muted">Remember me</span>
                </label>
            </div>

            <x-primary-button class="mt-6 w-full">Log in</x-primary-button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs muted" aria-hidden="true">
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
        <span>Or continue with</span>
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
    </div>

    <a href="{{ route('google.redirect', ['intent' => 'login']) }}" class="btn btn-secondary w-full">
        <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true" focusable="false" fill="none" stroke="currentColor"
            stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="24" cy="24" r="20" />
            <line x1="4" y1="24" x2="44" y2="24" />
            <path d="M24 4 C33 12, 33 36, 24 44 C15 36, 15 12, 24 4 Z" />
            <line x1="24" y1="4" x2="24" y2="44" />
        </svg>
        <span>Sign in with Google</span>
    </a>

    <p class="mt-6 text-center text-sm muted">
        Don’t have an account yet?
        <a href="{{ route('register') }}" class="link font-semibold">Sign up with username or Google</a>
    </p>
</x-guest-layout>
