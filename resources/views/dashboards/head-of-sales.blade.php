@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Head Of Sales Dashboard</h1>
    <p>You manage Area Managers and overall sales operations.</p>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'area-manager') }}">Register Area Manager</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card">
    <h2>Team Summary</h2>
    <div class="stats-grid">
        <div class="stat"><strong>{{ $hierarchyCounts['dependent_users'] }}</strong><span>Total Dependent Users</span></div>
        <div class="stat"><strong>{{ $hierarchyCounts['area_managers'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $hierarchyCounts['sales_consultants'] }}</strong><span>Sales Consultants</span></div>
    </div>
</section>

<section class="card">
    <h2>Team Hierarchy</h2>
    <ul class="list hierarchy-list">
        @forelse($hierarchy as $areaManager)
            <li>
                <strong>{{ $areaManager['name'] }} (Area Manager)</strong>
                <span>{{ $areaManager['email'] }}</span>
                <span>Sales Consultants: {{ $areaManager['sales_consultants_count'] }}</span>

                @if(!empty($areaManager['sales_consultants']))
                    <div class="hierarchy-leaf-wrap">
                        @foreach($areaManager['sales_consultants'] as $salesConsultant)
                            <span class="hierarchy-pill">{{ $salesConsultant['name'] }}</span>
                        @endforeach
                    </div>
                @else
                    <span>No Sales Consultants assigned under this Area Manager yet.</span>
                @endif
            </li>
        @empty
            <li>No Area Managers assigned to you yet.</li>
        @endforelse
    </ul>
</section>

@include('dashboards.partials.followup-escalations', ['followupEscalations' => $followupEscalations])

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
