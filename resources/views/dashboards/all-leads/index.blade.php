@extends('layouts.portal')
@section('content')
<section class="card all-leads-report">
    <div class="all-leads-report-heading">
        <div><a href="{{ route('dashboard.home') }}">&larr; Dashboard</a><h1>All Leads</h1></div>
        <button type="button" class="all-leads-button" data-all-leads-open>Change Filters</button>
    </div>
    <p id="allLeadsLoadStatus" role="status" hidden></p>
    <div id="allLeadsResults">@include('dashboards.all-leads.results')</div>
</section>
@include('dashboards.all-leads.filters')
@endsection
