@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>{{ $sectionTitle }}</h1>
    <p>Review the selected analytics graph details.</p>
    <div class="quick-links">
        <a class="btn-link alt" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard.home') }}">Back</a>
    </div>
</section>

@include('dashboards.partials.analytics', [
    'analytics' => $analytics,
    'initialAnalyticsSection' => $section,
    'onlyAnalyticsSection' => $section,
    'showAnalyticsFilters' => false,
    'showLeadAnalyticsSummary' => false,
    'showAnalysisCharts' => false,
    'showAnalyticsUserTable' => false,
])
@endsection
