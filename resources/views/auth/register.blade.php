<x-guest-layout
    heading="Register"
    subheading="Register with your official University of Baguio email."
    width="max-w-lg"
>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Full name -->
        <div>
            <x-input-label for="name" value="Full name" />

            <x-text-input
                id="name"
                class="mt-1.5"
                type="text"
                name="name"
                :value="old('name')"
                maxlength="150"
                required
                autofocus
                autocomplete="name"
                placeholder="Enter your full name"
                :aria-invalid="$errors->has('name') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- Account type -->
        <div class="mt-5">
            <x-input-label for="role" value="Account type" />

            <select
                id="role"
                name="role"
                required
                class="field mt-1.5"
                @if ($errors->has('role')) aria-invalid="true" @endif
            >
                <option value="" disabled @selected(! old('role'))>Select your account type</option>
                <option value="student" @selected(old('role') === 'student')>Student</option>
                <option value="faculty" @selected(old('role') === 'faculty')>Faculty</option>
                <option value="osa_staff" @selected(old('role') === 'osa_staff')>OSA Staff</option>
            </select>

            <x-input-error class="mt-2" :messages="$errors->get('role')" />
        </div>

        <!-- Student number -->
        <div id="student-number-field" class="mt-5" @if (old('role') !== 'student') hidden @endif>
            <x-input-label for="student_number" value="Student number" />

            <x-text-input
                id="student_number"
                class="mt-1.5"
                type="text"
                name="student_number"
                :value="old('student_number')"
                maxlength="50"
                autocomplete="off"
                placeholder="Enter your student number"
                :aria-invalid="$errors->has('student_number') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('student_number')" />
        </div>

        <!-- University email -->
        <div class="mt-5">
            <x-input-label for="email" value="University email" />

            <x-text-input
                id="email"
                class="mt-1.5"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
                :aria-invalid="$errors->has('email') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" value="Password" />

            <x-text-input
                id="password"
                class="mt-1.5"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Create a password"
                :aria-invalid="$errors->has('password') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('password')" />

            @unless ($errors->has('password'))
                <p class="mt-2 text-xs muted">At least 8 characters.</p>
            @endunless
        </div>

        <!-- Confirm password -->
        <div class="mt-5">
            <x-input-label for="password_confirmation" value="Confirm password" />

            <x-text-input
                id="password_confirmation"
                class="mt-1.5"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Re-enter your password"
                :aria-invalid="$errors->has('password_confirmation') ? 'true' : null"
            />

            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>

        <!-- Submit -->
        <x-primary-button class="mt-6 w-full">
            Create account
        </x-primary-button>
    </form>

    <p class="mt-6 border-t pt-6 text-center text-sm muted">
        Already have an account?
        <a href="{{ route('login') }}" class="link">Log in</a>
    </p>

    <script>
        // Show the matching email domain as the placeholder for the selected account type.
        (function () {
            var role  = document.getElementById('role');
            var email = document.getElementById('email');
            var studentNumberField = document.getElementById('student-number-field');
            var studentNumber = document.getElementById('student_number');
            var hints = {
                student:   'idnumber@s.ubaguio.edu',
                faculty:   'username@e.ubaguio.edu',
                osa_staff: 'username@e.ubaguio.edu'
            };

            function sync() {
                email.placeholder = hints[role.value] || '';
                studentNumberField.hidden = role.value !== 'student';
                studentNumber.required = role.value === 'student';
            }

            role.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-guest-layout>
