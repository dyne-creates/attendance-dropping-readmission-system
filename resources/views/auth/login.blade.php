<x-guest-layout>

    <div class="w-full max-w-md">

        <!-- Title -->
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-600 text-2xl font-bold text-white shadow-sm">
                A
            </div>

            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Welcome Back
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                Sign in to the Attendance, Dropping, and Readmission System
            </p>
        </div>

        <!-- Login Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    Login
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Use your University of Baguio account.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status
                class="mb-4"
                :status="session('status')"
            />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label
                        for="email"
                        :value="__('University Email')"
                    />

                    <x-text-input
                        id="email"
                        class="mt-2 block w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="yourname@s.ubaguio.edu"
                    />

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="mt-2"
                    />
                </div>

                <!-- Password -->
                <div class="mt-5">
                    <div class="flex items-center justify-between">
                        <x-input-label
                            for="password"
                            :value="__('Password')"
                        />

                        @if (Route::has('password.request'))
                            <a
                                href="{{ route('password.request') }}"
                                class="text-sm font-medium text-red-600 hover:text-red-700 hover:underline"
                            >
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <x-text-input
                        id="password"
                        class="mt-2 block w-full"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    />

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="mt-2"
                    />
                </div>

                <!-- Remember -->
                <div class="mt-5">
                    <label for="remember_me" class="inline-flex items-center">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                            name="remember"
                        >

                        <span class="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="mt-6 flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Sign In
                </button>
            </form>

        </div>

        <!-- Account Types -->
        <div class="mt-6 grid grid-cols-3 gap-3">

            <div class="rounded-xl border border-gray-200 bg-white p-3 text-center">
                <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    🎓
                </div>

                <p class="text-xs font-semibold text-gray-700">
                    Student
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-3 text-center">
                <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    👨‍🏫
                </div>

                <p class="text-xs font-semibold text-gray-700">
                    Faculty
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-3 text-center">
                <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    🏢
                </div>

                <p class="text-xs font-semibold text-gray-700">
                    OSA Staff
                </p>
            </div>

        </div>

        <!-- Mobile Register -->
        <div class="mt-6 text-center sm:hidden">
            <span class="text-sm text-gray-500">
                Don't have an account?
            </span>

            <a
                href="{{ route('register') }}"
                class="font-semibold text-red-600 hover:underline"
            >
                Register
            </a>
        </div>

    </div>

</x-guest-layout>