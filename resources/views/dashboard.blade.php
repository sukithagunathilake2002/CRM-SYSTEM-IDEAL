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

    $activeBookings = \App\Models\Booking::query()
        ->whereHas('enquiry', function ($query) use ($viewerId) {
            $query->where('user_id', $viewerId);
        })
        ->count();

    $leadsDone = \App\Models\Enquiry::query()
        ->where('user_id', $viewerId)
        ->whereRaw("LOWER(COALESCE(followup_status, '')) = ?", ['done'])
        ->count();

    $todaySriLanka = now('Asia/Colombo')->toDateString();
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

    $sriLankaHour = now('Asia/Colombo')->hour;
    if ($sriLankaHour >= 5 && $sriLankaHour < 12) {
        $greeting = 'Good Morning';
    } elseif ($sriLankaHour >= 12 && $sriLankaHour < 17) {
        $greeting = 'Good Afternoon';
    } else {
        $greeting = 'Good Evening';
    }
@endphp

<div class="crm-dashboard">
    <main class="crm-main">
        <header class="crm-header">
            <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
                <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="crm-brand-logo">
            </a>

            <label class="crm-search" for="dashboardSearch">
                <input id="dashboardSearch" type="search" placeholder="Search here">
            </label>

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
                    <p class="crm-greeting">{{ $greeting }},</p>
                    <h2 class="crm-title">{{ $displayName }}</h2>
                </div>

                <article class="crm-stats-card" aria-label="Performance summary">
                    <div class="crm-stats-list">
                        <div class="crm-stat-pill">
                            <span class="crm-stat-dot"></span>
                            <span>{{ $activeBookings }} Active Booking</span>
                        </div>
                        <div class="crm-stat-pill">
                            <span class="crm-stat-dot"></span>
                            <span>{{ $leadsDone }} Leads done</span>
                        </div>
                    </div>
                    <div class="crm-profile-mini">
                        <span class="crm-avatar">{{ $initials }}</span>
                        <p>Hello {{ $firstName }}..</p>
                    </div>
                </article>
            </div>

            <div class="crm-action-grid">
    <a href="{{ route('enquiries.list.call') }}" class="crm-action-card">
        <span class="crm-action-badge">
            <img src="{{ asset('icons/Call.png') }}" alt="Call">
        </span>
        <h3>CALL</h3>
        <p>Log and track customer calls</p>
    </a>

    <a href="{{ route('enquiries.list.showroom') }}" class="crm-action-card">
        <span class="crm-action-badge">
            <img src="{{ asset('icons/showroom.png') }}" alt="Showroom Visit">
        </span>
        <h3>SHOWROOM VISIT</h3>
        <p>Track customer showroom visits</p>
    </a>

    <a href="{{ route('enquiries.list.home') }}" class="crm-action-card">
        <span class="crm-action-badge">
            <img src="{{ asset('icons/home123.png') }}" alt="Home Visit">
        </span>
        <h3>HOME VISIT</h3>
        <p>Manage customer home visits</p>
    </a>

    <a href="{{ route('enquiries.list') }}" class="crm-action-card">
        <span class="crm-action-badge">
            <img src="{{ asset('icons/epr.png') }}" alt="EPR">
        </span>
        <h3>EPR</h3>
        <p>Manage customer inquiries and records</p>
    </a>
</div>

            <div class="crm-cta-row">
                <a href="{{ route('emi.calculator') }}" class="crm-cta">EMI Calculator</a>
                <a href="{{ url('/new-enquiry') }}" class="crm-cta">Add new EPR</a>
            </div>
        </section>
    </main>
</div>
@endsection
