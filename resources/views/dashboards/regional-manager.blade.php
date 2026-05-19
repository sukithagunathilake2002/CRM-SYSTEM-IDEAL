@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Regional Manager Dashboard</h1>
    <p>You manage Area Managers in your region.</p>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'area-manager') }}">Register Area Manager</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
        <a class="btn-link alt" href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}">Open Day Map</a>
    </div>
</section>

<section class="card">
    <h2>Your Area Managers</h2>
    <ul class="list">
        @forelse($areaManagers as $manager)
            <li>
                <strong>{{ $manager->name }}</strong>
                <span>{{ $manager->email }}</span>
                <span>Sales Consultants: {{ $manager->subordinates_count }}</span>
            </li>
        @empty
            <li>No Area Managers assigned to you yet.</li>
        @endforelse
    </ul>
</section>

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
