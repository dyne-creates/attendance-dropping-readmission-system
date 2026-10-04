<x-guest-layout
    heading="Log in"
    subheading="Use your University of Baguio account to continue."
>
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" value="University email" />

            <x-text-input
                id="email"
                class="mt-1.5"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                :aria-invalid="$errors->has('email') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Password" />

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link text-sm">
                        Forgot password?
                    </a>
                @endif
            </div>

            <x-text-input
                id="password"
                class="mt-1.5"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                :aria-invalid="$errors->has('password') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <!-- Remember me -->
        <div class="mt-5">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember" class="check">
                <span class="text-sm muted">Remember me</span>
            </label>
        </div>

        <!-- Submit -->
        <x-primary-button class="mt-6 w-full">
            Log in
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 border-t pt-6 text-center text-sm muted">
            Don't have an account?
            <a href="{{ route('register') }}" class="link">Register</a>
        </p>
    @endif
</x-guest-layout>