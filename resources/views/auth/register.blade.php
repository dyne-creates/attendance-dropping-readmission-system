<x-guest-layout heading="Sign up" subheading="Create an account with your University email, or continue with Google.">
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <x-input-error class="mb-5" :messages="$errors->get('google')" />

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Full name" />
            <x-text-input id="name" class="mt-1.5" type="text" name="name" :value="old('name')" required autocomplete="name"
                :aria-invalid="$errors->has('name') ? 'true' : null" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="mt-5">
            <x-input-label for="username" value="Username" />
            <x-text-input id="username" class="mt-1.5" type="text" name="username" :value="old('username')" required
                autocomplete="username" :aria-invalid="$errors->has('username') ? 'true' : null" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div class="mt-5">
            <x-input-label for="email" value="University email" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required
                autocomplete="email" :aria-invalid="$errors->has('email') ? 'true' : null" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-5">
            <x-input-label for="password" value="Password (at least 12 characters)" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="new-password"
                :aria-invalid="$errors->has('password') ? 'true' : null" />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div class="mt-5">
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" required
                autocomplete="new-password" />
        </div>

        <x-primary-button class="mt-6 w-full">Create account</x-primary-button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs muted" aria-hidden="true">
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
        <span>Or continue with</span>
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
    </div>

    <a href="{{ route('google.redirect', ['intent' => 'signup']) }}" class="btn btn-secondary w-full justify-center">
        <span>Sign up with Google</span>
    </a>

    <p class="mt-6 text-center text-sm muted">
        Already registered?
        <a href="{{ route('login') }}" class="link font-semibold">Log in</a>.
    </p>
</x-guest-layout>
