{{-- Faculty navigation --}}
<x-app-nav
    :home="route('faculty.dashboard')"
    subtitle="Faculty Portal"
    :links="[
        ['label' => 'Attendance', 'route' => 'faculty.dashboard'],
        ['label' => 'Subjects', 'route' => 'faculty.subjects', 'active' => 'faculty.subjects'],
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.*'],
    ]"
/>
