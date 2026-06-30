@php
    $canViewFollowupSummary = in_array(auth()->user()?->role, [
        \App\Models\User::ROLE_SUPER_ADMIN,
        \App\Models\User::ROLE_HEAD_OF_SALES,
    ], true);

    $followupEscalations = $followupEscalations ?? [
        'summary' => [],
        'type_pending_rows' => [],
        'area_manager_rows' => [],
        'sales_consultant_rows' => [],
        'total' => 0,
    ];

    $summaryRows = $followupEscalations['summary'] ?? [];
    $typePendingRows = $followupEscalations['type_pending_rows'] ?? [];
    $areaManagerRows = $followupEscalations['area_manager_rows'] ?? [];
    $salesConsultantRows = $followupEscalations['sales_consultant_rows'] ?? [];
    $totalPending = (int) ($followupEscalations['total'] ?? 0);
    $todaySummary = collect($summaryRows)->firstWhere('label', 'Today') ?? [];
    $todayPending = (int) ($todaySummary['count'] ?? 0);
    $delayedPending = max(0, $totalPending - $todayPending);
    $activePendingTypeCount = collect($typePendingRows)
        ->filter(fn($row) => (int) ($row['count'] ?? 0) > 0)
        ->count();
@endphp

@if($canViewFollowupSummary)
<section class="card followup-summary-card">
    <div class="followup-escalation-head">
        <div>
            <h2>Followup Summary</h2>
            <p>Pending and delayed followups by owner hierarchy.</p>
        </div>
        <span>{{ $totalPending }} pending</span>
    </div>

    <div class="followup-summary-tabs">
        <input class="followup-tab-input" type="radio" name="followup_pending_tab" id="followupTabSummary" checked>
        <input class="followup-tab-input" type="radio" name="followup_pending_tab" id="followupTabOverall">
        <input class="followup-tab-input" type="radio" name="followup_pending_tab" id="followupTabType">
        <input class="followup-tab-input" type="radio" name="followup_pending_tab" id="followupTabArea">
        <input class="followup-tab-input" type="radio" name="followup_pending_tab" id="followupTabConsultants">

        <div class="followup-summary-tabbar" role="tablist" aria-label="Followup pending tabs">
            <label for="followupTabSummary" role="tab">Followup Summary</label>
            <label for="followupTabOverall" role="tab">Overall Pending</label>
            <label for="followupTabType" role="tab">Type Of Pending</label>
            <label for="followupTabArea" role="tab">Area Manager wise Pending</label>
            <label for="followupTabConsultants" role="tab">Sales Consultants Wise Pending</label>
        </div>

        <div class="followup-summary-panels">
            <div class="followup-summary-panel" data-followup-panel="summary">
                <div class="followup-summary-kpis">
                    <div class="followup-summary-kpi">
                        <span>Overall Pending</span>
                        <strong>{{ $totalPending }}</strong>
                    </div>
                    <div class="followup-summary-kpi">
                        <span>Due Today</span>
                        <strong>{{ $todayPending }}</strong>
                    </div>
                    <div class="followup-summary-kpi">
                        <span>Delayed Pending</span>
                        <strong>{{ $delayedPending }}</strong>
                    </div>
                    <div class="followup-summary-kpi">
                        <span>Pending Types</span>
                        <strong>{{ $activePendingTypeCount }}</strong>
                    </div>
                    <div class="followup-summary-kpi">
                        <span>Area Managers</span>
                        <strong>{{ count($areaManagerRows) }}</strong>
                    </div>
                </div>
                <div class="followup-chart-grid followup-chart-grid-summary">
                    <div class="followup-chart-card">
                        <h3>Overall Pending Chart</h3>
                        <canvas id="followupDelaySummaryChart" aria-label="Overall pending followup chart"></canvas>
                    </div>
                    <div class="followup-chart-card">
                        <h3>Type Of Pending Chart</h3>
                        <canvas id="followupTypeSummaryChart" aria-label="Type of pending followup chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="followup-summary-panel" data-followup-panel="overall">
                <div class="followup-chart-card followup-chart-card-wide">
                    <h3>Overall Pending Chart</h3>
                    <canvas id="followupOverallPendingChart" aria-label="Overall pending by delay chart"></canvas>
                </div>
                <div class="analytics-table-wrap followup-summary-table-wrap">
                    <table class="analytics-table followup-summary-table">
                        <thead>
                            <tr>
                                <th>Pending Level</th>
                                <th>Description</th>
                                <th>Pending Leads</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaryRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['description'] }}</td>
                                    <td>{{ (int) $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No pending followups available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="followup-summary-panel" data-followup-panel="type">
                <div class="followup-chart-card followup-chart-card-wide">
                    <h3>Type Of Pending Chart</h3>
                    <canvas id="followupTypePendingChart" aria-label="Pending followups by type chart"></canvas>
                </div>
                <div class="analytics-table-wrap followup-summary-table-wrap">
                    <table class="analytics-table followup-summary-table">
                        <thead>
                            <tr>
                                <th>Followup Type</th>
                                <th>Pending Leads</th>
                                <th>Oldest Followup</th>
                                <th>Max Delay Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($typePendingRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ (int) $row['count'] }}</td>
                                    <td>{{ $row['oldest_follow_date_label'] }}</td>
                                    <td>{{ (int) $row['max_pending_days'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No type of pending followups available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="followup-summary-panel" data-followup-panel="area">
                <div class="followup-chart-card followup-chart-card-wide">
                    <h3>Area Manager wise Pending Chart</h3>
                    <canvas id="followupAreaPendingChart" aria-label="Area Manager wise pending chart"></canvas>
                </div>
                <div class="analytics-table-wrap followup-summary-table-wrap">
                    <table class="analytics-table followup-summary-table">
                        <thead>
                            <tr>
                                <th>Area Manager</th>
                                <th>Sales Consultants</th>
                                <th>Pending Leads</th>
                                <th>Oldest Followup</th>
                                <th>Max Delay Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($areaManagerRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['sales_consultants_count'] ?? 0 }}</td>
                                    <td>{{ (int) $row['count'] }}</td>
                                    <td>{{ $row['oldest_follow_date_label'] }}</td>
                                    <td>{{ (int) $row['max_pending_days'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No Area Manager wise pending followups available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="followup-summary-panel" data-followup-panel="consultants">
                <div class="followup-chart-card followup-chart-card-wide">
                    <h3>Sales Consultants Wise Pending Chart</h3>
                    <canvas id="followupConsultantPendingChart" aria-label="Sales Consultants wise pending chart"></canvas>
                </div>
                <div class="analytics-table-wrap followup-summary-table-wrap">
                    <table class="analytics-table followup-summary-table">
                        <thead>
                            <tr>
                                <th>Sales Consultant</th>
                                <th>Area Manager</th>
                                <th>Pending Leads</th>
                                <th>Oldest Followup</th>
                                <th>Max Delay Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesConsultantRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['area_manager_name'] ?? 'Not assigned' }}</td>
                                    <td>{{ (int) $row['count'] }}</td>
                                    <td>{{ $row['oldest_follow_date_label'] }}</td>
                                    <td>{{ (int) $row['max_pending_days'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No Sales Consultants wise pending followups available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        const boot = () => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const summaryRows = @json($summaryRows);
            const typeRows = @json($typePendingRows);
            const areaRows = @json($areaManagerRows);
            const consultantRows = @json($salesConsultantRows);
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const charts = [];

            const chartColors = ['#2f7eb8', '#4f9f7f', '#e0a544', '#8b70c9', '#d46a6a', '#5d8fd6', '#9aa4b2'];
            const labelList = (rows) => rows.map((row) => String(row.name || row.label || '-'));
            const countList = (rows) => rows.map((row) => Number.parseInt(row.count || 0, 10));

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: prefersReducedMotion ? false : {
                    duration: 850,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `Pending Leads: ${context.parsed.y ?? context.parsed ?? 0}`,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#64748b', font: { size: 10 } },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#64748b', font: { size: 10 } },
                        grid: { color: '#e5e7eb' },
                    },
                },
            };

            const createBarChart = (id, rows, label) => {
                const canvas = document.getElementById(id);
                if (!canvas) {
                    return;
                }

                const chart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labelList(rows),
                        datasets: [{
                            label,
                            data: countList(rows),
                            backgroundColor: rows.map((_, index) => chartColors[index % chartColors.length]),
                            borderRadius: 4,
                        }],
                    },
                    options: commonOptions,
                });

                charts.push(chart);
            };

            createBarChart('followupDelaySummaryChart', summaryRows, 'Overall Pending');
            createBarChart('followupOverallPendingChart', summaryRows, 'Overall Pending');
            createBarChart('followupTypeSummaryChart', typeRows, 'Type Of Pending');
            createBarChart('followupTypePendingChart', typeRows, 'Type Of Pending');
            createBarChart('followupAreaPendingChart', areaRows, 'Area Manager wise Pending');
            createBarChart('followupConsultantPendingChart', consultantRows, 'Sales Consultants Wise Pending');

            const resizeCharts = () => {
                window.requestAnimationFrame(() => {
                    charts.forEach((chart) => chart.resize());
                });
            };

            document.querySelectorAll('input[name="followup_pending_tab"]').forEach((input) => {
                input.addEventListener('change', resizeCharts);
            });

            resizeCharts();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();
</script>
@endif
