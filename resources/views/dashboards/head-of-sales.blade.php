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
        <a class="btn-link" href="{{ route('dashboard.delivery_analytics') }}">Delivery</a>
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
    <a class="hierarchy-metric-btn delivery" href="{{ route('dashboard.delivery_analytics') }}">Delivery</a>
    <a class="hierarchy-metric-btn lost" href="{{ route('dashboard.analytics.detail', 'lost') }}">Lost</a>
    <a class="hierarchy-metric-btn closed" href="{{ route('dashboard.analytics.detail', 'closed') }}">Closed Lead</a>
    <a class="hierarchy-metric-btn followup" href="{{ route('dashboard.followup_tracker') }}">FollowUp</a>
</div>

<section id="districtOverviewCard" class="card district-card">
    <h2>Sri Lanka District Lead Overview</h2>
    <p>Lead counts by district for users under your Head Of Sales hierarchy.</p>
    <div class="district-overview-grid">
        <div class="district-map-card">
            <div id="districtLeadMap" class="district-lead-map"></div>
            <div class="district-map-scale">
                <span class="district-map-scale-title">Lead density</span>
                <div class="district-map-scale-bar"></div>
                <div class="district-map-scale-labels">
                    <span>Low</span>
                    <span>High</span>
                </div>
            </div>
        </div>
        <div class="district-summary-card">
            <div id="districtLeadInfoCard" class="district-lead-info-card">
                <span class="district-lead-info-label">Selected District</span>
                <h3 id="districtLeadInfoName" class="district-lead-info-name">Click a district</h3>
                <p class="district-lead-info-value"><span id="districtLeadInfoCount">0</span> Active Leads</p>
            </div>
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
                            <tr class="district-summary-row" data-district="{{ $row['district'] }}">
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
        </div>
    </div>
</section>

<section id="provinceOverviewCard" class="card district-card">
    <h2>Sri Lanka Province Lead Overview</h2>
    <p>Lead counts by province for users under your Head Of Sales hierarchy.</p>
    <div class="district-overview-grid">
        <div class="district-map-card">
            <div id="provinceLeadMap" class="district-lead-map"></div>
            <div class="district-map-scale">
                <span class="district-map-scale-title">Lead density</span>
                <div class="district-map-scale-bar"></div>
                <div class="district-map-scale-labels">
                    <span>Low</span>
                    <span>High</span>
                </div>
            </div>
        </div>
        <div class="district-summary-card">
            <div id="provinceLeadInfoCard" class="district-lead-info-card">
                <span class="district-lead-info-label">Selected Province</span>
                <h3 id="provinceLeadInfoName" class="district-lead-info-name">Click a province</h3>
                <p class="district-lead-info-value"><span id="provinceLeadInfoCount">0</span> Active Leads</p>
            </div>
            <div class="analytics-table-wrap">
                <table class="analytics-table district-summary-table">
                    <thead>
                        <tr>
                            <th>Province</th>
                            <th>Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($analytics['by_province'] ?? []) as $row)
                            <tr class="district-summary-row province-summary-row" data-province="{{ $row['province'] }}">
                                <td>{{ $row['province'] }}</td>
                                <td>{{ $row['leads'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No province data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
window.IdealLeadMapConfig = {
    mapUrl: @json(asset('data/sri-lanka-districts-map.json')),
    districts: @json($analytics['by_district'] ?? []),
    provinces: @json($analytics['by_province'] ?? []),
    provinceDistrictMap: @json(\App\Models\User::PROVINCE_DISTRICT_MAP),
};
</script>
<script src="{{ asset('js/sri-lanka-lead-map.js') }}"></script>

@endsection
