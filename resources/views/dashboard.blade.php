@extends('layouts.app')

@section('content')
@php
$user = auth()->user();
$displayName = (string) ($user?->name ?? 'User');
$parts = preg_split('/\s+/', trim($displayName)) ?: [];
$firstName = $parts[0] ?? $displayName;
$initials = '';
foreach ($parts as $part) {
if ($part === '') {
continue;
}

$initials .= strtoupper(substr($part, 0, 1));
if (strlen($initials) >= 2) {
break;
}
}
$initials = $initials !== '' ? $initials : 'U';

$viewerId = (int) ($user?->id ?? 0);
    $visibleUserIds = collect([$viewerId])->filter()->values();
    $viewerRole = (string) ($user?->role ?? '');

    if ($viewerRole === \App\Models\User::ROLE_SUPER_ADMIN) {
        $visibleUserIds = \App\Models\User::query()->pluck('id');
    } elseif ($viewerRole === \App\Models\User::ROLE_HEAD_OF_SALES) {
        $regionalIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_REGIONAL_MANAGER)
            ->where('manager_id', $viewerId)
            ->pluck('id');
        $areaIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_AREA_MANAGER)
            ->whereIn('manager_id', $regionalIds)
            ->pluck('id');
        $consultantIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_SALES_CONSULTANT)
            ->whereIn('manager_id', $areaIds)
            ->pluck('id');

        $visibleUserIds = $visibleUserIds
            ->merge($regionalIds)
            ->merge($areaIds)
            ->merge($consultantIds)
            ->unique()
            ->values();
    } elseif ($viewerRole === \App\Models\User::ROLE_REGIONAL_MANAGER) {
        $areaIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_AREA_MANAGER)
            ->where('manager_id', $viewerId)
            ->pluck('id');
        $consultantIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_SALES_CONSULTANT)
            ->whereIn('manager_id', $areaIds)
            ->pluck('id');

        $visibleUserIds = $visibleUserIds
            ->merge($areaIds)
            ->merge($consultantIds)
            ->unique()
            ->values();
    } elseif ($viewerRole === \App\Models\User::ROLE_AREA_MANAGER) {
        $consultantIds = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_SALES_CONSULTANT)
            ->where('manager_id', $viewerId)
            ->pluck('id');

        $visibleUserIds = $visibleUserIds
            ->merge($consultantIds)
            ->unique()
            ->values();
    }

    $sriNow = now('Asia/Colombo');
    $todaySriLanka = $sriNow->toDateString();
    $todayStartUtc = $sriNow->copy()->startOfDay()->timezone('UTC');
    $todayEndUtc = $sriNow->copy()->endOfDay()->timezone('UTC');

    $activeBookings = \App\Models\Booking::query()
        ->whereHas('enquiry', function ($query) use ($visibleUserIds) {
            $query->whereIn('user_id', $visibleUserIds)
                ->whereRaw("LOWER(COALESCE(status, 'open')) NOT IN ('closed', 'cancelled', 'canceled', 'lost')");
        })
        ->count();

    $todayLeads = \App\Models\Enquiry::query()
        ->whereIn('user_id', $visibleUserIds)
        ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
        ->count();

    $todayFollowups = \App\Models\Enquiry::query()
        ->with(['customer:id,title,name'])
        ->select(['id', 'customer_id', 'follow_type', 'follow_date', 'follow_time', 'followup_status'])
