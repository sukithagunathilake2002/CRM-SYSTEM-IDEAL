@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>FollowUp Summary</h1>
    <p>Review pending and delayed follow-up graphs.</p>
    <div class="quick-links">
        <a class="btn-link alt" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard.home') }}">Back</a>
    </div>
</section>

@include('dashboards.partials.followup-escalations', [
    'followupEscalations' => $followupEscalations,
])
@endsection
