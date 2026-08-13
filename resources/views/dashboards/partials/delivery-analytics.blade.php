@php
    $deliveryAnalytics = $deliveryAnalytics ?? [
        'has_date_range' => false,
        'filters' => ['from_date' => '', 'to_date' => ''],
        'total_deliveries' => 0,
        'total_enquiries' => 0,
        'overall_conversion_ratio' => 0,
        'tabs' => [],
    ];
    $deliveryChartId = 'deliveryAnalyticsChart_' . substr(md5((string) spl_object_id((object) $deliveryAnalytics)), 0, 8);
@endphp

@if($deliveryAnalytics['has_date_range'] ?? false)
<section class="card delivery-analytics-card" id="deliveryAnalyticsCard">
    <div class="delivery-analytics-head">
        <div>
            <h2>Delivery Analytics</h2>
            <p>{{ $deliveryAnalytics['filters']['from_date'] ?: 'Start' }} to {{ $deliveryAnalytics['filters']['to_date'] ?: 'Today' }}</p>
        </div>
        <a class="btn-link alt" href="{{ route('dashboard.delivery_analytics') }}">Change Date</a>
    </div>

    <p class="delivery-conversion-note">*- Conversion Ratio = Total no of vehicles delivered / No of enquiries received in that period</p>

    <div class="analytics-kpi-grid delivery-kpi-grid">
        <div class="analytics-kpi">
            <span>Total Delivery</span>
            <strong>{{ $deliveryAnalytics['total_deliveries'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Total Enquiries</span>
            <strong>{{ $deliveryAnalytics['total_enquiries'] ?? 0 }}</strong>
        </div>
        <div class="analytics-kpi">
            <span>Conversion Ratio</span>
            <strong>{{ $deliveryAnalytics['overall_conversion_ratio'] ?? 0 }}%</strong>
        </div>
    </div>

    <div class="delivery-tabs" role="tablist" aria-label="Delivery analytics breakdowns">
        @foreach($deliveryAnalytics['tabs'] ?? [] as $key => $tab)
            <button
                type="button"
                class="delivery-tab {{ $loop->first ? 'active' : '' }}"
                data-delivery-tab="{{ $key }}"
                role="tab"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="delivery-chart-wrap">
        <div class="delivery-chart-title-row">
            <h3 id="deliveryChartTitle">{{ collect($deliveryAnalytics['tabs'] ?? [])->first()['title'] ?? 'Delivery' }}</h3>
            <span>Total : <strong id="deliveryChartTotal">{{ $deliveryAnalytics['total_deliveries'] ?? 0 }}</strong></span>
        </div>
        <canvas id="{{ $deliveryChartId }}" height="140"></canvas>
    </div>

    <div class="analytics-table-wrap delivery-table-wrap">
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Deliveries</th>
                    <th>Enquiries</th>
                    <th>Conversion Ratio</th>
                    <th>Contribution</th>
                </tr>
            </thead>
            <tbody id="deliveryAnalyticsRows"></tbody>
        </table>
    </div>
</section>

<script>
window.IdealDeliveryAnalytics = window.IdealDeliveryAnalytics || [];
window.IdealDeliveryAnalytics.push({
    chartId: @json($deliveryChartId),
    tabs: @json($deliveryAnalytics['tabs'] ?? []),
    total: @json($deliveryAnalytics['total_deliveries'] ?? 0),
});
</script>
@endif

@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
(function() {
    function formatPercent(value) {
        var number = Number(value) || 0;
        return number.toFixed(number % 1 === 0 ? 0 : 2) + '%';
    }

    function renderDeliveryAnalytics(config) {
        if (!config || !config.chartId || !config.tabs || !window.Chart) return;

        var canvas = document.getElementById(config.chartId);
        if (!canvas) return;

        var card = canvas.closest('.delivery-analytics-card');
        var tabs = card.querySelectorAll('[data-delivery-tab]');
        var title = card.querySelector('#deliveryChartTitle');
        var total = card.querySelector('#deliveryChartTotal');
        var tableBody = card.querySelector('#deliveryAnalyticsRows');
        var chart = null;

        function activeRows(key) {
            return (config.tabs[key] && Array.isArray(config.tabs[key].rows)) ? config.tabs[key].rows : [];
        }

        function renderTable(rows) {
            if (!tableBody) return;
            tableBody.innerHTML = '';
            if (!rows.length) {
                var emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="5">No delivery data for selected date range.</td>';
                tableBody.appendChild(emptyRow);
                return;
            }

            rows.forEach(function(row) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td></td><td></td><td></td><td></td><td></td>';
                tr.children[0].textContent = row.label || 'N/A';
                tr.children[1].textContent = Number(row.deliveries) || 0;
                tr.children[2].textContent = Number(row.enquiries) || 0;
                tr.children[3].textContent = formatPercent(row.conversion_ratio);
                tr.children[4].textContent = formatPercent(row.contribution);
                tableBody.appendChild(tr);
            });
        }

        function renderChart(key) {
            var tab = config.tabs[key] || {};
            var rows = activeRows(key);
            var labels = rows.map(function(row) { return row.label || 'N/A'; });
            var deliveries = rows.map(function(row) { return Number(row.deliveries) || 0; });
            var conversion = rows.map(function(row) { return Number(row.conversion_ratio) || 0; });
            var contribution = rows.map(function(row) { return Number(row.contribution) || 0; });

            if (title) title.textContent = tab.title || 'Delivery';
            if (total) total.textContent = String(config.total || 0);
            renderTable(rows);

            if (chart) chart.destroy();
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'deliveries', data: deliveries, backgroundColor: '#287fba', yAxisID: 'y' },
                        { label: 'conversion_ratio', data: conversion, backgroundColor: '#ff8517', yAxisID: 'ratio' },
                        { label: 'contribution', data: contribution, backgroundColor: '#22a447', yAxisID: 'ratio' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var value = context.parsed.y;
                                    if (context.dataset.label === 'deliveries') {
                                        return 'deliveries: ' + value;
                                    }
                                    return context.dataset.label + ': ' + formatPercent(value);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: '#334155' }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#334155' }, grid: { color: '#e5e7eb' } },
                        ratio: {
                            beginAtZero: true,
                            position: 'right',
                            ticks: { color: '#64748b', callback: function(value) { return value + '%'; } },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }

        tabs.forEach(function(button) {
            button.addEventListener('click', function() {
                tabs.forEach(function(tab) {
                    tab.classList.toggle('active', tab === button);
                    tab.setAttribute('aria-selected', tab === button ? 'true' : 'false');
                });
                renderChart(button.dataset.deliveryTab);
            });
        });

        var firstKey = tabs.length ? tabs[0].dataset.deliveryTab : Object.keys(config.tabs)[0];
        if (firstKey) renderChart(firstKey);
    }

    document.addEventListener('DOMContentLoaded', function() {
        (window.IdealDeliveryAnalytics || []).forEach(renderDeliveryAnalytics);
    });
})();
</script>
@endonce
