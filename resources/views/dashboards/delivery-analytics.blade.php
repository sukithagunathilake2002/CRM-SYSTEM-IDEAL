@extends('layouts.portal')

@section('content')
<section class="card delivery-page-head">
    <div>
        <h1>Delivery Analytics</h1>
        <p>Select a delivery date range to generate delivery breakdowns.</p>
    </div>
    <a class="btn-link alt" href="{{ $backRoute }}">Back to Dashboard</a>
</section>

<section class="card delivery-date-page-card">
    <h2>Select Date Range</h2>
    <form method="GET" action="{{ route('dashboard.delivery_analytics') }}" class="delivery-date-page-form">
        <label>
            From Date
            <input type="date" name="delivery_from_date" value="{{ $deliveryAnalytics['filters']['from_date'] ?? '' }}">
        </label>
        <label>
            To Date
            <input type="date" name="delivery_to_date" value="{{ $deliveryAnalytics['filters']['to_date'] ?? '' }}">
        </label>
        <div class="delivery-date-page-actions">
            <button type="submit" class="btn-primary delivery-generate-btn">Generate</button>
            <a class="btn-link alt" href="{{ route('dashboard.delivery_analytics') }}">Clear All</a>
        </div>
    </form>
</section>

@if(!($deliveryAnalytics['has_date_range'] ?? false))
    <section class="card delivery-empty-card">
        <h2>No Date Range Selected</h2>
        <p>Please choose From Date and To Date, then generate the delivery report.</p>
    </section>
@endif

@include('dashboards.partials.delivery-analytics')
@endsection
