<button type="button" data-theme-toggle {{ $attributes->merge(['class' => 'btn btn-secondary btn-sm flex items-center justify-center']) }}>
    <!-- Moon Icon: Shown by default, hidden when <html> has the .dark class -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 dark:hidden">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
    </svg>

    <!-- Sun Icon: Hidden by default, shown when <html> has the .dark class -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden w-5 h-5 dark:block">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21M4.22 4.22l1.56 1.56m12.44 12.44l1.56 1.56M21 12h-2.25m-13.5 0H3m16.52 7.78l-1.56-1.56M6.78 6.78l-1.56-1.56M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" />
    </svg>
</button>

@once
    <script>
        (function () {
            var root = document.documentElement;

            function apply(dark) {
                root.classList.toggle('dark', dark);
                root.style.colorScheme = dark ? 'dark' : 'light';
            }

            document.addEventListener('click', function (event) {
                if (! event.target.closest('[data-theme-toggle]')) {
                    return;
                }

                var dark = ! root.classList.contains('dark');

                try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}

                apply(dark);
            });

            // Follow the system setting only while the user has not chosen a theme themselves.
            try {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (event) {
                    if (! localStorage.getItem('theme')) {
                        apply(event.matches);
                    }
                });
            } catch (e) {}
        })();
    </script>
@endonce
