@extends('layouts.portal')

@section('content')
<section class="card dashboard-header-card">
    <h1>Head Of Sales Dashboard</h1>
    <p>You manage Area Managers and overall sales operations.</p>

    <div class="stats-grid">
        <div class="stat"><strong>{{ $hierarchyCounts['dependent_users'] }}</strong><span>Total Dependent Users</span></div>
        <div class="stat"><strong>{{ $hierarchyCounts['area_managers'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $hierarchyCounts['sales_consultants'] }}</strong><span>Sales Consultants</span></div>
    </div>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'area-manager') }}">Register Area Manager</a>
        <a class="btn-link" href="{{ route('dashboard.analytics') }}">Analytics Filters</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card">
    <h2>Hierarchy Lead Summary</h2>
    <div class="analytics-kpi-grid">
        <div class="analytics-kpi">
            <span>Total Leads</span>
            <strong>{{ $analytics['kpis']['total_leads'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Active Leads</span>
            <strong>{{ $analytics['kpis']['active_leads'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Lost Leads</span>
            <strong>{{ $analytics['kpis']['lost_leads'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Closed Leads</span>
            <strong>{{ $analytics['kpis']['closed_leads'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Pending Followups</span>
            <strong>{{ $analytics['kpis']['pending_followups'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Done Followups</span>
            <strong>{{ $analytics['kpis']['done_followups'] ?? 0 }}</strong>
        </div>
    </div>
</section>

<section class="card hierarchy-card">
    <h2>Team Hierarchy</h2>
    <ul class="list hierarchy-list">
        @forelse($hierarchy as $areaManager)
            <li>
                <details class="hierarchy-toggle hierarchy-head-toggle">
                    <summary>
                        <span class="hierarchy-summary-main">
                            <strong>{{ $areaManager['name'] }} (Area Manager)</strong>
                            <span>{{ $areaManager['email'] }}</span>
                            <span>{{ $areaManager['phone'] ?: 'Phone not set' }}</span>
                        </span>
                        <span class="hierarchy-summary-counts">Sales Consultants: {{ $areaManager['sales_consultants_count'] }}</span>
                    </summary>

                    @if(!empty($areaManager['sales_consultants']))
                        <div class="hierarchy-leaf-wrap">
                            @foreach($areaManager['sales_consultants'] as $salesConsultant)
                                <span class="hierarchy-pill" title="{{ $salesConsultant['email'] }}{{ $salesConsultant['phone'] ? ' | ' . $salesConsultant['phone'] : '' }}">{{ $salesConsultant['name'] }}</span>
                            @endforeach
                        </div>
                    @else
                        <span>No Sales Consultants assigned under this Area Manager yet.</span>
                    @endif
                </details>
            </li>
        @empty
            <li>No Area Managers assigned to you yet.</li>
        @endforelse
    </ul>
</section>

<div class="hierarchy-metric-actions" aria-label="Lead and followup analytics shortcuts">
    <a class="hierarchy-metric-btn analytics" href="{{ route('dashboard.analytics') }}">Analytics Filters</a>
    <a class="hierarchy-metric-btn active" href="{{ route('dashboard.analytics.detail', 'active') }}">Active</a>
    <a class="hierarchy-metric-btn booking" href="{{ route('dashboard.analytics.detail', 'booking') }}">Booking</a>
    <a class="hierarchy-metric-btn lost" href="{{ route('dashboard.analytics.detail', 'lost') }}">Lost</a>
    <a class="hierarchy-metric-btn closed" href="{{ route('dashboard.analytics.detail', 'closed') }}">Closed Lead</a>
    <a class="hierarchy-metric-btn followup" href="{{ route('dashboard.followup_tracker') }}">FollowUp</a>
</div>

<section class="card district-card">
    <h2>Hierarchy District Lead Overview</h2>
    <p>Lead counts by district for users under your Head Of Sales hierarchy.</p>
    <div class="analytics-table-wrap">
        <table class="analytics-table district-summary-table">
            <thead>
                <tr>
                    <th>District</th>
                    <th>Leads</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($analytics['by_district'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['district'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No district data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
