<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ideal Motors CRM</title>
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
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="@yield('bodyClass')">
    <div class="portal-shell">
        <header class="portal-topbar">
            <a href="{{ route('dashboard.main') }}" class="portal-brand">IDEAL MOTORS CRM</a>
            <div class="portal-topbar-right">
                @auth
                    @unless(request()->routeIs('login'))
                    <div class="portal-user">
                        <span>{{ auth()->user()->name }} ({{ auth()->user()->role_label }})</span>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit">Logout</button>
                        </form>
                    </div>
                    <a href="{{ route('dashboard.main') }}" class="portal-icon-btn" aria-label="Open main dashboard" title="Dashboard">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <rect x="4" y="4" width="7" height="7" rx="1.5"></rect>
                            <rect x="13" y="4" width="7" height="7" rx="1.5"></rect>
                            <rect x="4" y="13" width="7" height="7" rx="1.5"></rect>
                            <rect x="13" y="13" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </a>
                    @endunless
                @endauth
                <button type="button" id="themeToggle" class="theme-toggle-btn theme-toggle-icon" aria-label="Toggle dark mode" aria-pressed="false"></button>
            </div>
        </header>

        <main class="portal-main">
            @php
                $isLoginRoute = request()->routeIs('login') || request()->routeIs('auth.login.form');
            @endphp

            @if(session('success') && !$isLoginRoute)
                <div class="portal-flash success">{{ session('success') }}</div>
            @endif

            @if($errors->any() && !$isLoginRoute)
                <div class="portal-flash error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        (() => {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) {
                return;
            }

            const root = document.documentElement;
            const moonIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.5 2.5a9.5 9.5 0 1 0 6 17.2 8 8 0 1 1-6-17.2Z" fill="currentColor"/></svg>';
            const sunIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="currentColor"/><path d="M12 1.5v3M12 19.5v3M22.5 12h-3M4.5 12h-3M19.4 4.6l-2.1 2.1M6.7 17.3l-2.1 2.1M19.4 19.4l-2.1-2.1M6.7 6.7L4.6 4.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/></svg>';

            const updateLabel = () => {
                const isDark = root.classList.contains('theme-dark');
                toggle.innerHTML = isDark ? sunIcon : moonIcon;
                toggle.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
                toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
                toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            };

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
        })();

        (() => {
            const targets = Array.from(document.querySelectorAll('.stat strong, .analytics-kpi strong'));
            if (!targets.length) {
                return;
            }

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) {
                return;
            }

            const formatNumber = (value) => new Intl.NumberFormat('en-US').format(Math.round(value));
            const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

            targets.forEach((el, index) => {
                const raw = String(el.textContent || '').replace(/,/g, '').trim();
                const target = Number(raw);
                if (!Number.isFinite(target)) {
                    return;
                }

                el.textContent = '0';

                const duration = 900 + (index * 80);
                const start = performance.now();

                const tick = (now) => {
                    const elapsed = now - start;
                    const progress = Math.min(1, elapsed / duration);
                    const current = target * easeOutCubic(progress);
                    el.textContent = formatNumber(current);

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            });
        })();

        (() => {
            const notices = Array.from(document.querySelectorAll('.auto-dismiss[data-auto-dismiss]'));
            if (!notices.length) {
                return;
            }

            notices.forEach((notice) => {
                const delay = Number.parseInt(notice.getAttribute('data-auto-dismiss') || '10000', 10);
                const timeout = Number.isFinite(delay) ? Math.max(delay, 0) : 10000;

                window.setTimeout(() => {
                    notice.classList.add('is-hidden');
                    window.setTimeout(() => notice.remove(), 350);
                }, timeout);
            });
        })();
    </script>
</body>
</html>
