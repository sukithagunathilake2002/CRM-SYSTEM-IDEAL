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
        </header>

        <section class="crm-shell">
            <div class="crm-perf-top">
                <div>
                    <p class="crm-greeting">{{ $greeting }},</p>
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
