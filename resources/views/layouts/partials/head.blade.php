<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title }}</title>

{{-- Apply the saved (or system) theme before first paint so the page never flashes the wrong colours. --}}
<script>
    (function () {
        var dark = false;

        try {
            var saved = localStorage.getItem('theme');
            dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        } catch (e) {}

        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    })();
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])