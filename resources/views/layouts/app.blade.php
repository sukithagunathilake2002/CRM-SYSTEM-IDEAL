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
        $globalHeaderShowAnalyticsIcon = !in_array($globalHeaderUser?->role, [\App\Models\User::ROLE_AREA_MANAGER, \App\Models\User::ROLE_SALES_CONSULTANT], true);
    @endphp

    @yield('content')

    @auth
        <template id="globalNotificationsTemplate">
            @include('layouts.partials.notifications')
        </template>
    @endauth

    @auth
        <aside id="globalLeadSidebar" class="global-lead-sidebar" aria-label="Leads and Bookings menu" aria-hidden="true">
            <div class="crm-left-group">
                <p>Leads and Bookings</p>
                @include('layouts.partials.lead-sidebar-links')
            </div>
        </aside>
        <button type="button" id="globalLeadSidebarOverlay" class="global-lead-sidebar-overlay" aria-label="Close sidebar"></button>
    @endauth

    <style>
        .global-unified-topbar {
            position: relative !important;
            z-index: 1500 !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 12px !important;
            min-height: 64px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 6px 24px !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #000000 !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        .global-unified-topbar::before,
        .global-unified-topbar::after,
        .global-unified-topbar .top-icons-right::before,
        .global-unified-topbar .top-icons-right::after,
        .global-unified-topbar .followup-top-actions::before,
        .global-unified-topbar .followup-top-actions::after,
        .global-unified-topbar .map-topbar-actions::before,
        .global-unified-topbar .map-topbar-actions::after {
            content: none !important;
            display: none !important;
            box-shadow: none !important;
        }

        .global-unified-topbar .global-header-menu-link {
            grid-column: 1 !important;
            justify-self: start !important;
            cursor: pointer !important;
            padding: 0 !important;
        }

        .global-unified-topbar .brand-logo-link {
            grid-column: 2 !important;
            justify-self: center !important;
        }

        .global-unified-topbar .top-icons-right,
        .global-unified-topbar .followup-top-actions,
        .global-unified-topbar .map-topbar-actions,
        .global-unified-topbar .global-right-host {
            grid-column: 3 !important;
            justify-self: end !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 12px !important;
        }

        .global-lead-sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            bottom: 0;
            width: 230px;
            z-index: 1450;
            box-sizing: border-box;
            overflow-y: auto;
            background: #060606;
            color: #ffffff;
            border-right: 1px solid #1a1a1a;
            padding: 14px 12px;
            transform: translateX(-100%);
            transition: transform 180ms ease;
        }

        body.global-sidebar-open .global-lead-sidebar {
            transform: translateX(0);
        }

        .global-lead-sidebar-overlay {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1440;
            border: 0;
            background: rgba(2, 6, 23, 0.42);
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }

        body.global-sidebar-open .global-lead-sidebar-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        .global-lead-sidebar .crm-left-group {
            display: grid;
            gap: 6px;
        }

        .global-lead-sidebar .crm-left-group p {
            margin: 10px 0 4px;
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .global-lead-sidebar .crm-left-group a {
            color: #f3f4f6;
            text-decoration: none;
            font-size: 13px;
            border-radius: 8px;
            padding: 7px 8px;
            transition: background 160ms ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .global-lead-sidebar .crm-left-group a:hover {
            background: #171717;
        }
    </style>

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
            const logoutUrl = @json(route('auth.logout'));
            const csrfToken = @json(csrf_token());
            const globalUserName = @json($globalHeaderUserName);
            const globalUserEmail = @json($globalHeaderUserEmail);
            const globalUserRole = @json($globalHeaderUserRole);
            const showAnalyticsIcon = @json($globalHeaderShowAnalyticsIcon);

            const resolveUnifiedTopbar = () =>
                document.querySelector('.epr-topbar, .prospect-topbar, .emi-topbar, .topbar, .booking-topbar, .followup-topbar, .map-topbar');

            const createGlobalMenuLink = () => {
                const existing = document.querySelector('.global-header-menu-link');
                if (existing) {
                    return existing;
                }

                const menu = document.createElement('button');
                menu.type = 'button';
                menu.className = 'global-menu-link global-header-menu-link';
                menu.setAttribute('aria-label', 'Open menu');
                menu.setAttribute('title', 'Menu');
                menu.setAttribute('aria-controls', 'globalLeadSidebar');
                menu.setAttribute('aria-expanded', 'false');
                menu.innerHTML = '<span></span><span></span><span></span>';
                return menu;
            };

            const ensureLeftMenu = (topbar) => {
                if (!topbar || topbar.querySelector('.crm-menu-toggle, .global-header-menu-link')) {
                    return;
                }

                const logo = topbar.querySelector('.brand-logo-link, .portal-brand');
                topbar.insertBefore(createGlobalMenuLink(), logo || topbar.firstChild);
            };

            const createQuickIcons = () => {
                const existing = document.querySelector('.global-quick-icons');
                if (existing) {
                    return existing;
                }

                const notificationTemplate = document.getElementById('globalNotificationsTemplate');
                const notificationMarkup = notificationTemplate ? notificationTemplate.innerHTML : '';
                const quickIcons = document.createElement('div');
                quickIcons.className = 'global-quick-icons';
                const analyticsIconMarkup = showAnalyticsIcon ? `
                    <a href="${analyticsUrl}" class="global-quick-icon" aria-label="Dashboard analytics" title="Dashboard analytics">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M4 18h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M7 16V11" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M12 16V8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M17 16V6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </a>
                ` : '';
                quickIcons.innerHTML = `
                    <a href="${dashboardMainUrl}" class="global-quick-icon" aria-label="Dashboard" title="Dashboard">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M3.5 11.5 12 4l8.5 7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6.5 10.5V20h11V10.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M10 20v-5h4v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    ${analyticsIconMarkup}
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
                    ${notificationMarkup}
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
                    ensureLeftMenu(unifiedTopbar);
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

            const bindGlobalSidebar = () => {
                const sidebar = document.getElementById('globalLeadSidebar');
                const overlay = document.getElementById('globalLeadSidebarOverlay');
                const menu = document.querySelector('.global-header-menu-link');

                if (!sidebar || !overlay || !menu || menu.dataset.sidebarBound === '1') {
                    return;
                }

                menu.dataset.sidebarBound = '1';

                const setOpen = (open) => {
                    document.body.classList.toggle('global-sidebar-open', open);
                    sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
                    menu.setAttribute('aria-expanded', open ? 'true' : 'false');
                };

                menu.addEventListener('click', () => {
                    setOpen(!document.body.classList.contains('global-sidebar-open'));
                });

                overlay.addEventListener('click', () => setOpen(false));

                sidebar.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => setOpen(false));
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        setOpen(false);
                    }
                });
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
            bindGlobalSidebar();

            toggle.addEventListener('click', () => {
                const isDark = root.classList.toggle('theme-dark');
                try {
                    localStorage.setItem('ideal_theme', isDark ? 'dark' : 'light');
                } catch (error) {
                    // Ignore theme save errors.
                }
                updateLabel();
            });

            window.addEventListener('resize', () => {
                attachToHeader();
                bindGlobalSidebar();
            });
        })();
    </script>
</body>
</html>
