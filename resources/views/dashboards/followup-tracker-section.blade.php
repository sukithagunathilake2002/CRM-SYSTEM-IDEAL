@extends('layouts.portal')

@section('content')
<style>
    .followup-report-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        border: 1px solid #d8e2f0;
        box-shadow: none;
    }

    .followup-report-head h1 {
        margin-bottom: 6px;
        color: #111827;
    }

    .followup-report-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .followup-report-notice {
        margin: 0 0 10px;
        text-align: center;
        color: #ff0000;
        font-weight: 600;
    }

    .followup-report-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 92px;
        margin-bottom: 14px;
        padding: 20px 40px;
        border-radius: 6px;
        background: #ffc107;
        color: #5f7594;
    }

    .followup-report-total strong {
        font-size: 38px;
        line-height: 1;
    }

    .followup-report-total span {
        color: #1f2937;
        font-weight: 700;
    }

    .followup-filter-card {
        border: 1px solid #d8e2f0;
        box-shadow: none;
    }

    .followup-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(130px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .followup-filter-field label {
        display: block;
        margin-bottom: 6px;
        color: #5f7594;
        font-weight: 700;
    }

    .followup-filter-field input,
    .followup-filter-field select {
        width: 100%;
        min-height: 36px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
        color: #111827;
    }

    .followup-filter-actions {
        display: flex;
        gap: 8px;
    }

    .followup-filter-note {
        margin: 12px 0 0;
        color: #ff0000;
        font-weight: 600;
    }

    .followup-category-list {
        display: grid;
        gap: 12px;
    }

    .followup-category {
        overflow: hidden;
        border: 1px solid #d8e2f0;
        border-radius: 6px;
        background: #ffffff;
    }

    .followup-category summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 46px;
        padding: 10px 12px;
        color: #5f7594;
        cursor: pointer;
        font-weight: 700;
        list-style: none;
    }

    .followup-category summary::-webkit-details-marker {
        display: none;
    }

    .followup-category-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 28px;
        border-radius: 999px;
        background: #e8f0fb;
        color: #1f5f99;
        font-weight: 800;
    }

    .followup-category-body {
        border-top: 1px solid #e5edf7;
        padding: 12px;
    }

    .followup-report-table td,
    .followup-report-table th {
        white-space: nowrap;
    }

    .followup-report-table td:first-child,
    .followup-report-table th:first-child {
        white-space: normal;
        min-width: 170px;
    }

    html.theme-dark .followup-tracker-home,
    html.theme-dark .followup-report-head,
    html.theme-dark .followup-filter-card,
    html.theme-dark .followup-category {
        border-color: #334155;
        background: #111827;
    }

    html.theme-dark .followup-filter-field input,
    html.theme-dark .followup-filter-field select {
        border-color: #334155;
        background: #0f172a;
        color: #e5e7eb;
    }

    html.theme-dark .followup-report-total span,
    html.theme-dark .followup-report-head h1 {
        color: #e5e7eb;
    }

    @media (max-width: 960px) {
        .followup-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .followup-filter-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .followup-report-head,
        .followup-report-total,
        .followup-category summary {
            align-items: stretch;
            flex-direction: column;
        }

        .followup-report-actions {
            justify-content: flex-start;
        }

        .followup-filter-grid {
            grid-template-columns: 1fr;
        }

        .followup-report-total {
            padding: 18px;
        }
    }
</style>

@php
    $filters = $report['filters'];
    $options = $report['filter_options'];
@endphp

<section class="card followup-report-head">
    <div>
        <h1>{{ $report['title'] }}</h1>
        <p>{{ $report['subtitle'] }}</p>
    </div>
    <div class="followup-report-actions">
        <a class="btn-link alt" href="{{ route('dashboard.followup_tracker') }}">Follow-Up Tracker Dashboard</a>
        <a class="btn-link" href="{{ route('dashboard.home') }}">Home</a>
    </div>
</section>

@if($report['requires_date_filter'])
<section class="card followup-filter-card">
    <form method="GET" action="{{ route('dashboard.followup_tracker.section', $report['section']) }}">
        <div class="followup-filter-grid">
            <div class="followup-filter-field">
                <label for="from_date">From Date</label>
                <input id="from_date" type="date" name="from_date" value="{{ $filters['from_date'] }}">
            </div>
            <div class="followup-filter-field">
                <label for="to_date">To Date</label>
                <input id="to_date" type="date" name="to_date" value="{{ $filters['to_date'] }}">
            </div>
            <div class="followup-filter-field">
                <label for="dealer_id">Area Manager</label>
                <select id="dealer_id" name="dealer_id">
                    <option value="">None selected</option>
                    @foreach($options['area_managers'] as $areaManager)
                        <option value="{{ $areaManager['id'] }}" @selected($filters['dealer_id'] === (string) $areaManager['id'])>{{ $areaManager['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="followup-filter-field">
                <label for="sc_id">SC</label>
                <select id="sc_id" name="sc_id">
                    <option value="">None selected</option>
                    @foreach($options['sales_consultants'] as $salesConsultant)
                        <option value="{{ $salesConsultant['id'] }}" @selected($filters['sc_id'] === (string) $salesConsultant['id'])>{{ $salesConsultant['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="followup-filter-field">
                <label for="model">Model</label>
                <select id="model" name="model">
                    <option value="">None selected</option>
                    @foreach($options['models'] as $model)
                        <option value="{{ $model }}" @selected($filters['model'] === (string) $model)>{{ $model }}</option>
                    @endforeach
                </select>
            </div>
            <div class="followup-filter-actions">
                <a class="btn-link alt" href="{{ route('dashboard.followup_tracker.section', $report['section']) }}">Clear all</a>
                <button class="btn-link" type="submit">Submit</button>
            </div>
        </div>
        <p class="followup-filter-note">* Please select dates within one month</p>
    </form>
</section>
@endif

@if($report['filter_error'])
    <div class="portal-flash error">{{ $report['filter_error'] }}</div>
@else
    <p class="followup-report-notice">*- {{ $report['notice'] }}</p>

    <div class="followup-report-total">
        <strong>{{ number_format((int) $report['total']) }}</strong>
        <span>{{ $report['total_label'] }}@if($report['date_label']) - {{ $report['date_label'] }}@endif</span>
    </div>

    <div class="followup-category-list">
        @foreach($report['groups'] as $group)
            <details class="followup-category" open>
                <summary>
                    <span>{{ $group['label'] }}</span>
                    <span class="followup-category-count">{{ number_format((int) $group['count']) }}</span>
                </summary>
                <div class="followup-category-body">
                    <div class="analytics-table-wrap">
                        <table class="analytics-table followup-report-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Model</th>
                                    <th>Follow Type</th>
                                    <th>Follow Date</th>
                                    <th>Time</th>
                                    <th>Attempted At</th>
                                    <th>Status</th>
                                    <th>Area Manager</th>
                                    <th>SC</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['primary_phone'] }}</td>
                                        <td>{{ $row['vehicle_name'] }}</td>
                                        <td>{{ $row['follow_type'] }}</td>
                                        <td>{{ $row['follow_date'] }}</td>
                                        <td>{{ $row['follow_time'] }}</td>
                                        <td>{{ $row['attempted_at'] }}</td>
                                        <td>{{ $row['status'] }}</td>
                                        <td>{{ $row['area_manager'] }}</td>
                                        <td>{{ $row['sales_consultant'] }}</td>
                                        <td><a class="btn-link alt" href="{{ $row['url'] }}">Show</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11">No follow-up records available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        @endforeach
    </div>
@endif
@endsection
