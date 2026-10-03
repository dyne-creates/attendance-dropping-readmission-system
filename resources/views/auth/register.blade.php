<x-guest-layout>

    <div class="w-full max-w-lg">

        <!-- Title -->
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-600 text-2xl font-bold text-white shadow-sm">
                A
            </div>

            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Create an Account
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                Register for the Attendance, Dropping, and Readmission System
            </p>
        </div>

        <!-- Registration Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    Registration
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Select your account type and use your official University of Baguio email.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div>
                    <x-input-label
                        for="name"
                        :value="__('Full Name')"
                    />

                    <x-text-input
                        id="name"
                        class="mt-2 block w-full"
                        type="text"
                        name="name"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your full name"
                    />

                    <x-input-error
                        :messages="$errors->get('name')"
                        class="mt-2"
                    />
                </div>

                <!-- Role -->
                <div class="mt-5">
                    <x-input-label
                        for="role"
                        :value="__('Account Type')"
                    />

                    <select
                        id="role"
                        name="role"
                        required
                        class="mt-2 block w-full rounded-lg border-gray-300 bg-white py-3 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                    >
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>
                            Select your account type
                        </option>

                        <option
                            value="student"
                            {{ old('role') === 'student' ? 'selected' : '' }}
                        >
                            Student
                        </option>

                        <option
                            value="faculty"
                            {{ old('role') === 'faculty' ? 'selected' : '' }}
                        >
                            Faculty
                        </option>

                        <option
                            value="osa_staff"
                            {{ old('role') === 'osa_staff' ? 'selected' : '' }}
                        >
                            OSA Staff
                        </option>
                    </select>

                    <p class="mt-2 text-xs text-gray-500">
                        Student accounts use @s.ubaguio.edu.
                        Faculty and OSA Staff accounts use @e.ubaguio.edu.
                    </p>

                    <x-input-error
                        :messages="$errors->get('role')"
                        class="mt-2"
                    />
                </div>

                <!-- Email -->
                <div class="mt-5">
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
                    <x-input-label
                        for="password"
                        :value="__('Password')"
                    />

                    <x-text-input
                        id="password"
                        class="mt-2 block w-full"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password"
                    />

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="mt-2"
                    />
                </div>

                <!-- Confirm Password -->
                <div class="mt-5">
                    <x-input-label
                        for="password_confirmation"
                        :value="__('Confirm Password')"
                    />

                    <x-text-input
                        id="password_confirmation"
                        class="mt-2 block w-full"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                    />

                    <x-input-error
                        :messages="$errors->get('password_confirmation')"
                        class="mt-2"
                    />
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="mt-6 flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Create Account
                </button>

            </form>

        </div>

        <!-- Account Information -->
        <div class="mt-6 rounded-xl border border-red-100 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-800">
                University Email Requirement
            </p>

            <ul class="mt-2 space-y-1 text-xs text-red-700">
                <li>• Students: @s.ubaguio.edu</li>
                <li>• Faculty: @e.ubaguio.edu</li>
                <li>• OSA Staff: @e.ubaguio.edu</li>
            </ul>
        </div>

        <!-- Mobile Login -->
        <div class="mt-6 text-center sm:hidden">
            <span class="text-sm text-gray-500">
                Already have an account?
            </span>

            <a
                href="{{ route('login') }}"
                class="font-semibold text-red-600 hover:underline"
            >
                Login
            </a>
        </div>

    </div>

</x-guest-layout>