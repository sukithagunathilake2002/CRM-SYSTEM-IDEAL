@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Area Manager Dashboard</h1>
    <p>You manage Sales Consultants in your area.</p>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'sales-consultant') }}">Register Sales Consultant</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card">
    <h2>Your Sales Consultants</h2>
    <ul class="list">
        @forelse($salesConsultants as $consultant)
            <li>
                <strong>{{ $consultant->name }}</strong>
                <span>{{ $consultant->email }}</span>
                <span>{{ $consultant->phone ?: 'Phone not set' }}</span>
            </li>
        @empty
            <li>No Sales Consultants assigned to you yet.</li>
        @endforelse
    </ul>
</section>

@include('dashboards.partials.followup-escalations', ['followupEscalations' => $followupEscalations])

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
