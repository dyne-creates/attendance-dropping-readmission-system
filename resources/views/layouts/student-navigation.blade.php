{{-- Student navigation --}}
<x-app-nav
    :home="route('student.dashboard')"
    subtitle="Student Portal"
    :links="[
        ['label' => 'Dashboard', 'route' => 'student.dashboard'],
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.*'],
    ]"
/>
