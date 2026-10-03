<x-guest-layout
    heading="Register"
    subheading="Register with your official University of Baguio email."
    width="max-w-lg"
>
    @php
        $field = 'mt-1.5 block w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:outline-none focus:ring-1 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500';
        $ok    = 'border-gray-300 focus:border-red-700 focus:ring-red-700 dark:border-gray-600 dark:focus:border-red-500 dark:focus:ring-red-500';
        $bad   = 'border-red-600 focus:border-red-700 focus:ring-red-700 dark:border-red-500 dark:focus:border-red-400 dark:focus:ring-red-400';
        $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
        $error = 'mt-2 text-sm text-red-700 dark:text-red-400';
    @endphp

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Full name -->
        <div>
            <label for="name" class="{{ $label }}">Full name</label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                maxlength="150"
                required
                autofocus
                autocomplete="name"
                class="{{ $field }} {{ $errors->has('name') ? $bad : $ok }}"
                @if ($errors->has('name')) aria-invalid="true" @endif
            >

            @if ($errors->has('name'))
                <p class="{{ $error }}" role="alert">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <!-- Account type -->
        <div class="mt-5">
            <label for="role" class="{{ $label }}">Account type</label>

            <select
                id="role"
                name="role"
                required
                class="{{ $field }} pr-10 {{ $errors->has('role') ? $bad : $ok }}"
                @if ($errors->has('role')) aria-invalid="true" @endif
            >
                <option value="" disabled class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100" @selected(! old('role'))>Select your account type</option>
                <option value="student" class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100" @selected(old('role') === 'student')>Student</option>
                <option value="faculty" class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100" @selected(old('role') === 'faculty')>Faculty</option>
                <option value="osa_staff" class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100" @selected(old('role') === 'osa_staff')>OSA Staff</option>
            </select>

            @if ($errors->has('role'))
                <p class="{{ $error }}" role="alert">{{ $errors->first('role') }}</p>
            @endif
        </div>

        <!-- University email -->
        <div class="mt-5">
            <label for="email" class="{{ $label }}">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="{{ $field }} {{ $errors->has('email') ? $bad : $ok }}"
                @if ($errors->has('email')) aria-invalid="true" @endif
            >

            @if ($errors->has('email'))
                <p class="{{ $error }}" role="alert">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <!-- Password -->
        <div class="mt-5">
            <label for="password" class="{{ $label }}">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Create a password"
                class="{{ $field }} {{ $errors->has('password') ? $bad : $ok }}"
                @if ($errors->has('password')) aria-invalid="true" @endif
            >

            @if ($errors->has('password'))
                <p class="{{ $error }}" role="alert">{{ $errors->first('password') }}</p>
            @else
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">At least 8 characters.</p>
            @endif
        </div>

        <!-- Confirm password -->
        <div class="mt-5">
            <label for="password_confirmation" class="{{ $label }}">Confirm password</label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Re-enter your password"
                class="{{ $field }} {{ $errors->has('password_confirmation') ? $bad : $ok }}"
                @if ($errors->has('password_confirmation')) aria-invalid="true" @endif
            >

            @if ($errors->has('password_confirmation'))
                <p class="{{ $error }}" role="alert">{{ $errors->first('password_confirmation') }}</p>
            @endif
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="mt-6 flex w-full justify-center rounded-md bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-500 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-gray-800"
        >
            Create account
        </button>
    </form>

    <p class="mt-6 border-t border-gray-200 pt-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-red-700 hover:underline dark:text-red-400">Log in</a>
    </p>
</x-guest-layout>