@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>Transfer Sales Consultant Data</h1>
    <p>Use filters to move selected leads from one Sales Consultant to another.</p>

    <form method="POST" action="{{ route('dashboard.super_admin.consultant_transfer.run') }}" class="form-grid">
        @csrf

        <label>
            Source Sales Consultant
            <select name="source_consultant_id" required>
                <option value="">Select source consultant</option>
                @foreach($consultants as $consultant)
                    <option
                        value="{{ $consultant->id }}"
                        @selected((string) old('source_consultant_id', $selectedSourceConsultantId) === (string) $consultant->id)
                    >
                        {{ $consultant->name }} ({{ $consultant->email }}) - Manager: {{ $consultant->manager?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Target Sales Consultant
            <select name="target_consultant_id" required>
                <option value="">Select target consultant</option>
                @foreach($consultants as $consultant)
                    <option value="{{ $consultant->id }}" @selected((string) old('target_consultant_id') === (string) $consultant->id)>
                        {{ $consultant->name }} ({{ $consultant->email }}) - Manager: {{ $consultant->manager?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            From Date
            <input type="date" name="from_date" value="{{ old('from_date') }}">
        </label>

        <label>
            To Date
            <input type="date" name="to_date" value="{{ old('to_date') }}">
        </label>

        <label>
            Lead Result
            <select name="lead_result">
                <option value="">All</option>
                @foreach($leadResultOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('lead_result') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Lead Temperature
            <select name="lead_temperature">
                <option value="">All</option>
                @foreach($leadTemperatureOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('lead_temperature') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Followup Type
            <select name="follow_type">
                <option value="">All</option>
                @foreach($followTypeOptions as $option)
                    <option value="{{ $option }}" @selected(old('follow_type') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Followup Status
            <select name="followup_status">
                <option value="">All</option>
                @foreach($followupStatusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('followup_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn-primary">Transfer Filtered Leads</button>
    </form>

    <div class="helper-links">
        <a href="{{ route('dashboard.super_admin') }}">Back to Super Admin Dashboard</a>
    </div>
</section>
@endsection
