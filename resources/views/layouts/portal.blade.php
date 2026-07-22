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
    @php
        $portalUser = auth()->user();
        $isLoginRoute = request()->routeIs('login') || request()->routeIs('auth.login.form');
        $portalInitial = strtoupper(substr((string) ($portalUser?->name ?? 'U'), 0, 1));
        $portalSystemReminders = collect();
        if ($portalUser && !$isLoginRoute && \Illuminate\Support\Facades\Schema::hasTable('sales_consultant_reminders')) {
            $portalSystemReminders = \App\Models\SalesConsultantReminder::query()
                ->with('sender:id,name')
                ->where('recipient_id', $portalUser->id)
                ->whereNull('read_at')
                ->latest()
                ->limit(5)
                ->get();
        }
        $portalNotificationCount = $portalSystemReminders->count();
    @endphp
    <div class="portal-shell">
        <header class="portal-topbar">
            @auth
                @unless($isLoginRoute)
                    <button type="button" class="portal-menu-link" id="portalMenuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="portalSidebar" title="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                @endunless
            @endauth
            <a href="{{ route('dashboard.main') }}" class="portal-brand">IDEAL MOTORS CRM</a>
            <div class="portal-topbar-right">
                @auth
                    @unless($isLoginRoute)
                    <div class="portal-quick-icons" aria-label="Quick navigation">
                        <a href="{{ route('dashboard.main') }}" class="portal-quick-icon" aria-label="Open CRM dashboard overview" title="CRM Dashboard Overview">
                            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                <path d="M4 19h16" stroke-linecap="round"></path>
                                <path d="M7 18v-5" stroke-linecap="round"></path>
                                <path d="M12 18v-8" stroke-linecap="round"></path>
                                <path d="M17 18v-11" stroke-linecap="round"></path>
                                <path d="M6 11l4-3 3 2 5-5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </a>

                        <details class="portal-notifications">
                            <summary class="portal-quick-icon" aria-label="Open notifications" title="Notifications">
                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                    <path d="M15 18H5l1.2-1.6A2 2 0 0 0 6.6 15V11a5.4 5.4 0 0 1 10.8 0v4a2 2 0 0 0 .4 1.4L19 18h-4" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M10 20a2 2 0 0 0 4 0" stroke-linecap="round"></path>
                                </svg>
                                @if($portalNotificationCount > 0)
                                    <span class="portal-notify-badge">{{ $portalNotificationCount }}</span>
                                @endif
                            </summary>
                            <div class="portal-popover portal-notify-menu">
                                <p class="portal-popover-title">Notifications</p>
                                @forelse($portalSystemReminders as $reminder)
                                    <div class="portal-notify-item">
                                        <span>{{ $reminder->sender?->name ?? 'Manager' }} sent a reminder</span>
                                        <small>
                                            Registration {{ $reminder->pending_registration_count }},
                                            Follow Up {{ $reminder->pending_followup_count }},
                                            Booking {{ $reminder->pending_booking_count }},
                                            Delivery {{ $reminder->pending_delivery_count }}
                                        </small>
                                        <form method="POST" action="{{ route('dashboard.reminders.read', $reminder->id) }}">
                                            @csrf
                                            <button type="submit">Mark Read</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="portal-popover-empty">No notifications.</p>
                                @endforelse
                            </div>
                        </details>

                        <details class="portal-profile-menu-wrap">
                            <summary class="portal-quick-icon portal-profile-btn" aria-label="Open profile menu" title="Profile">
                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3.5"></circle>
                                    <path d="M5 19c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke-linecap="round"></path>
                                </svg>
                            </summary>
                            <div class="portal-popover portal-profile-menu">
                                <div class="portal-profile-summary">
                                    <span>{{ $portalInitial }}</span>
                                    <div>
                                        <p class="portal-popover-title">{{ $portalUser?->name ?? 'User' }}</p>
                                        <p class="portal-popover-detail">{{ $portalUser?->role_label ?? 'User' }}</p>
                                    </div>
                                </div>
                                <p class="portal-popover-detail">{{ $portalUser?->email ?? 'No email' }}</p>
                                @if(!empty($portalUser?->phone))
                                    <p class="portal-popover-detail">{{ $portalUser->phone }}</p>
                                @endif
                                <form method="POST" action="{{ route('auth.logout') }}" class="portal-profile-logout-form">
                                    @csrf
                                    <button type="submit" class="portal-profile-logout-btn">Logout</button>
                                </form>
                            </div>
                        </details>
                    </div>
                    @endunless
                @endauth
                <button type="button" id="themeToggle" class="theme-toggle-btn theme-toggle-icon" aria-label="Toggle dark mode" aria-pressed="false"></button>
            </div>
        </header>

        @auth
            @unless($isLoginRoute)
                <button type="button" class="portal-sidebar-overlay" id="portalSidebarOverlay" aria-label="Close menu"></button>
                <aside class="portal-sidebar" id="portalSidebar" aria-label="Dashboard menu">
                    <div class="portal-sidebar-profile portal-sidebar-profile-top">
                        <span>{{ strtoupper(substr((string) ($portalUser?->name ?? 'U'), 0, 1)) }}</span>
                        <div>
                            <strong>{{ $portalUser?->name ?? 'User' }}</strong>
                            <small>{{ $portalUser?->role_label ?? 'User' }}</small>
                        </div>
                    </div>

                    <div class="portal-sidebar-group-title">Leads and Bookings</div>
                    <nav class="portal-sidebar-nav">
                        <a href="{{ route('enquiries.list', ['lead_status' => 'hot']) }}">Hot Leads</a>
                        <a href="{{ route('enquiries.list', ['lead_status' => 'warm']) }}">Warm Leads</a>
                        <a href="{{ route('enquiries.list', ['lead_status' => 'cold']) }}">Cold Leads</a>
                        <a href="{{ route('enquiries.list', ['lead_result' => 'active']) }}">Active Lead</a>
                        <a href="{{ route('enquiries.list', ['lead_result' => 'lost']) }}">Lost Lead</a>
                        <a href="{{ route('enquiries.list', ['lead_result' => 'closed']) }}">Closed Lead</a>
                        <a href="{{ route('enquiries.list', ['registration' => 'pending']) }}">EPR</a>
                        <a href="{{ url('/epr') }}">Active Booking</a>
                        <a href="{{ url('/epr') }}">Inactive Booking</a>
                        <a href="{{ url('/epr') }}">Cancelled Booking</a>
                        <a href="{{ url('/epr') }}">Deliveries</a>
                        <a href="{{ route('enquiries.list') }}">All Leads</a>
                    </nav>

                    <hr class="portal-sidebar-sep">

                    <form method="POST" action="{{ route('auth.logout') }}" class="portal-sidebar-logout-form">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                </aside>
            @endunless
        @endauth

        <main class="portal-main">
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
            const root = document.querySelector('.portal-shell');
            const toggle = document.getElementById('portalMenuToggle');
            const overlay = document.getElementById('portalSidebarOverlay');
            const sidebar = document.getElementById('portalSidebar');
            if (!root || !toggle || !overlay || !sidebar) {
                return;
            }

            const setOpen = (open) => {
                root.classList.toggle('portal-sidebar-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            toggle.addEventListener('click', () => {
                setOpen(!root.classList.contains('portal-sidebar-open'));
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

        (() => {
            const toggles = Array.from(document.querySelectorAll('[data-password-toggle]'));
            if (!toggles.length) {
                return;
            }

            toggles.forEach((toggle) => {
                const targetId = toggle.getAttribute('data-password-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                toggle.addEventListener('click', () => {
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    toggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
                    toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                    toggle.setAttribute('title', shouldShow ? 'Hide password' : 'Show password');
                    input.focus({ preventScroll: true });
                });
            });
        })();
    </script>
</body>
</html>