->where('user_id', $viewerId)
->whereDate('follow_date', $todaySriLanka)
->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
    ->orderBy('follow_time')
    ->limit(8)
    ->get();
    $todayFollowupCount = $todayFollowups->count();
    $recentActivities = \App\Models\Enquiry::query()
    ->with(['customer:id,title,name'])
    ->select(['id', 'customer_id', 'updated_at'])
    ->where('user_id', $viewerId)
    ->latest('updated_at')
    ->limit(5)
    ->get();
    $todayLabel = now('Asia/Colombo')->format('M d, Y');
    $roleLabel = (string) ($user?->role_label ?? 'Sales Executive');
    $isSuperAdmin = (string) ($user?->role ?? '') === \App\Models\User::ROLE_SUPER_ADMIN;

    $sriLankaHour = now('Asia/Colombo')->hour;
    if ($sriLankaHour >= 5 && $sriLankaHour < 12) {
        $greeting='Good Morning' ;
        } elseif ($sriLankaHour>= 12 && $sriLankaHour < 17) {
            $greeting='Good Afternoon' ;
            } else {
            $greeting='Good Evening' ;
            }
            @endphp

            <div class="crm-dashboard">
            <div class="crm-layout-shell">
                <aside id="dashboardSidebar" class="crm-left-nav" aria-label="Dashboard navigation">
                    <div class="crm-left-group">
                        <p>Leads and Bookings</p>
                        <a href="{{ route('enquiries.list.call') }}">Hot Leads</a>
                        <a href="{{ route('enquiries.list.showroom') }}">Warm Leads</a>
                        <a href="{{ route('enquiries.list.home') }}">Cold Leads</a>
                        <a href="{{ url('/epr') }}">Active Bookings</a>
                        <a href="{{ url('/epr') }}">Lost Leads</a>
                    </div>

                    <div class="crm-left-group">
                        <p>Management</p>
                        <a href="{{ route('enquiries.list') }}">ERP</a>
                        <a href="{{ url('/epr') }}">Inactive Booking</a>
                        <a href="{{ url('/epr') }}">Canceled Booking</a>
                    </div>

                    <hr class="crm-left-sep">

                    <div class="crm-left-group">
                        <a href="{{ route('enquiries.list') }}">All Leads</a>
                    </div>

                    <hr class="crm-left-sep">

                    <div class="crm-left-profile">
                        <span class="crm-left-profile-avatar">{{ $initials }}</span>
                        <div>
                            <strong>{{ $displayName }}</strong>
                            <small>{{ $roleLabel }}</small>
                        </div>
                    </div>
                </aside>

                <button type="button" class="crm-sidebar-overlay" id="crmSidebarOverlay" aria-label="Close sidebar"></button>

                <main class="crm-main">
                    <header class="crm-header">
                        <button type="button" class="crm-menu-toggle" id="crmMenuToggle" aria-label="Toggle sidebar" aria-expanded="false" aria-controls="dashboardSidebar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>

                        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
                            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="crm-brand-logo">
                        </a>

                        <div class="top-icons-right crm-header-actions">
                            <details class="crm-notifications">
                                <summary class="crm-notify-btn" aria-label="Today's follow-up notifications">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M15 18H5l1.2-1.6A2 2 0 0 0 6.6 15V11a5.4 5.4 0 0 1 10.8 0v4a2 2 0 0 0 .4 1.4L19 18h-4" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M10 20a2 2 0 0 0 4 0" stroke-linecap="round"></path>
                                    </svg>
                                    @if($todayFollowupCount > 0)
                                    <span class="crm-notify-badge">{{ $todayFollowupCount }}</span>
                                    @endif
                                </summary>
                                <div class="crm-notify-menu">
                                    <p class="crm-notify-title">Today's Followups</p>
                                    @forelse($todayFollowups as $followup)
                                    @php
                                    $customerTitle = trim((string) ($followup->customer?->title ?? ''));
                                    $customerName = trim((string) ($followup->customer?->name ?? 'Customer'));
                                    $customerLabel = trim($customerTitle . ' ' . $customerName);
                                    $followupTime = $followup->follow_time ? substr((string) $followup->follow_time, 0, 5) : '--:--';
                                    $followupType = trim((string) ($followup->follow_type ?? 'Followup'));
                                    @endphp
                                    <a href="{{ route('followup.show', $followup->id) }}" class="crm-notify-item">
                                        <span>{{ $customerLabel !== '' ? $customerLabel : 'Customer' }}</span>
                                        <small>{{ $followupType }} at {{ $followupTime }}</small>
                                    </a>
                                    @empty
                                    <p class="crm-notify-empty">No followups due today.</p>
                                    @endforelse
                                </div>
                            </details>

                            <a href="{{ route('dashboard.home') }}" class="crm-analytics-nav" aria-label="Open analyzing dashboard" title="Analyzing Dashboard">
                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                    <path d="M4 19h16" stroke-linecap="round"></path>
                                    <path d="M7 18v-5" stroke-linecap="round"></path>
                                    <path d="M12 18v-8" stroke-linecap="round"></path>
                                    <path d="M17 18v-11" stroke-linecap="round"></path>
                                    <path d="M6 11l4-3 3 2 5-5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </a>

                            <details class="crm-profile-menu-wrap">
                                <summary class="crm-profile-btn" aria-label="Open profile menu">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <circle cx="12" cy="8" r="3.5"></circle>
                                        <path d="M5 19c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke-linecap="round"></path>
                                    </svg>
                                </summary>
                                <div class="crm-profile-menu">
                                    <p class="crm-profile-name">{{ $displayName }}</p>
                                    <p class="crm-profile-detail">{{ $user?->email ?? 'No email' }}</p>
                                    <p class="crm-profile-detail">{{ $user?->role_label ?? 'User' }}</p>
                                    @if(!empty($user?->phone))
                                    <p class="crm-profile-detail">{{ $user->phone }}</p>
                                    @endif

                                    <form method="POST" action="{{ route('auth.logout') }}" class="crm-logout-form">
                                        @csrf
                                        <button type="submit" class="crm-logout-btn">Log out</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </header>

                    <section class="crm-shell">
                        <div class="crm-perf-top">
                            <div>
                                <p class="crm-greeting">{{ $greeting }}, {{ $displayName }}!</p>
                                <p class="crm-subline">Here's what's happening with your CRM today.</p>
                            </div>

                            <article class="crm-stats-card" aria-label="Performance summary">
                                <div class="crm-stats-list">
                                    <div class="crm-stat-pill">
                                        <span class="crm-stat-dot"></span>
                                        <span>{{ $activeBookings }} Active Booking</span>
                                    </div>
                        <div class="crm-stat-pill">
                            <span class="crm-stat-dot"></span>
                            <span>{{ $todayLeads }} Today Leads</span>
                        </div>
                    </div>
                    <a href="{{ route('enquiries.map', ['date' => $todaySriLanka]) }}" class="crm-day-map-link" aria-label="Open day map">
                        <img src="{{ asset('icons/mapdsh.png') }}" alt="Day Map">
                    </a>
                </article>
                        </div>

            <div class="crm-overview-head">
                <h3>Dashboard overview</h3>
                <div class="crm-current-date-box">
                    <span class="crm-current-date-pill" aria-label="Current Date">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path d="M7 3v3M17 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M9 13h2M13 13h2M9 17h2M13 17h2" stroke-linecap="round"></path>
                        </svg>
                        <span>{{ $todayLabel }}</span>
                    </span>
                </div>
            </div>

                        <div class="crm-action-grid">
                            <a href="{{ route('enquiries.list.call') }}" class="crm-action-card">
                                <span class="crm-action-badge">
                                    <img src="{{ asset('icons/Call.png') }}" alt="Call">
                                </span>
                                <h3>Call</h3>
                            </a>

                            <a href="{{ route('enquiries.list.showroom') }}" class="crm-action-card">
                                <span class="crm-action-badge">
                                    <img src="{{ asset('icons/showroom.png') }}" alt="Showroom Visit">
                                </span>
                                <h3>Showroom Visits</h3>
                            </a>

                            <a href="{{ route('enquiries.list.home') }}" class="crm-action-card">
                                <span class="crm-action-badge">
                                    <img src="{{ asset('icons/home123.png') }}" alt="Home Visit">
                                </span>
                                <h3>Home Visits</h3>
                            </a>

                            <a href="{{ route('enquiries.list') }}" class="crm-action-card">
                                <span class="crm-action-badge">
                                    <img src="{{ asset('icons/epr.png') }}" alt="EPR">
                                </span>
                                <h3>EPR Records</h3>
                            </a>
                        </div>

                        <div class="crm-cta-row">
                            <a href="{{ route('emi.calculator') }}" class="crm-cta">EMI Calculator</a>
                            <a href="{{ url('/new-enquiry') }}" class="crm-cta">Add new EPR</a>
                        </div>

            <div class="crm-quick-head">Quick Actions</div>
            <div class="crm-quick-actions">
                <a href="{{ route('enquiries.list', ['lead_status' => 'hot']) }}" class="crm-quick-chip hot">
                    <img src="{{ asset('icons/hotadsh.png') }}" alt="Hot Leads">
                    <span>Hot Leads</span>
                </a>
                <a href="{{ route('enquiries.list', ['lead_status' => 'warm']) }}" class="crm-quick-chip warm">
                    <img src="{{ asset('icons/warmdsh.png') }}" alt="Warm Leads">
                    <span>Warm Leads</span>
                </a>
                <a href="{{ route('enquiries.list', ['lead_status' => 'cold']) }}" class="crm-quick-chip cold">
                    <img src="{{ asset('icons/colddsh.png') }}" alt="Cold Leads">
                    <span>Cold Leads</span>
                </a>
            </div>

                        @if(!$isSuperAdmin)
                        <div class="crm-lower-grid">
                            <section class="crm-lower-card">
                                <div class="crm-lower-head">
                                    <h4>Upcoming Tasks</h4>
                                    <a href="{{ url('/epr') }}">View All</a>
                                </div>
                                <div class="crm-lower-list">
                                    @forelse($todayFollowups as $followup)
                                    @php
                                    $taskTime = $followup->follow_time ? substr((string) $followup->follow_time, 0, 5) : '--:--';
                                    $taskName = trim((string) ($followup->customer?->name ?? 'Customer'));
                                    @endphp
                                    <div class="crm-lower-item">
                                        <span>{{ $followup->follow_type ?: 'Followup' }} - {{ $taskName }}</span>
                                        <small>{{ $taskTime }}</small>
                                    </div>
                                    @empty
                                    <div class="crm-lower-item">
                                        <span>No tasks for today</span>
                                        <small>--:--</small>
                                    </div>
                                    @endforelse
                                </div>
                            </section>

                            <section class="crm-lower-card">
                                <div class="crm-lower-head">
                                    <h4>Recent Activities</h4>
                                    <a href="{{ url('/epr') }}">View All</a>
                                </div>
                                <div class="crm-lower-list">
                                    @forelse($recentActivities as $activity)
                                    @php
                                    $activityName = trim((string) ($activity->customer?->name ?? 'Lead'));
                                    @endphp
                                    <div class="crm-lower-item">
                                        <span>{{ $activityName }} updated</span>
                                        <small>{{ $activity->updated_at?->timezone('Asia/Colombo')->format('h:i A') }}</small>
                                    </div>
                                    @empty
                                    <div class="crm-lower-item">
                                        <span>No recent activities</span>
                                        <small>--:--</small>
                                    </div>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                        @endif
                    </section>
                </main>
            </div>
            </div>

            <script>
                (function initDashboardSidebarToggle() {
                    const root = document.querySelector('.crm-dashboard');
                    const toggle = document.getElementById('crmMenuToggle');
                    const overlay = document.getElementById('crmSidebarOverlay');
                    const sidebar = document.getElementById('dashboardSidebar');
                    if (!root || !toggle || !overlay || !sidebar) return;

                    const setOpen = (open) => {
                        root.classList.toggle('sidebar-open', open);
                        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    };

                    toggle.addEventListener('click', () => {
                        setOpen(!root.classList.contains('sidebar-open'));
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
            </script>
            @endsection
