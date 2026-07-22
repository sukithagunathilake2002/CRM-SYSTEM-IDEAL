@extends('layouts.portal')

@section('content')
<section class="card dashboard-header-card">
    <h1>Area Manager Dashboard</h1>
    <p>You manage Sales Consultants and leads in your area hierarchy.</p>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'sales-consultant') }}">Register Sales Consultant</a>
        <a class="btn-link" href="{{ route('lead_transfer.approvals') }}">
            Transfer Approvals{{ ($pendingTransferRequestCount ?? 0) > 0 ? ' (' . $pendingTransferRequestCount . ')' : '' }}
        </a>
        <a class="btn-link alt" href="{{ url('/new-enquiry') }}">Create New Enquiry</a>
        <a class="btn-link" href="{{ route('dashboard.analytics') }}">Analytics Filters</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card users-card">
    <details class="manage-users-toggle" open>
        <summary>
            <span class="manage-users-heading">Manage All Users</span>
            <span class="manage-users-summary-count">{{ $manageableUsers->count() }} user{{ $manageableUsers->count() === 1 ? '' : 's' }}</span>
        </summary>

        <p>Sales Consultants assigned under your Area Manager hierarchy.</p>
        <div class="analytics-table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Employee Number</th>
                        <th>Role</th>
                        <th>Manager</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manageableUsers as $managedUser)
                    <tr>
                        <td>{{ $managedUser->name }}</td>
                        <td>{{ $managedUser->email }}</td>
                        <td>{{ $managedUser->employee_number ?: '-' }}</td>
                        <td>{{ $managedUser->role_label }}</td>
                        <td>{{ $managedUser->manager?->name ?? '-' }}</td>
                        <td>{{ $managedUser->phone ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">No Sales Consultants assigned to your hierarchy yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
</section>

<section class="card hierarchy-card">
    <h2>Area Manager Team Hierarchy</h2>
    <ul class="list hierarchy-list">
        <li>
            <details class="hierarchy-toggle hierarchy-head-toggle" open>
                <summary>
                    <span class="hierarchy-summary-main">
                        <strong>{{ auth()->user()?->name ?? 'Area Manager' }} (Area Manager)</strong>
                        <span>{{ auth()->user()?->email ?? '' }}</span>
                    </span>
                    <span class="hierarchy-summary-counts">Sales Consultants: {{ $salesConsultants->count() }}</span>
                </summary>

                @if($salesConsultants->isNotEmpty())
                    <div class="hierarchy-leaf-wrap">
                        @foreach($salesConsultants as $salesConsultant)
                            @php
                                $pendingSummary = $consultantPendingDetails[(int) $salesConsultant->id] ?? null;
                            @endphp
                            <button
                                type="button"
                                class="hierarchy-pill hierarchy-consultant-pill"
                                data-consultant-detail="{{ (int) $salesConsultant->id }}"
                                title="{{ $salesConsultant->email }}{{ $salesConsultant->phone ? ' | ' . $salesConsultant->phone : '' }}"
                            >
                                {{ $salesConsultant->name }}
                                <span>{{ (int) ($pendingSummary['total_pending'] ?? 0) }}</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <span>No Sales Consultants assigned under you yet.</span>
                @endif
            </details>
        </li>
    </ul>
</section>

<section class="card consultant-pending-card" id="consultantPendingCard">
    <div class="consultant-pending-head">
        <div>
            <h2>Sales Consultant Pending Details</h2>
            <p>Click a Sales Consultant name above to view pending registration, follow-up, booking, and delivery work.</p>
        </div>
    </div>

    @if(empty($consultantPendingDetails))
        <p class="consultant-pending-empty">No Sales Consultants assigned under you yet.</p>
    @else
        @foreach($consultantPendingDetails as $consultantDetail)
            <div
                class="consultant-detail-panel {{ $loop->first ? 'is-active' : '' }}"
                data-consultant-panel="{{ (int) $consultantDetail['id'] }}"
                @if(!$loop->first) hidden @endif
            >
                <div class="consultant-detail-title">
                    <div>
                        <h3>{{ $consultantDetail['name'] }}</h3>
                        <span>{{ $consultantDetail['email'] ?: 'No email' }}{{ $consultantDetail['phone'] ? ' | ' . $consultantDetail['phone'] : '' }}</span>
                    </div>
                    <div class="consultant-reminder-actions">
                        @if(!empty($consultantDetail['reminder_url']))
                            <a href="{{ $consultantDetail['reminder_url'] }}" class="consultant-reminder-btn consultant-reminder-email-btn">Send by Email</a>
                        @else
                            <button type="button" class="consultant-reminder-btn consultant-reminder-email-btn disabled" disabled>No Email</button>
                        @endif
                        <form method="POST" action="{{ route('dashboard.area_manager.consultant_reminder.system', $consultantDetail['id']) }}" class="consultant-reminder-form">
                            @csrf
                            <button type="submit" class="consultant-reminder-btn consultant-reminder-system-btn">Send Through System</button>
                        </form>
                    </div>
                </div>

                <div class="consultant-pending-kpis">
                    @foreach($consultantDetail['sections'] as $sectionKey => $section)
                        <button type="button" class="consultant-pending-kpi" data-consultant-section="{{ $sectionKey }}">
                            <strong>{{ (int) ($consultantDetail['counts'][$sectionKey] ?? 0) }}</strong>
                            <span>{{ $section['title'] }}</span>
                        </button>
                    @endforeach
                </div>

                <details class="consultant-pending-details">
                    <summary>
                        <span>Maximize Pending Details</span>
                        <strong>{{ (int) ($consultantDetail['total_pending'] ?? 0) }} total pending</strong>
                    </summary>

                    <div class="consultant-pending-sections">
                        @foreach($consultantDetail['sections'] as $sectionKey => $section)
                            <article class="consultant-pending-section" data-consultant-section-panel="{{ $sectionKey }}">
                                <h4>{{ $section['title'] }}</h4>
                                @if(empty($section['items']))
                                    <p class="consultant-pending-empty">No pending records.</p>
                                @else
                                    <div class="consultant-pending-list">
                                        @foreach($section['items'] as $item)
                                            <a href="{{ $item['url'] }}" class="consultant-pending-row">
                                                <span>{{ $item['customer'] }}</span>
                                                <small>{{ $item['vehicle'] }}</small>
                                                <em>{{ $item['follow_date'] }}</em>
                                                <strong>{{ $section['action'] }}</strong>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </details>
            </div>
        @endforeach
    @endif
</section>

<div class="lead-overview-toggle" role="tablist" aria-label="Lead overview type">
    <button type="button" class="lead-overview-tab is-active" data-lead-overview-tab="districtOverviewCard" role="tab" aria-selected="true" aria-controls="districtOverviewCard">
        District Lead Overview
    </button>
    <button type="button" class="lead-overview-tab" data-lead-overview-tab="provinceOverviewCard" role="tab" aria-selected="false" aria-controls="provinceOverviewCard">
        Province Lead Overview
    </button>
</div>

<section id="districtOverviewCard" class="card district-card lead-overview-panel" data-lead-overview-panel>
    <h2>Sri Lanka District Lead Overview</h2>
    <p>Lead counts by district for users under your Area Manager hierarchy.</p>
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

<section id="provinceOverviewCard" class="card district-card lead-overview-panel" data-lead-overview-panel>
    <h2>Sri Lanka Province Lead Overview</h2>
    <p>Lead counts by province for users under your Area Manager hierarchy.</p>
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
(() => {
    const buttons = Array.from(document.querySelectorAll('[data-consultant-detail]'));
    const panels = Array.from(document.querySelectorAll('[data-consultant-panel]'));

    if (buttons.length && panels.length) {
        const activate = (id, shouldScroll = true) => {
            buttons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.consultantDetail === id);
            });

            panels.forEach((panel) => {
                const active = panel.dataset.consultantPanel === id;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });

            if (shouldScroll) {
                document.getElementById('consultantPendingCard')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => activate(button.dataset.consultantDetail || ''));
        });

        document.querySelectorAll('[data-consultant-section]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = button.closest('[data-consultant-panel]');
                const detailToggle = panel?.querySelector('.consultant-pending-details');
                const section = panel?.querySelector(`[data-consultant-section-panel="${button.dataset.consultantSection}"]`);
                if (detailToggle) {
                    detailToggle.open = true;
                }
                window.setTimeout(() => {
                    section?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }, 0);
            });
        });

        activate(buttons[0].dataset.consultantDetail || '', false);
    }
})();

window.IdealLeadMapConfig = {
    mapUrl: @json(asset('data/sri-lanka-districts-map.json')),
    districts: @json($analytics['by_district'] ?? []),
    provinces: @json($analytics['by_province'] ?? []),
    provinceDistrictMap: @json(\App\Models\User::PROVINCE_DISTRICT_MAP),
};
</script>
<script src="{{ asset('js/sri-lanka-lead-map.js') }}"></script>
@endsection
