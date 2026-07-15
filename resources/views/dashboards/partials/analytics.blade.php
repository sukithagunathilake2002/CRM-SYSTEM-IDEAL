@php
    $canViewAnalytics = auth()->user()?->role !== \App\Models\User::ROLE_SALES_CONSULTANT;
    $canViewLostAnalytics = in_array(auth()->user()?->role, [
        \App\Models\User::ROLE_SUPER_ADMIN,
        \App\Models\User::ROLE_HEAD_OF_SALES,
    ], true);
    $canViewActiveAnalytics = $canViewLostAnalytics;
    $canViewClosedAnalytics = $canViewLostAnalytics;
    $lostAnalytics = $analytics['lost_analytics'] ?? ['total' => 0, 'tabs' => []];
    $closedAnalytics = $analytics['closed_analytics'] ?? ['total' => 0, 'tabs' => []];
    $activeAnalytics = $analytics['active_analytics'] ?? ['total' => 0, 'tabs' => []];
    $bookingAnalytics = $analytics['booking_analytics'] ?? ['total' => 0, 'tabs' => []];
    $showAnalyticsUserTable = $showAnalyticsUserTable ?? true;
    $initialAnalyticsSection = $initialAnalyticsSection ?? request('analytics_section', '');
    $onlyAnalyticsSection = $onlyAnalyticsSection ?? null;
    $showAnalyticsFilters = $showAnalyticsFilters ?? true;
    $showLeadAnalyticsSummary = $showLeadAnalyticsSummary ?? true;
    $showAnalysisCharts = $showAnalysisCharts ?? true;
@endphp

