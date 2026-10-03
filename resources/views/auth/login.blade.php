<x-guest-layout
    heading="Log in"
    subheading="Use your University of Baguio account to continue."
>
    @php
        $field = 'mt-1.5 block w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:outline-none focus:ring-1 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500';
        $ok    = 'border-gray-300 focus:border-red-700 focus:ring-red-700 dark:border-gray-600 dark:focus:border-red-500 dark:focus:ring-red-500';
        $bad   = 'border-red-600 focus:border-red-700 focus:ring-red-700 dark:border-red-500 dark:focus:border-red-400 dark:focus:ring-red-400';
        $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
    @endphp

    @if (session('status'))
        <div
            class="mb-5 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-300"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="{{ $label }}">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="{{ $field }} {{ $errors->has('email') ? $bad : $ok }}"
                @if ($errors->has('email')) aria-invalid="true" @endif
            >

            @if ($errors->has('email'))
                <p class="mt-2 text-sm text-red-700 dark:text-red-400" role="alert">
                    {{ $errors->first('email') }}
                </p>
            @endif

        </div>

        <!-- Password -->
        <div class="mt-5">
            <div class="flex items-center justify-between">
                <label for="password" class="{{ $label }}">Password</label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-medium text-red-700 hover:underline dark:text-red-400"
                    >
                        Forgot password?
                    </a>
                @endif
            </div>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="{{ $field }} {{ $errors->has('password') ? $bad : $ok }}"
                @if ($errors->has('password')) aria-invalid="true" @endif
            >

            @if ($errors->has('password'))
                <p class="mt-2 text-sm text-red-700 dark:text-red-400" role="alert">
                    {{ $errors->first('password') }}
                </p>
            @endif
        </div>

        <!-- Remember me -->
        <div class="mt-5">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-red-700 accent-red-700 focus:ring-red-700 dark:border-gray-600 dark:bg-gray-900 dark:focus:ring-red-500 dark:focus:ring-offset-gray-800"
                >
                <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
            </label>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="mt-6 flex w-full justify-center rounded-md bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-500 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-gray-800"
        >
            Sign in
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 border-t border-gray-200 pt-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-red-700 hover:underline dark:text-red-400">Register</a>
        </p>
    @endif
</x-guest-layout>