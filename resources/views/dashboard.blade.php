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

    $activeBookings = \App\Models\Booking::query()->count();
    $tasksDone = \App\Models\Enquiry::query()
        ->whereRaw("LOWER(COALESCE(followup_status, '')) = ?", ['done'])
        ->count();
@endphp

<div class="crm-dashboard">
    <aside class="crm-sidebar" aria-label="Quick navigation">
        <a href="{{ route('dashboard.main') }}" class="crm-side-btn active" aria-label="Dashboard">
            <span class="crm-side-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <rect x="4" y="4" width="7" height="7" rx="1.5"></rect>
                    <rect x="13" y="4" width="7" height="7" rx="1.5"></rect>
                    <rect x="4" y="13" width="7" height="7" rx="1.5"></rect>
                    <rect x="13" y="13" width="7" height="7" rx="1.5"></rect>
                </svg>
            </span>
        </a>
        <a href="{{ url('/epr') }}" class="crm-side-btn" aria-label="EPR list">
            <span class="crm-side-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"></path>
                </svg>
            </span>
        </a>
        <a href="{{ route('dashboard.home') }}" class="crm-side-btn" aria-label="Role dashboard">
            <span class="crm-side-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M19 12a7 7 0 1 1-2.05-4.95" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M19 5v4h-4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
        </a>
    </aside>

    <main class="crm-main">
        <header class="crm-header">
            <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
                <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="crm-brand-logo">
            </a>

            <label class="crm-search" for="dashboardSearch">
                <span class="crm-search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <circle cx="11" cy="11" r="6"></circle>
                        <path d="M16 16l4 4" stroke-linecap="round"></path>
                    </svg>
                </span>
                <input id="dashboardSearch" type="search" placeholder="Search here">
            </label>

            <button type="button" class="crm-alert-btn" aria-label="Notifications">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M15 18H5l1.2-1.6A2 2 0 0 0 6.6 15V11a5.4 5.4 0 0 1 10.8 0v4a2 2 0 0 0 .4 1.4L19 18h-4" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M10 20a2 2 0 0 0 4 0" stroke-linecap="round"></path>
                </svg>
            </button>
        </header>

        <section class="crm-shell">
            <div class="crm-perf-top">
                <div>
                    <p class="crm-greeting">Good Morning,</p>
                    <h2 class="crm-title">System Performance</h2>
                </div>

                <article class="crm-stats-card" aria-label="Performance summary">
                    <div class="crm-stats-list">
                        <div class="crm-stat-pill">
                            <span class="crm-stat-dot"></span>
                            <span>{{ $activeBookings }} Active Booking</span>
                        </div>
                        <div class="crm-stat-pill">
                            <span class="crm-stat-dot"></span>
                            <span>{{ $tasksDone }} Tasks done</span>
                        </div>
                    </div>
                    <div class="crm-profile-mini">
                        <span class="crm-avatar">{{ $initials }}</span>
                        <p>Hello {{ $firstName }}..</p>
                    </div>
                </article>
            </div>

            <div class="crm-action-grid">
                <a href="{{ url('/epr') }}" class="crm-action-card">
                    <span class="crm-action-badge">
                        <img src="{{ asset('icons/Call.png') }}" alt="Call">
                    </span>
                    <h3>CALL</h3>
                    <p>Log and track customer calls</p>
                </a>

                <a href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}" class="crm-action-card">
                    <span class="crm-action-badge">
                        <img src="{{ asset('icons/showroom.png') }}" alt="Showroom Visit">
                    </span>
                    <h3>SHOWROOM VISIT</h3>
                    <p>Track customer showroom visits</p>
                </a>

                <a href="{{ url('/epr') }}" class="crm-action-card">
                    <span class="crm-action-badge">
                        <img src="{{ asset('icons/home123.png') }}" alt="Home Visit">
                    </span>
                    <h3>HOME VISIT</h3>
                    <p>Manage customer home visits</p>
                </a>

                <a href="{{ url('/epr') }}" class="crm-action-card">
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
