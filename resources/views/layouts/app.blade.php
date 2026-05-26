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
    @php
        $globalHeaderUser = auth()->user();
        $globalHeaderUserName = (string) ($globalHeaderUser?->name ?? 'User');
        $globalHeaderUserEmail = (string) ($globalHeaderUser?->email ?? 'No email');
        $globalHeaderUserRole = (string) ($globalHeaderUser?->role_label ?? 'User');
    @endphp

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
            const globalSearchTarget = @json(url('/epr'));
            const dashboardMainUrl = @json(route('dashboard.main'));
            const analyticsUrl = @json(route('dashboard.home'));
            const notificationUrl = @json(url('/epr'));
            const logoutUrl = @json(route('auth.logout'));
            const csrfToken = @json(csrf_token());
            const globalUserName = @json($globalHeaderUserName);
            const globalUserEmail = @json($globalHeaderUserEmail);
            const globalUserRole = @json($globalHeaderUserRole);

            const resolveUnifiedTopbar = () =>
                document.querySelector('.epr-topbar, .prospect-topbar, .emi-topbar, .topbar, .booking-topbar, .followup-topbar, .map-topbar');

            const ensureUnifiedSearch = (topbar) => {
                if (!topbar || topbar.querySelector('.global-header-search, .crm-search, .prospect-top-search, .search-box, .search-wrap')) {
                    return;
                }

                const searchWrap = document.createElement('label');
                searchWrap.className = 'global-header-search';
                searchWrap.setAttribute('for', 'globalHeaderSearchInput');
                searchWrap.innerHTML = '<input id="globalHeaderSearchInput" type="search" placeholder="Search here">';
                topbar.appendChild(searchWrap);
            };

            const createQuickIcons = () => {
                const existing = document.querySelector('.global-quick-icons');
                if (existing) {
                    return existing;
                }

                const quickIcons = document.createElement('div');
                quickIcons.className = 'global-quick-icons';
                quickIcons.innerHTML = `
                    <a href="${notificationUrl}" class="global-quick-icon" aria-label="Notifications" title="Notifications">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 4a4 4 0 0 0-4 4v2.6c0 .8-.2 1.6-.6 2.3L6 15h12l-1.4-2.1c-.4-.7-.6-1.5-.6-2.3V8a4 4 0 0 0-4-4Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M10 17a2 2 0 0 0 4 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="${dashboardMainUrl}" class="global-quick-icon" aria-label="Dashboard" title="Dashboard">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M3.5 11.5 12 4l8.5 7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6.5 10.5V20h11V10.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M10 20v-5h4v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="${analyticsUrl}" class="global-quick-icon" aria-label="Dashboard analytics" title="Dashboard analytics">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M4 18h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M7 16V11" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M12 16V8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M17 16V6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <details class="global-quick-profile">
                        <summary class="global-quick-icon" aria-label="Profile" title="Profile">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M6.8 18c1.1-2.3 3.2-3.5 5.2-3.5S16.1 15.7 17.2 18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </summary>
                        <div class="global-quick-menu">
                            <p class="global-quick-name">${globalUserName}</p>
                            <p class="global-quick-detail">${globalUserEmail}</p>
                            <p class="global-quick-detail">${globalUserRole}</p>
                            <form method="POST" action="${logoutUrl}" class="global-quick-logout-form">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" class="global-quick-logout-btn">Log out</button>
                            </form>
                        </div>
                    </details>
                `;

                return quickIcons;
            };

            const resolveIconHost = () => {
                const existingRightHost = document.querySelector('.top-icons-right:not(.crm-header-actions), .followup-top-actions, .map-topbar-actions');
                if (existingRightHost) {
                    return existingRightHost;
                }

                const topbar = resolveUnifiedTopbar();
                if (topbar) {
                    const rightHost = document.createElement('div');
                    rightHost.className = 'top-icons-right global-right-host';
                    topbar.appendChild(rightHost);
                    return rightHost;
                }

                return null;
            };

            const attachToHeader = () => {
                const dashboardHost = document.querySelector('.crm-header-actions');
                if (dashboardHost) {
                    if (!dashboardHost.contains(toggle)) {
                        dashboardHost.appendChild(toggle);
                    }
                    toggle.classList.add('theme-toggle-attached');
                    return;
                }

                const unifiedTopbar = resolveUnifiedTopbar();
                if (unifiedTopbar) {
                    unifiedTopbar.classList.add('global-unified-topbar');
                    ensureUnifiedSearch(unifiedTopbar);
                }

                const iconHost = resolveIconHost();
                if (!iconHost) {
                    toggle.classList.remove('theme-toggle-attached');
                    return;
                }

                const quickIcons = createQuickIcons();
                if (!iconHost.contains(quickIcons)) {
                    iconHost.appendChild(quickIcons);
                }

                if (!quickIcons.contains(toggle)) {
                    quickIcons.appendChild(toggle);
                }
                toggle.classList.add('theme-toggle-attached');
            };

            const bindSearchInput = (searchInput) => {
                if (!searchInput || searchInput.dataset.bound === '1') {
                    return;
                }

                searchInput.dataset.bound = '1';
                searchInput.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') {
                        return;
                    }
                    event.preventDefault();

                    const q = searchInput.value.trim();
                    const url = q ? `${globalSearchTarget}?q=${encodeURIComponent(q)}` : globalSearchTarget;
                    window.location.href = url;
                });
            };

            const bindGlobalSearch = () => {
                bindSearchInput(document.getElementById('globalHeaderSearchInput'));
                bindSearchInput(document.getElementById('dashboardSearch'));
                bindSearchInput(document.getElementById('prospectSearch'));
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
            bindGlobalSearch();

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
