{{-- Faculty navigation --}}
<x-app-nav
    :home="route('faculty.dashboard')"
    subtitle="Faculty Portal"
    :links="[
        ['label' => 'Dashboard', 'route' => 'faculty.dashboard'],
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.*'],
    ]"
/>
