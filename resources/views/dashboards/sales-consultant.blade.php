@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Sales Consultant Dashboard</h1>
    <p>Manage your leads, followups, and CRM workflow.</p>

    <div class="quick-links">
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
        <a class="btn-link alt" href="{{ url('/new-enquiry') }}">Create New Enquiry</a>
        <a class="btn-link alt" href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}">Open Day Map</a>
    </div>
</section>

<section class="card">
    <h2>Your Hierarchy</h2>
    <ul class="list">
        <li>
            <strong>Area Manager</strong>
            <span>{{ optional($user->manager)->name ?? 'Not assigned' }}</span>
        </li>
        <li>
            <strong>Regional Manager</strong>
            <span>{{ optional(optional($user->manager)->manager)->name ?? 'Not assigned' }}</span>
        </li>
        <li>
            <strong>Head Of Sales</strong>
            <span>{{ optional(optional(optional($user->manager)->manager)->manager)->name ?? 'Not assigned' }}</span>
        </li>
    </ul>
</section>

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
