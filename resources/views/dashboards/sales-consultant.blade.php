@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Sales Consultant Dashboard</h1>
    <p>Manage your leads, followups, and CRM workflow.</p>

    <div class="quick-links">
        <a class="btn-link alt" href="{{ route('enquiries.list') }}">Open EPR</a>
        <a class="btn-link alt" href="{{ url('/new-enquiry') }}">Create New Enquiry</a>
        <a class="btn-link alt" href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}">Open Day Map</a>
    </div>
</section>

<!-- Call EPR Section -->
<section class="card dashboard-epr-section">
    <div class="epr-section-header">
        <div class="epr-section-icon call-icon"></div>
        <h2 class="epr-section-title">Call EPR</h2>
        <span class="epr-count-badge">{{ $dashboardEpds['call_count'] ?? 0 }}</span>
        <a href="{{ route('enquiries.list.call') }}" class="epr-view-all-link">View All</a>
    </div>
    <div class="epr-list-compact">
        @forelse(($dashboardEpds['call_epds'] ?? []) as $epd)
            <div class="epr-compact-item">
                <div class="epr-compact-info">
                    <div class="epr-compact-name">{{ $epd->customer_name }}</div>
                    <div class="epr-compact-detail">{{ $epd->vehicle_name }} | {{ $epd->primary_phone }}</div>
                    @if($epd->follow_date)
                        <div class="epr-compact-date">Followup: {{ \Carbon\Carbon::parse($epd->follow_date)->format('d M Y') }} at {{ substr($epd->follow_time ?? '', 0, 5) }}</div>
                    @endif
                </div>
                <a href="{{ route('followup.show', $epd->id) }}" class="epr-compact-btn">View</a>
            </div>
        @empty
            <div class="epr-empty-state">No call EPRs available.</div>
        @endforelse
    </div>
</section>

<!-- Showroom Visit EPR Section -->
<section class="card dashboard-epr-section">
    <div class="epr-section-header">
        <div class="epr-section-icon showroom-icon"></div>
        <h2 class="epr-section-title">Showroom Visit EPR</h2>
        <span class="epr-count-badge">{{ $dashboardEpds['showroom_count'] ?? 0 }}</span>
        <a href="{{ route('enquiries.list.showroom') }}" class="epr-view-all-link">View All</a>
    </div>
    <div class="epr-list-compact">
        @forelse(($dashboardEpds['showroom_epds'] ?? []) as $epd)
            <div class="epr-compact-item">
                <div class="epr-compact-info">
                    <div class="epr-compact-name">{{ $epd->customer_name }}</div>
                    <div class="epr-compact-detail">{{ $epd->vehicle_name }} | {{ $epd->primary_phone }}</div>
                    @if($epd->follow_date)
                        <div class="epr-compact-date">Followup: {{ \Carbon\Carbon::parse($epd->follow_date)->format('d M Y') }} at {{ substr($epd->follow_time ?? '', 0, 5) }}</div>
                    @endif
                </div>
                <a href="{{ route('followup.show', $epd->id) }}" class="epr-compact-btn">View</a>
            </div>
        @empty
            <div class="epr-empty-state">No showroom visit EPRs available.</div>
        @endforelse
    </div>
</section>

<!-- Home Visit EPR Section -->
<section class="card dashboard-epr-section">
    <div class="epr-section-header">
        <div class="epr-section-icon home-icon"></div>
        <h2 class="epr-section-title">Home Visit EPR</h2>
        <span class="epr-count-badge">{{ $dashboardEpds['home_count'] ?? 0 }}</span>
        <a href="{{ route('enquiries.list.home') }}" class="epr-view-all-link">View All</a>
    </div>
    <div class="epr-list-compact">
        @forelse(($dashboardEpds['home_epds'] ?? []) as $epd)
            <div class="epr-compact-item">
                <div class="epr-compact-info">
                    <div class="epr-compact-name">{{ $epd->customer_name }}</div>
                    <div class="epr-compact-detail">{{ $epd->vehicle_name }} | {{ $epd->primary_phone }}</div>
                    @if($epd->follow_date)
                        <div class="epr-compact-date">Followup: {{ \Carbon\Carbon::parse($epd->follow_date)->format('d M Y') }} at {{ substr($epd->follow_time ?? '', 0, 5) }}</div>
                    @endif
                </div>
                <a href="{{ route('followup.show', $epd->id) }}" class="epr-compact-btn">View</a>
            </div>
        @empty
            <div class="epr-empty-state">No home visit EPRs available.</div>
        @endforelse
    </div>
</section>

<section class="card">
    <h2>Your Hierarchy</h2>
    <ul class="list">
        <li>
            <strong>Area Manager</strong>
            <span>{{ optional($user->manager)->name ?? 'Not assigned' }}</span>
        </li>
        <li>
            <strong>Regional Manager</strong>
            <span>{{ optional(optional($user->manager)->manager)->name ?? 'Not assigned' }}</span>
        </li>
        <li>
            <strong>Head Of Sales</strong>
            <span>{{ optional(optional(optional($user->manager)->manager)->manager)->name ?? 'Not assigned' }}</span>
        </li>
    </ul>
</section>

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection