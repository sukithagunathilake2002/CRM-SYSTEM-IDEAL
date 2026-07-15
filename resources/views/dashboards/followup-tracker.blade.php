@extends('layouts.portal')

@section('content')
<style>
    .followup-tracker-home {
        min-height: 220px;
        border: 1px solid #d8e2f0;
        background: #f7f7f7;
        box-shadow: none;
        text-align: center;
    }

    .followup-tracker-home h1 {
        margin: 12px 0 28px;
        color: #ff0000;
        font-size: 24px;
        font-weight: 500;
    }

    .followup-tracker-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
    }

    .followup-tracker-actions .btn-link {
        min-height: 34px;
        border-radius: 3px;
        background: #2f7eb8;
        color: #ffffff;
        font-size: 14px;
        line-height: 1.2;
        text-decoration: none;
    }

    @media (max-width: 760px) {
        .followup-tracker-home h1 {
            font-size: 21px;
        }

        .followup-tracker-actions {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<section class="card followup-tracker-home">
    <h1>Follow-up Tracker Dashboard</h1>

    <div class="followup-tracker-actions">
        <a class="btn-link" href="{{ route('dashboard.followup_summary') }}">Pending Follow-up</a>
        <a class="btn-link" href="{{ route('dashboard.followup_tracker.section', 'today-due') }}">No. Of Leads Follow Up Today</a>
        <a class="btn-link" href="{{ route('dashboard.followup_tracker.section', 'today-attempted') }}">No. Follow Ups Attempted Today</a>
        <a class="btn-link" href="{{ route('dashboard.followup_tracker.section', 'total-followed') }}">Total No. Of Leads Followed Up</a>
        <a class="btn-link" href="{{ route('dashboard.followup_tracker.section', 'total-attempted') }}">Total No. Of Follow Ups Attempted</a>
    </div>
</section>
@endsection
