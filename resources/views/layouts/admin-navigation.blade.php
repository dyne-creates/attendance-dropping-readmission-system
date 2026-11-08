<x-app-nav
    :home="route('admin.osa-accounts.index')"
    subtitle="Administration"
    :links="[
        ['label' => 'OSA Accounts', 'route' => 'admin.osa-accounts.index', 'active' => 'admin.osa-accounts*'],
    ]"
/>