@if($canViewAnalytics)
@if($showAnalyticsFilters)
<section class="card analytics-card">
    <div class="analytics-head">
        <h2>Analytics Filters</h2>
        @if($analytics['has_active_filters'])
            <p>Filtered results are shown.</p>
        @else
            <p>No filters applied.</p>
        @endif
    </div>

    @if(!empty($analytics['selected_filter_user_name']))
        <p class="analytics-filter-note">
            User filter: <strong>{{ $analytics['selected_filter_user_name'] }}</strong>
            @if(($analytics['filters']['user_scope'] ?? 'hierarchy') === 'hierarchy' && !empty($analytics['selected_hierarchy_count']))
                with hierarchy users ({{ $analytics['selected_hierarchy_count'] }} user{{ $analytics['selected_hierarchy_count'] === 1 ? '' : 's' }}).
            @endif
        </p>
    @endif

    <form method="GET" action="{{ url()->current() }}" class="analytics-filter-form">
        <label>
            From Date
            <input type="date" name="from_date" value="{{ $analytics['filters']['from_date'] }}">
        </label>
        <label>
            To Date
            <input type="date" name="to_date" value="{{ $analytics['filters']['to_date'] }}">
        </label>
        <label>
            Owner Role
            <select name="owner_role">
                <option value="">All roles</option>
                @foreach($analytics['filter_options']['owner_roles'] as $option)
                    @continue(($option['value'] ?? '') === \App\Models\User::ROLE_HEAD_OF_SALES)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['owner_role'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            District
            <select name="district">
                <option value="">All districts</option>
                @foreach(($analytics['filter_options']['districts'] ?? []) as $option)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['district'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            User
            <select name="user_id" id="analyticsUserSelect">
                <option value="">Select owner role first</option>
                @foreach($analytics['filter_options']['users'] as $option)
                    <option
                        value="{{ $option['id'] }}"
                        data-role-key="{{ $option['role_key'] }}"
                        @selected((string) $analytics['filters']['user_id'] === (string) $option['id'])
                    >
                        {{ $option['name'] }} ({{ $option['role'] }})
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            User Scope
            <select name="user_scope">
                @foreach($analytics['filter_options']['user_scopes'] as $option)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['user_scope'] ?? 'hierarchy') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Lead Result
            <select name="lead_result">
                <option value="">All</option>
                @foreach($analytics['filter_options']['lead_results'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['lead_result'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Lead Temperature
            <select name="lead_temperature">
                <option value="">All</option>
                @foreach($analytics['filter_options']['lead_temperatures'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['lead_temperature'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Followup Type
            <select name="follow_type">
                <option value="">All</option>
                @foreach($analytics['filter_options']['follow_types'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['follow_type'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Followup Status
            <select name="followup_status">
                <option value="">All</option>
                @foreach($analytics['filter_options']['followup_statuses'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['followup_status'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>

        <div class="analytics-filter-actions">
            <button type="submit" class="btn-primary analytics-filter-btn">Apply Filters</button>
            <button type="submit" formaction="{{ route('dashboard.analytics.report') }}" class="btn-link alt analytics-filter-btn">Download Report</button>
            <a href="{{ url()->current() }}" class="btn-link alt analytics-filter-btn analytics-filter-reset">Reset</a>
        </div>
    </form>
</section>
@endif

@if($showLeadAnalyticsSummary)
<section class="card analytics-card">
    <div class="analytics-head">
        <h2>Lead Analytics</h2>
        <p>Scope: {{ $analytics['scope_label'] }} ({{ $analytics['scope_user_count'] }} user{{ $analytics['scope_user_count'] === 1 ? '' : 's' }})</p>
        <div class="analytics-toggle-actions" hidden>
            @if($canViewActiveAnalytics)
                <button type="button" class="btn-link analytics-active-toggle" id="activeAnalyticsToggle">Active</button>
            @endif
            @if($canViewActiveAnalytics)
                <button type="button" class="btn-link analytics-booking-toggle" id="bookingAnalyticsToggle">Booking</button>
            @endif
            @if($canViewLostAnalytics)
                <button type="button" class="btn-link analytics-lost-toggle" id="lostAnalyticsToggle">Lost</button>
            @endif
            @if($canViewClosedAnalytics)
                <button type="button" class="btn-link analytics-closed-toggle" id="closedAnalyticsToggle">Closed Lead</button>
            @endif
        </div>
    </div>

    <div class="analytics-kpi-grid">
        <div class="analytics-kpi">
            <span>Total Leads</span>
            <strong>{{ $analytics['kpis']['total_leads'] }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Active Leads</span>
            <strong>{{ $analytics['kpis']['active_leads'] }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Lost Leads</span>
            <strong>{{ $analytics['kpis']['lost_leads'] }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Closed Leads</span>
            <strong>{{ $analytics['kpis']['closed_leads'] }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Pending Followups</span>
            <strong>{{ $analytics['kpis']['pending_followups'] }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Done Followups</span>
            <strong>{{ $analytics['kpis']['done_followups'] }}</strong>
        </div>
    </div>
</section>
@endif

@if($canViewActiveAnalytics && ($onlyAnalyticsSection === null || $onlyAnalyticsSection === 'active'))
<section class="card analytics-card lost-analytics-card" id="activeAnalyticsCard" hidden>
    <div class="analytics-head lost-analytics-head">
        <div>
            <h2>Active Analytics</h2>
            <p>Active Percentage = active count / total active leads for the selected parameter.</p>
        </div>
        <strong>Total : {{ number_format((int) ($activeAnalytics['total'] ?? 0)) }}</strong>
    </div>

    <div class="lost-analytics-tabs" role="tablist" aria-label="Active analytics charts">
        @foreach(($activeAnalytics['tabs'] ?? []) as $index => $tab)
            <button
                type="button"
                class="lost-analytics-tab {{ $index === 0 ? 'active' : '' }}"
                data-active-tab="{{ $tab['key'] }}"
                role="tab"
                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="lost-analytics-chart-wrap">
        <div class="lost-analytics-chart-top">
            <h3 id="activeAnalyticsTitle">{{ ($activeAnalytics['tabs'][0]['title'] ?? 'EPR Vs Registered') }}</h3>
            <span>Total : <strong id="activeAnalyticsTotal">{{ number_format((int) ($activeAnalytics['total'] ?? 0)) }}</strong></span>
        </div>
        <canvas id="activeAnalyticsChart" aria-label="Active analytics chart"></canvas>
        <p class="lost-analytics-empty" id="activeAnalyticsEmpty" hidden>No active lead data available for this parameter.</p>
    </div>

    <div class="lost-analytics-table-grid" aria-label="Active analytics export tables">
        @foreach(($activeAnalytics['export_tabs'] ?? []) as $index => $tab)
            @php
                $tableId = 'activeAnalyticsExportTable' . $index;
                $firstColumnKey = trim((string) ($tab['export_label'] ?? ''), '_');
                if ($firstColumnKey === '') {
                    $firstColumnKey = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($tab['title'] ?? $tab['label'] ?? 'Active Analytics')), '_');
                }
                $firstColumnKey = $firstColumnKey !== '' ? $firstColumnKey : 'Active_Analytics';
                $activeTableRowCount = count($tab['rows'] ?? []);
                $activeColumns = $tab['columns'] ?? [
                    ['key' => 'label', 'heading' => $firstColumnKey],
                    ['key' => 'count', 'heading' => 'No_of_Leads'],
                    ['key' => 'contribution', 'heading' => 'Contribution'],
                ];
                $activeTotalRow = $tab['total_row'] ?? null;
            @endphp
            <section class="lost-analytics-table-panel" data-lost-export-panel>
                <div class="lost-analytics-table-head">
                    <h3>{{ $tab['title'] ?? $tab['label'] ?? 'Active Analytics' }}</h3>
                    <div class="lost-analytics-export-actions">
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="excel">Excel</button>
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="csv">CSV</button>
                        <button type="button" class="btn-link lost-export-toggle" data-lost-table-toggle>show</button>
                    </div>
                </div>
                <div class="lost-analytics-table-body" hidden>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table lost-analytics-export-table" id="{{ $tableId }}" data-export-name="{{ $firstColumnKey }}">
                            <thead>
                                <tr>
                                    @foreach($activeColumns as $column)
                                        <th>{{ $column['heading'] ?? $column['key'] ?? '' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($tab['rows'] ?? []) as $row)
                                    <tr>
                                        @foreach($activeColumns as $column)
                                            @php
                                                $columnKey = $column['key'] ?? '';
                                                $cellValue = $row[$columnKey] ?? '';
                                                if ($columnKey === 'contribution' && is_numeric($cellValue)) {
                                                    $cellValue = number_format((float) $cellValue, 2) . '%';
                                                }
                                            @endphp
                                            <td>{{ $cellValue }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($activeColumns), 1) }}">No active analytics data available.</td>
                                    </tr>
                                @endforelse
                                @if(is_array($activeTotalRow))
                                    <tr class="lost-analytics-total-row">
                                        @foreach($activeColumns as $column)
                                            <td>{{ $activeTotalRow[$column['key'] ?? ''] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <p class="lost-analytics-table-foot">
                        Showing {{ $activeTableRowCount > 0 ? 1 : 0 }} to {{ $activeTableRowCount }} of {{ $activeTableRowCount }} entries
                    </p>
                </div>
            </section>
        @endforeach
    </div>
</section>
@endif

@if($canViewActiveAnalytics && ($onlyAnalyticsSection === null || $onlyAnalyticsSection === 'booking'))
<section class="card analytics-card lost-analytics-card" id="bookingAnalyticsCard" hidden>
    <div class="analytics-head lost-analytics-head">
        <div>
            <h2>Booking Analytics</h2>
            <p>Booking Percentage = booking count / total bookings for the selected parameter.</p>
        </div>
        <strong>Total : {{ number_format((int) ($bookingAnalytics['total'] ?? 0)) }}</strong>
    </div>

    <div class="lost-analytics-tabs" role="tablist" aria-label="Booking analytics charts">
        @foreach(($bookingAnalytics['tabs'] ?? []) as $index => $tab)
            <button
                type="button"
                class="lost-analytics-tab {{ $index === 0 ? 'active' : '' }}"
                data-booking-tab="{{ $tab['key'] }}"
                role="tab"
                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="lost-analytics-chart-wrap">
        <div class="lost-analytics-chart-top">
            <h3 id="bookingAnalyticsTitle">{{ ($bookingAnalytics['tabs'][0]['title'] ?? 'Type of Booking') }}</h3>
            <span>Total : <strong id="bookingAnalyticsTotal">{{ number_format((int) ($bookingAnalytics['total'] ?? 0)) }}</strong></span>
        </div>
        <canvas id="bookingAnalyticsChart" aria-label="Booking analytics chart"></canvas>
        <p class="lost-analytics-empty" id="bookingAnalyticsEmpty" hidden>No booking data available for this parameter.</p>
    </div>
</section>
@endif

@if($canViewLostAnalytics && ($onlyAnalyticsSection === null || $onlyAnalyticsSection === 'lost'))
<section class="card analytics-card lost-analytics-card" id="lostAnalyticsCard" hidden>
    <div class="analytics-head lost-analytics-head">
        <div>
            <h2>Lost Analytics</h2>
            <p>Lost Leads Percentage = lost leads / total lost leads for the selected parameter.</p>
        </div>
        <strong>Total : {{ number_format((int) ($lostAnalytics['total'] ?? 0)) }}</strong>
    </div>

    <div class="lost-analytics-tabs" role="tablist" aria-label="Lost analytics charts">
        @foreach(($lostAnalytics['tabs'] ?? []) as $index => $tab)
            <button
                type="button"
                class="lost-analytics-tab {{ $index === 0 ? 'active' : '' }}"
                data-lost-tab="{{ $tab['key'] }}"
                role="tab"
                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="lost-analytics-chart-wrap">
        <div class="lost-analytics-chart-top">
            <h3 id="lostAnalyticsTitle">{{ ($lostAnalytics['tabs'][0]['title'] ?? 'Lost To') }}</h3>
            <span>Total : <strong id="lostAnalyticsTotal">{{ number_format((int) ($lostAnalytics['total'] ?? 0)) }}</strong></span>
        </div>
        <canvas id="lostAnalyticsChart" aria-label="Lost analytics chart"></canvas>
        <p class="lost-analytics-empty" id="lostAnalyticsEmpty" hidden>No lost lead data available for this parameter.</p>
    </div>

    <div class="lost-analytics-table-grid" aria-label="Lost analytics export tables">
        @php
            $lostDataHeaders = $lostAnalytics['lost_data_headers'] ?? [];
            $lostDataRows = $lostAnalytics['lost_data_rows'] ?? [];
            $lostDataTableId = 'lostAnalyticsAllLostDataTable';
            $lostDataRowCount = count($lostDataRows);
        @endphp
        <section class="lost-analytics-table-panel lost-analytics-table-panel-wide" data-lost-export-panel>
            <div class="lost-analytics-table-head">
                <h3>All Lost Data</h3>
                <div class="lost-analytics-export-actions">
                    <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $lostDataTableId }}" data-export-format="excel">Excel</button>
                    <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $lostDataTableId }}" data-export-format="csv">CSV</button>
                    <button type="button" class="btn-link lost-export-toggle" data-lost-table-toggle>show</button>
                </div>
            </div>
            <div class="lost-analytics-table-body" hidden>
                <div class="analytics-table-wrap">
                    <table class="analytics-table lost-analytics-export-table lost-data-export-table" id="{{ $lostDataTableId }}" data-export-name="All_Lost_Data">
                        <thead>
                            <tr>
                                @foreach($lostDataHeaders as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lostDataRows as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(count($lostDataHeaders), 1) }}">No lost data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="lost-analytics-table-foot">
                    Showing {{ $lostDataRowCount > 0 ? 1 : 0 }} to {{ $lostDataRowCount }} of {{ $lostDataRowCount }} entries
                </p>
            </div>
        </section>

        @foreach(($lostAnalytics['tabs'] ?? []) as $index => $tab)
            @php
                $tableId = 'lostAnalyticsExportTable' . $index;
                $firstColumnKey = trim((string) ($tab['export_label'] ?? ''), '_');
                if ($firstColumnKey === '') {
                    $firstColumnKey = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($tab['title'] ?? $tab['label'] ?? 'Lost Analytics')), '_');
                }
                $firstColumnKey = $firstColumnKey !== '' ? $firstColumnKey : 'Lost_Analytics';
                $totalLostRows = (int) ($lostAnalytics['total'] ?? 0);
                $lostTableRowCount = count($tab['rows'] ?? []);
            @endphp
            <section class="lost-analytics-table-panel" data-lost-export-panel>
                <div class="lost-analytics-table-head">
                    <h3>{{ $tab['title'] ?? $tab['label'] ?? 'Lost Analytics' }}</h3>
                    <div class="lost-analytics-export-actions">
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="excel">Excel</button>
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="csv">CSV</button>
                        <button type="button" class="btn-link lost-export-toggle" data-lost-table-toggle>show</button>
                    </div>
                </div>
                <div class="lost-analytics-table-body" hidden>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table lost-analytics-export-table" id="{{ $tableId }}" data-export-name="{{ $firstColumnKey }}">
                            <thead>
                                <tr>
                                    <th>{{ $firstColumnKey }}</th>
                                    <th>No_of_Lost_Leads</th>
                                    <th>Contribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($tab['rows'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['label'] ?? '-' }}</td>
                                        <td>{{ (int) ($row['lost_leads'] ?? 0) }}</td>
                                        <td>{{ number_format((float) ($row['contribution'] ?? 0), 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">No lost analytics data available.</td>
                                    </tr>
                                @endforelse
                                <tr class="lost-analytics-total-row">
                                    <td>Total</td>
                                    <td>{{ $totalLostRows }}</td>
                                    <td>{{ $totalLostRows > 0 ? '100.00%' : '0.00%' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="lost-analytics-table-foot">
                        Showing {{ $lostTableRowCount > 0 ? 1 : 0 }} to {{ $lostTableRowCount }} of {{ $lostTableRowCount }} entries
                    </p>
                </div>
            </section>
        @endforeach
    </div>
</section>
@endif

@if($canViewClosedAnalytics && ($onlyAnalyticsSection === null || $onlyAnalyticsSection === 'closed'))
<section class="card analytics-card lost-analytics-card" id="closedAnalyticsCard" hidden>
    <div class="analytics-head lost-analytics-head">
        <div>
            <h2>Closed Lead Analytics</h2>
            <p>Closed Leads Percentage = closed leads / total closed leads for the selected parameter.</p>
        </div>
        <strong>Total : {{ number_format((int) ($closedAnalytics['total'] ?? 0)) }}</strong>
    </div>

    <div class="lost-analytics-tabs" role="tablist" aria-label="Closed lead analytics charts">
        @foreach(($closedAnalytics['tabs'] ?? []) as $index => $tab)
            <button
                type="button"
                class="lost-analytics-tab {{ $index === 0 ? 'active' : '' }}"
                data-closed-tab="{{ $tab['key'] }}"
                role="tab"
                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="lost-analytics-chart-wrap">
        <div class="lost-analytics-chart-top">
            <h3 id="closedAnalyticsTitle">{{ ($closedAnalytics['tabs'][0]['title'] ?? 'Month Wise - Closed') }}</h3>
            <div class="analytics-chart-actions">
                <span>Total : <strong id="closedAnalyticsTotal">{{ number_format((int) ($closedAnalytics['total'] ?? 0)) }}</strong></span>
                <button type="button" class="btn-link alt analytics-drill-back" id="closedAnalyticsBack" hidden>Back</button>
            </div>
        </div>
        <canvas id="closedAnalyticsChart" aria-label="Closed lead analytics chart"></canvas>
        <p class="lost-analytics-empty" id="closedAnalyticsEmpty" hidden>No closed lead data available for this parameter.</p>
    </div>

    <div class="lost-analytics-table-grid" aria-label="Closed lead analytics export tables">
        @foreach(($closedAnalytics['export_tabs'] ?? []) as $index => $tab)
            @php
                $tableId = 'closedAnalyticsExportTable' . $index;
                $firstColumnKey = trim((string) ($tab['export_label'] ?? ''), '_');
                if ($firstColumnKey === '') {
                    $firstColumnKey = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($tab['title'] ?? $tab['label'] ?? 'Closed Lead Analytics')), '_');
                }
                $firstColumnKey = $firstColumnKey !== '' ? $firstColumnKey : 'Closed_Lead_Analytics';
                $totalClosedRows = (int) ($closedAnalytics['total'] ?? 0);
                $closedTableRowCount = count($tab['rows'] ?? []);
                $closedColumns = $tab['columns'] ?? [
                    ['key' => 'label', 'heading' => $firstColumnKey],
                    ['key' => 'closed_leads', 'heading' => 'No_of_Closed_Leads'],
                    ['key' => 'contribution', 'heading' => 'Contribution'],
                ];
                $closedTotalRow = $tab['total_row'] ?? [
                    'label' => 'Total',
                    'closed_leads' => $totalClosedRows,
                    'contribution' => $totalClosedRows > 0 ? '100.00%' : '0.00%',
                ];
            @endphp
            <section class="lost-analytics-table-panel" data-lost-export-panel>
                <div class="lost-analytics-table-head">
                    <h3>{{ $tab['title'] ?? $tab['label'] ?? 'Closed Lead Analytics' }}</h3>
                    <div class="lost-analytics-export-actions">
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="excel">Excel</button>
                        <button type="button" class="btn-link alt lost-export-btn" data-export-table="{{ $tableId }}" data-export-format="csv">CSV</button>
                        <button type="button" class="btn-link lost-export-toggle" data-lost-table-toggle>show</button>
                    </div>
                </div>
                <div class="lost-analytics-table-body" hidden>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table lost-analytics-export-table" id="{{ $tableId }}" data-export-name="{{ $firstColumnKey }}">
                            <thead>
                                <tr>
                                    @foreach($closedColumns as $column)
                                        <th>{{ $column['heading'] ?? $column['key'] ?? '' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($tab['rows'] ?? []) as $row)
                                    <tr>
                                        @foreach($closedColumns as $column)
                                            @php
                                                $columnKey = $column['key'] ?? '';
                                                $cellValue = $row[$columnKey] ?? '';
                                                if ($columnKey === 'contribution' && is_numeric($cellValue)) {
                                                    $cellValue = number_format((float) $cellValue, 2) . '%';
                                                }
                                            @endphp
                                            <td>{{ $cellValue }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($closedColumns), 1) }}">No closed lead analytics data available.</td>
                                    </tr>
                                @endforelse
                                <tr class="lost-analytics-total-row">
                                    @foreach($closedColumns as $column)
                                        <td>{{ $closedTotalRow[$column['key'] ?? ''] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="lost-analytics-table-foot">
                        Showing {{ $closedTableRowCount > 0 ? 1 : 0 }} to {{ $closedTableRowCount }} of {{ $closedTableRowCount }} entries
                    </p>
                </div>
            </section>
        @endforeach
    </div>
</section>
@endif

@if($showAnalysisCharts)
<section class="card analytics-card analysis-charts-card">
    <h2>Analysis Charts</h2>
    <p class="analysis-charts-subtitle">Overview of leads and follow-up performance</p>
    <div class="analytics-chart-grid">
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-lead-result"></span>Lead Result</h3>
            <canvas id="leadResultChart" aria-label="Lead result bar chart"></canvas>
        </div>
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-temp"></span>Lead Temperature</h3>
            <canvas id="leadTemperatureChart" aria-label="Lead temperature pie chart"></canvas>
        </div>
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-follow-split"></span>Followup Type Split</h3>
            <canvas id="followupTotalsChart" aria-label="Followup type pie chart"></canvas>
        </div>
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-follow-status"></span>Followup Done vs Pending</h3>
            <canvas id="followupStatusByTypeChart" aria-label="Followup status by type chart"></canvas>
        </div>
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-user"></span>Leads By User</h3>
            <canvas id="leadsByUserChart" aria-label="Leads by user chart"></canvas>
        </div>
        <div class="analytics-chart-card">
            <h3><span class="chart-title-dot chart-dot-district"></span>Leads By District</h3>
            <canvas id="leadsByDistrictChart" aria-label="Leads by district chart"></canvas>
        </div>
    </div>
</section>
@endif

@if($showAnalyticsUserTable)
<section class="card analytics-card">
    <h2>By User</h2>
    @php
        $byUserAnalytics = collect($analytics['by_user'])
            ->reject(fn ($row) => in_array($row['role'] ?? '', ['Head Of Sales', 'Super Admin'], true))
            ->values();
        $byUserRoles = $byUserAnalytics
            ->pluck('role')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $byUserManagers = $byUserAnalytics
            ->pluck('manager')
            ->filter(fn ($manager) => $manager !== '-')
            ->unique()
            ->sort()
            ->values();
    @endphp
    <div class="analytics-inline-filters">
        <label class="analytics-inline-filter">
            <span>Search (User / Manager)</span>
            <input type="text" id="byUserSearch" placeholder="Type name...">
        </label>
        <label class="analytics-inline-filter">
            <span>Role</span>
            <select id="byUserRoleFilter">
                <option value="">All roles</option>
                @foreach($byUserRoles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
        </label>
        <label class="analytics-inline-filter">
            <span>Manager</span>
            <select id="byUserManagerFilter">
                <option value="">All managers</option>
                @foreach($byUserManagers as $manager)
                    <option value="{{ $manager }}">{{ $manager }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" class="btn-link alt analytics-inline-reset" id="byUserFilterReset">Reset</button>
    </div>
    <div class="analytics-table-wrap">
        <table class="analytics-table" id="byUserAnalyticsTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Manager</th>
                    <th>Total</th>
                    <th>Active</th>
                    <th>Lost</th>
                    <th>Closed</th>
                    <th>Hot</th>
                    <th>Warm</th>
                    <th>Cold</th>
                    <th>Pending</th>
                    <th>Done</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byUserAnalytics as $row)
                    <tr
                        data-user-row="1"
                        data-user="{{ strtolower((string) $row['name']) }}"
                        data-role="{{ strtolower((string) $row['role']) }}"
                        data-manager="{{ strtolower((string) $row['manager']) }}"
                    >
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['role'] }}</td>
                        <td>{{ $row['manager'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['active'] }}</td>
                        <td>{{ $row['lost'] }}</td>
                        <td>{{ $row['closed'] }}</td>
                        <td>{{ $row['hot'] }}</td>
                        <td>{{ $row['warm'] }}</td>
                        <td>{{ $row['cold'] }}</td>
                        <td>{{ $row['pending'] }}</td>
                        <td>{{ $row['done'] }}</td>
                    </tr>
                @empty
                    <tr class="by-user-empty-row">
                        <td colspan="12">No user analytics data available.</td>
                    </tr>
                @endforelse
                <tr id="byUserNoMatchRow" hidden>
                    <td colspan="12">No matching users for selected filter.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
@endif

<style>
    .lost-analytics-table-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .lost-analytics-table-panel {
        overflow: hidden;
        border: 1px solid #d5dde8;
        border-radius: 6px;
        background: #ffffff;
    }

    .lost-analytics-table-panel-wide {
        grid-column: 1 / -1;
    }

    .lost-analytics-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 34px;
        padding: 6px 8px;
        border-bottom: 1px solid #d5dde8;
    }

    .lost-analytics-table-head h3 {
        margin: 0;
        color: #526b8f;
        font-size: 13px;
        font-weight: 700;
    }

    .lost-analytics-export-actions {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    .lost-analytics-export-actions .btn-link {
        min-height: 24px;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .lost-analytics-table-body {
        padding: 0 8px 6px;
    }

    .lost-analytics-export-table th {
        background: #7ec9bf;
        color: #111827;
        font-size: 12px;
        white-space: nowrap;
    }

    .lost-analytics-export-table td {
        font-size: 12px;
        white-space: nowrap;
    }

    .lost-analytics-export-table td:first-child {
        color: #0000ee;
    }

    .lost-data-export-table th,
    .lost-data-export-table td {
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lost-analytics-total-row td {
        font-weight: 800;
    }

    .lost-analytics-table-foot {
        margin: 4px 0 0;
        color: #526b8f;
        font-size: 12px;
    }

    html.theme-dark .lost-analytics-table-panel {
        border-color: #334155;
        background: #111827;
    }

    html.theme-dark .lost-analytics-table-head {
        border-color: #334155;
    }

    html.theme-dark .lost-analytics-table-head h3,
    html.theme-dark .lost-analytics-table-foot {
        color: #cbd5e1;
    }

    @media (max-width: 960px) {
        .lost-analytics-table-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .lost-analytics-table-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endonce

<script>
    (() => {
        const cards = document.querySelectorAll('.analytics-card');

        cards.forEach((card, index) => {
            let header = card.querySelector(':scope > .analytics-head');

            if (!header) {
                const heading = card.querySelector(':scope > h2');
                if (!heading) {
                    return;
                }

                header = document.createElement('div');
                header.className = 'analytics-head analytics-card-titlebar';
                heading.before(header);
                header.appendChild(heading);
            } else {
                header.classList.add('analytics-card-titlebar');
            }

            const toggle = document.createElement('button');
            const toggleId = `analyticsCardToggle${index}`;
            toggle.type = 'button';
            toggle.id = toggleId;
            toggle.className = 'btn-link alt analytics-minimize-toggle';
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = 'Minimize';
            header.appendChild(toggle);

            toggle.addEventListener('click', () => {
                const isMinimized = card.classList.toggle('is-minimized');
                toggle.textContent = isMinimized ? 'Maximize' : 'Minimize';
                toggle.setAttribute('aria-expanded', String(!isMinimized));

                if (!isMinimized) {
                    window.dispatchEvent(new Event('resize'));
                }
            });
        });
    })();

    (() => {
        const form = document.querySelector('.analytics-filter-form');
        if (!form) {
            return;
        }

        const ownerRoleSelect = form.querySelector('select[name="owner_role"]');
        const userSelect = form.querySelector('#analyticsUserSelect');
        if (!ownerRoleSelect || !userSelect) {
            return;
        }

        const userOptions = Array.from(userSelect.options).filter((opt) => opt.value !== '');
        const defaultLabel = userSelect.options[0];

        const applyUserCascade = () => {
            const role = String(ownerRoleSelect.value || '').trim();
            const allowUserSelection = role !== '' && role !== 'unassigned';

            userSelect.disabled = !allowUserSelection;
            defaultLabel.text = allowUserSelection ? 'All users in selected role' : 'Select owner role first';

            userOptions.forEach((option) => {
                const match = allowUserSelection && option.dataset.roleKey === role;
                option.hidden = !match;
                option.disabled = !match;
            });

            const selected = userSelect.options[userSelect.selectedIndex];
            if (!allowUserSelection || (selected && selected.value !== '' && (selected.hidden || selected.disabled))) {
                userSelect.value = '';
            }
        };

        ownerRoleSelect.addEventListener('change', applyUserCascade);
        applyUserCascade();
    })();

    (() => {
        const table = document.getElementById('byUserAnalyticsTable');
        const searchInput = document.getElementById('byUserSearch');
        const roleSelect = document.getElementById('byUserRoleFilter');
        const managerSelect = document.getElementById('byUserManagerFilter');
        const resetBtn = document.getElementById('byUserFilterReset');
        if (!table || !searchInput || !roleSelect || !managerSelect || !resetBtn) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr[data-user-row="1"]'));
        const noMatchRow = document.getElementById('byUserNoMatchRow');

        const normalize = (value) => String(value || '').trim().toLowerCase();

        const applyTableFilter = () => {
            const query = normalize(searchInput.value);
            const role = normalize(roleSelect.value);
            const manager = normalize(managerSelect.value);
            let visibleRows = 0;

            rows.forEach((row) => {
                const userName = normalize(row.dataset.user);
                const roleName = normalize(row.dataset.role);
                const managerName = normalize(row.dataset.manager);

                const matchesQuery = query === ''
                    || userName.includes(query)
                    || managerName.includes(query);
                const matchesRole = role === '' || roleName === role;
                const matchesManager = manager === '' || managerName === manager;

                const isVisible = matchesQuery && matchesRole && matchesManager;
                row.hidden = !isVisible;
                if (isVisible) {
                    visibleRows++;
                }
            });

            if (noMatchRow) {
                noMatchRow.hidden = rows.length === 0 || visibleRows > 0;
            }
        };

        searchInput.addEventListener('input', applyTableFilter);
        roleSelect.addEventListener('change', applyTableFilter);
        managerSelect.addEventListener('change', applyTableFilter);
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            roleSelect.value = '';
            managerSelect.value = '';
            applyTableFilter();
        });

        applyTableFilter();
    })();

    (() => {
        const exportButtons = Array.from(document.querySelectorAll('[data-export-table][data-export-format]'));
        const toggleButtons = Array.from(document.querySelectorAll('[data-lost-table-toggle]'));
        if (!exportButtons.length && !toggleButtons.length) {
            return;
        }

        const normalizeFileName = (value) => {
            const cleaned = String(value || 'lost_analytics')
                .trim()
                .replace(/[^A-Za-z0-9_-]+/g, '_')
                .replace(/^_+|_+$/g, '');
            return cleaned || 'lost_analytics';
        };

        const downloadBlob = (content, fileName, type) => {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        };

        const cellText = (cell) => String(cell.textContent || '').trim();
        const csvEscape = (value) => {
            const text = String(value ?? '');
            return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
        };

        const exportCsv = (table, fileName) => {
            const rows = Array.from(table.querySelectorAll('tr'))
                .map((row) => Array.from(row.children).map((cell) => csvEscape(cellText(cell))).join(','))
                .join('\r\n');
            downloadBlob(rows, `${fileName}.csv`, 'text/csv;charset=utf-8;');
        };

        const exportExcel = (table, fileName) => {
            const worksheet = `
                <html>
                    <head><meta charset="UTF-8"></head>
                    <body>${table.outerHTML}</body>
                </html>
            `;
            downloadBlob(worksheet, `${fileName}.xls`, 'application/vnd.ms-excel;charset=utf-8;');
        };

        exportButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const table = document.getElementById(button.dataset.exportTable || '');
                if (!table) {
                    return;
                }

                const baseName = normalizeFileName(table.dataset.exportName);
                const format = button.dataset.exportFormat;
                if (format === 'excel') {
                    exportExcel(table, baseName);
                    return;
                }

                exportCsv(table, baseName);
            });
        });

        toggleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const panel = button.closest('[data-lost-export-panel]');
                const body = panel?.querySelector('.lost-analytics-table-body');
                if (!body) {
                    return;
                }

                body.hidden = !body.hidden;
                button.textContent = body.hidden ? 'show' : 'hide';
            });
        });
    })();

    (() => {
        if (typeof Chart === 'undefined') {
            return;
        }
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const leadResults = @json($analytics['charts']['lead_results']);
        const leadTemperature = @json($analytics['charts']['lead_temperature']);
        const followupTotals = @json($analytics['charts']['followup_totals']);
        const followupStatusByType = @json($analytics['charts']['followup_by_status']);
        const leadsByUser = @json($analytics['charts']['user_totals']);
        const leadsByDistrict = @json($analytics['charts']['district_totals']);
        const lostAnalytics = @json($lostAnalytics);
        const closedAnalytics = @json($closedAnalytics);
        const activeAnalytics = @json($activeAnalytics);
        const bookingAnalytics = @json($bookingAnalytics);

        const colors = {
            red: '#eca8a0',
            amber: '#ebc9a9',
            green: '#a8ceb9',
            blue: '#abb8eb',
            cyan: '#9ec9d8',
            slate: '#b8acd8',
            pink: '#e7a9c8',
            cool: '#9ec7ea',
        };

        const commonLegend = {
            position: 'right',
            labels: {
                color: '#64748b',
                boxWidth: 12,
                usePointStyle: true,
                pointStyle: 'rectRounded',
                font: { size: 10 }
            }
        };

        const barAnimation = (delayStep = 70) => prefersReducedMotion ? false : {
            duration: 980,
            easing: 'easeOutQuart',
            delay: (context) => {
                if (context.type !== 'data') {
                    return 0;
                }

                const dataIndex = Number(context.dataIndex || 0);
                const datasetIndex = Number(context.datasetIndex || 0);
                return (dataIndex * delayStep) + (datasetIndex * 90);
            },
        };

        const pieAnimation = prefersReducedMotion ? false : {
            duration: 760,
            easing: 'easeOutCubic',
        };

        const createChart = (id, config) => {
            const canvas = document.getElementById(id);
            if (!canvas) {
                return;
            }
            new Chart(canvas, config);
        };

        createChart('leadResultChart', {
            type: 'bar',
            data: {
                labels: leadResults.labels,
                datasets: [{
                    label: 'Leads',
                    data: leadResults.values,
                    backgroundColor: ['#c5b4d9', '#ebb3d1', '#a9d0bd'],
                    borderRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: barAnimation(),
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { size: 10 } }, grid: { color: '#e5e7eb' } }
                },
                animations: { y: { from: 0 } }
            }
        });

        createChart('leadTemperatureChart', {
            type: 'pie',
            data: {
                labels: leadTemperature.labels,
                datasets: [{
                    data: leadTemperature.values,
                    backgroundColor: ['#ebb3c6', '#ebc8a4', '#a8c7e8'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: pieAnimation,
                plugins: { legend: commonLegend },
                cutout: '36%',
            }
        });

        createChart('followupTotalsChart', {
            type: 'pie',
            data: {
                labels: followupTotals.labels,
                datasets: [{
                    data: followupTotals.values,
                    backgroundColor: [colors.amber, colors.green, colors.slate],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: pieAnimation,
                plugins: { legend: commonLegend },
                cutout: '0%',
            }
        });

        createChart('followupStatusByTypeChart', {
            type: 'bar',
            data: {
                labels: followupStatusByType.labels,
                datasets: [
                    {
                        label: 'Done',
                        data: followupStatusByType.done,
                        backgroundColor: '#a8ceb9',
                        borderRadius: 2,
                    },
                    {
                        label: 'Pending',
                        data: followupStatusByType.pending,
                        backgroundColor: '#ecb3ab',
                        borderRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: barAnimation(85),
                plugins: { legend: commonLegend },
                scales: {
                    x: { stacked: false, ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } },
                    y: { stacked: false, beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { size: 10 } }, grid: { color: '#e5e7eb' } }
                },
                animations: { y: { from: 0 } }
            }
        });

        createChart('leadsByUserChart', {
            type: 'bar',
            data: {
                labels: leadsByUser.labels,
                datasets: [{
                    label: 'Total Leads',
                    data: leadsByUser.values,
                    backgroundColor: '#abb8eb',
                    borderRadius: 3,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: barAnimation(),
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { size: 10 } }, grid: { color: '#e5e7eb' } },
                    y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } }
                },
                animations: { x: { from: 0 } }
            }
        });

        createChart('leadsByDistrictChart', {
            type: 'bar',
            data: {
                labels: leadsByDistrict.labels,
                datasets: [{
                    label: 'Total Leads',
                    data: leadsByDistrict.values,
                    backgroundColor: '#ecb1aa',
                    borderRadius: 3,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: barAnimation(),
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { size: 10 } }, grid: { color: '#e5e7eb' } },
                    y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } }
                },
                animations: { x: { from: 0 } }
            }
        });

        const setupBreakdownAnalytics = ({
            toggleId,
            cardId,
            canvasId,
            titleId,
            totalId,
            emptyId,
            backId,
            tabSelector,
            tabDataset,
            analytics,
            fallbackTitle,
        }) => {
            const toggle = document.getElementById(toggleId);
            const card = document.getElementById(cardId);
            const canvas = document.getElementById(canvasId);
            const title = document.getElementById(titleId);
            const total = document.getElementById(totalId);
            const empty = document.getElementById(emptyId);
            const back = backId ? document.getElementById(backId) : null;
            const tabs = Array.from(document.querySelectorAll(tabSelector));
            const tabData = Array.isArray(analytics.tabs) ? analytics.tabs : [];
            if (!card || !canvas || !tabs.length) {
                return;
            }

            let chart = null;
            let activeKey = tabs[0]?.dataset[tabDataset] || '';
            let currentView = null;
            let drillStack = [];

            const getActiveTab = () => tabData.find((tab) => tab.key === activeKey) || tabData[0] || null;

            const setActiveButton = () => {
                tabs.forEach((button) => {
                    const active = button.dataset[tabDataset] === activeKey;
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });
            };

            const getMetric = (view) => view?.metric || 'lost_leads';

            const renderChart = () => {
                const view = currentView || getActiveTab();
                const rows = Array.isArray(view?.rows) ? view.rows : [];
                const metric = getMetric(view);
                const labels = rows.map((row) => row.label);
                const leadCounts = rows.map((row) => Number(row[metric] || 0));
                const contribution = rows.map((row) => Number(row.contribution || 0));
                const hasRows = rows.length > 0;
                const totalValue = view?.total ?? analytics.total ?? 0;

                if (title) {
                    title.textContent = view?.title || fallbackTitle;
                }
                if (total) {
                    total.textContent = new Intl.NumberFormat('en-US').format(Number(totalValue || 0));
                }
                if (empty) {
                    empty.hidden = hasRows;
                }
                if (back) {
                    back.hidden = drillStack.length === 0;
                }
                canvas.hidden = !hasRows;

                if (chart) {
                    chart.destroy();
                    chart = null;
                }
                if (!hasRows) {
                    return;
                }

                chart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: metric,
                                data: leadCounts,
                                backgroundColor: '#1f77b4',
                                borderRadius: 2,
                                yAxisID: 'y',
                            },
                            {
                                type: 'line',
                                label: 'contribution',
                                data: contribution,
                                borderColor: '#ff7a00',
                                backgroundColor: '#ff7a00',
                                tension: 0.28,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                yAxisID: 'y1',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: barAnimation(55),
                        onClick: (_event, elements) => {
                            const first = elements[0];
                            if (!first) {
                                return;
                            }

                            const next = rows[first.index]?.drilldown;
                            if (!next || !Array.isArray(next.rows)) {
                                return;
                            }

                            drillStack.push(view);
                            currentView = next;
                            renderChart();
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#334155',
                                    boxWidth: 10,
                                    usePointStyle: true,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const label = context.dataset.label || '';
                                        const value = Number(context.parsed.y || 0);
                                        return label === 'contribution'
                                            ? `${label}: ${value.toFixed(2)}%`
                                            : `${label}: ${value}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: { color: '#0f172a', font: { size: 10 }, maxRotation: 0, autoSkip: false },
                                grid: { display: false },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: '#0f172a', font: { size: 10 } },
                                grid: { color: '#e5e7eb' },
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                ticks: {
                                    color: '#ff7a00',
                                    font: { size: 10 },
                                    callback: (value) => `${value}%`,
                                },
                                grid: { drawOnChartArea: false },
                            },
                        },
                    },
                });
            };

            const showCard = () => {
                card.hidden = false;
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
                window.requestAnimationFrame(renderChart);
            };

            if (toggle) {
                toggle.setAttribute('aria-controls', cardId);
                toggle.setAttribute('aria-expanded', 'false');
                toggle.addEventListener('click', () => {
                    if (card.hidden) {
                        showCard();
                        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        return;
                    }

                    card.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                });
            }

            tabs.forEach((button) => {
                button.addEventListener('click', () => {
                    activeKey = button.dataset[tabDataset] || activeKey;
                    currentView = null;
                    drillStack = [];
                    setActiveButton();
                    showCard();
                });
            });

            if (back) {
                back.addEventListener('click', () => {
                    currentView = drillStack.pop() || getActiveTab();
                    renderChart();
                });
            }

            setActiveButton();

            return {
                card,
                showCard,
            };
        };

        const breakdownCards = {};

        breakdownCards.active = setupBreakdownAnalytics({
            toggleId: 'activeAnalyticsToggle',
            cardId: 'activeAnalyticsCard',
            canvasId: 'activeAnalyticsChart',
            titleId: 'activeAnalyticsTitle',
            totalId: 'activeAnalyticsTotal',
            emptyId: 'activeAnalyticsEmpty',
            tabSelector: '[data-active-tab]',
            tabDataset: 'activeTab',
            analytics: activeAnalytics,
            fallbackTitle: 'Active Analytics',
        });

        breakdownCards.booking = setupBreakdownAnalytics({
            toggleId: 'bookingAnalyticsToggle',
            cardId: 'bookingAnalyticsCard',
            canvasId: 'bookingAnalyticsChart',
            titleId: 'bookingAnalyticsTitle',
            totalId: 'bookingAnalyticsTotal',
            emptyId: 'bookingAnalyticsEmpty',
            tabSelector: '[data-booking-tab]',
            tabDataset: 'bookingTab',
            analytics: bookingAnalytics,
            fallbackTitle: 'Booking Analytics',
        });

        breakdownCards.lost = setupBreakdownAnalytics({
            toggleId: 'lostAnalyticsToggle',
            cardId: 'lostAnalyticsCard',
            canvasId: 'lostAnalyticsChart',
            titleId: 'lostAnalyticsTitle',
            totalId: 'lostAnalyticsTotal',
            emptyId: 'lostAnalyticsEmpty',
            tabSelector: '[data-lost-tab]',
            tabDataset: 'lostTab',
            analytics: lostAnalytics,
            fallbackTitle: 'Lost Analytics',
        });

        breakdownCards.closed = setupBreakdownAnalytics({
            toggleId: 'closedAnalyticsToggle',
            cardId: 'closedAnalyticsCard',
            canvasId: 'closedAnalyticsChart',
            titleId: 'closedAnalyticsTitle',
            totalId: 'closedAnalyticsTotal',
            emptyId: 'closedAnalyticsEmpty',
            backId: 'closedAnalyticsBack',
            tabSelector: '[data-closed-tab]',
            tabDataset: 'closedTab',
            analytics: closedAnalytics,
            fallbackTitle: 'Closed Lead Analytics',
        });

        const initialAnalyticsSection = @json($initialAnalyticsSection);
        const initialBreakdown = breakdownCards[initialAnalyticsSection] || null;
        if (initialBreakdown) {
            initialBreakdown.showCard();
            window.requestAnimationFrame(() => {
                initialBreakdown.card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    })();
</script>
@endif
