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
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['owner_role'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
                <option value="unassigned" @selected(($analytics['filters']['owner_role'] ?? '') === 'unassigned')>Unassigned</option>
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

<section class="card analytics-card">
    <div class="analytics-head">
        <h2>Lead Analytics</h2>
        <p>Scope: {{ $analytics['scope_label'] }} ({{ $analytics['scope_user_count'] }} user{{ $analytics['scope_user_count'] === 1 ? '' : 's' }})</p>
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

<section class="card analytics-card">
    <h2>Current User Hierarchy</h2>
    <div class="hierarchy-badges">
        @foreach($analytics['current_hierarchy'] as $node)
            <span>{{ $node['role'] }}: {{ $node['name'] }}</span>
        @endforeach
    </div>
</section>

<section class="card analytics-card">
    <h2>By Hierarchy (Role)</h2>
    <div class="analytics-table-wrap">
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Users</th>
                    <th>Leads</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analytics['by_role'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['users'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">No hierarchy data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card analytics-card">
    <h2>By District</h2>
    <div class="analytics-table-wrap">
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>District</th>
                    <th>Leads</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analytics['by_district'] as $row)
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

<section class="card analytics-card">
    <h2>By User</h2>
    @php
        $byUserRoles = collect($analytics['by_user'])
            ->pluck('role')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $byUserManagers = collect($analytics['by_user'])
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
                @forelse($analytics['by_user'] as $row)
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

@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endonce

<script>
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
    })();
</script>
