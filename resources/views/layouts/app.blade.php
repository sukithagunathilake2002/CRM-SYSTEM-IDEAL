<!DOCTYPE html>
<html>
<head>
    <title>Ideal Motors CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (() => {
            try {
                if (localStorage.getItem('ideal_theme') === 'dark') {
                    document.documentElement.classList.add('theme-dark');
                }
            } catch (error) {
                // Ignore theme read errors.
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    @yield('content')

    <button type="button" id="themeToggle" class="theme-toggle-btn theme-toggle-icon" aria-label="Toggle dark mode" aria-pressed="false"></button>

    <script>
        (() => {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) {
                return;
            }

            const root = document.documentElement;
            const moonIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.5 2.5a9.5 9.5 0 1 0 6 17.2 8 8 0 1 1-6-17.2Z" fill="currentColor"/></svg>';
            const sunIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="currentColor"/><path d="M12 1.5v3M12 19.5v3M22.5 12h-3M4.5 12h-3M19.4 4.6l-2.1 2.1M6.7 17.3l-2.1 2.1M19.4 19.4l-2.1-2.1M6.7 6.7L4.6 4.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/></svg>';

            const attachToHeader = () => {
                const iconHost = document.querySelector('.epr-topbar .top-icons-right, .top-icons-right');
                if (iconHost) {
                    iconHost.appendChild(toggle);
                    toggle.classList.add('theme-toggle-attached');
                } else {
                    toggle.classList.remove('theme-toggle-attached');
                }
            };

            const updateLabel = () => {
                const isDark = root.classList.contains('theme-dark');
                toggle.innerHTML = isDark ? sunIcon : moonIcon;
                toggle.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
                toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
                toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            };

            attachToHeader();
            updateLabel();

            toggle.addEventListener('click', () => {
                const isDark = root.classList.toggle('theme-dark');
                try {
                    localStorage.setItem('ideal_theme', isDark ? 'dark' : 'light');
                } catch (error) {
                    // Ignore theme save errors.
                }
                updateLabel();
            });

            window.addEventListener('resize', attachToHeader);
        })();
    </script>
</body>
</html>
