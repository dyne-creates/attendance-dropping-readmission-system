<x-guest-layout heading="Verify your email" subheading="Check your University inbox to finish setting up your account.">
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <p class="text-sm muted">
        Your account is ready, but you need to verify your email address before using the app.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-primary-button class="w-full">Resend verification email</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-secondary w-full">Log out</button>
    </form>
</x-guest-layout>