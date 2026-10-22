<x-guest-layout heading="Sign up" subheading="Create your account with your University of Baguio Google account.">
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <x-input-error class="mb-5" :messages="$errors->get('google')" />

    <a href="{{ route('google.redirect', ['intent' => 'signup']) }}" class="btn btn-primary w-full justify-center">
        <span>Sign up with Google</span>
    </a>

    <p class="mt-6 text-center text-sm muted">
        Already have an account?
        <a href="{{ route('login') }}" class="link font-semibold">Log in</a>
    </p>
</x-guest-layout>
