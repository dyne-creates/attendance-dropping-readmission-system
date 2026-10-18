<x-app-layout>
    <x-slot name="header">
        <x-page-title title="Your profile" description="View and update your account details." />
    </x-slot>

    <div class="page space-y-6">
        <section class="card p-4 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="card p-4 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="card p-4 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>
