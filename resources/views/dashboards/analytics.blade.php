@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Analytics</h1>
    <p>Use filters to review the relevant lead and follow-up graphs.</p>
    <div class="quick-links">
        <a class="btn-link alt" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard.home') }}">Back</a>
    </div>
</section>

@include('dashboards.partials.analytics', [
    'analytics' => $analytics,
    'showAnalyticsUserTable' => false,
])
@endsection
