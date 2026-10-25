{{-- OSA staff navigation --}}
<x-app-nav
    :home="route('osa.dashboard')"
    subtitle="OSA Management"
    :links="[
        ['label' => 'Dashboard',            'route' => 'osa.dashboard'],
        ['label' => 'Readmissions',         'route' => 'osa.readmissions',         'active' => 'osa.readmissions*'],
        ['label' => 'Drop Records',         'route' => 'osa.archive.drops'],
        ['label' => 'Readmission Archive',  'route' => 'osa.archive.readmissions'],
    ]"
/>
