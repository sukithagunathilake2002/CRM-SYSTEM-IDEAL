@extends('layouts.portal')

@section('content')
<section class="card auth-card">
    <h1>Request Lead Transfer</h1>
    <p>Send a transfer request for the selected lead to your Area Manager with a reason.</p>

    @php
        $selectedLead = !empty($selectedLeadId) ? $leads->firstWhere('id', $selectedLeadId) : null;
    @endphp

    @if($selectedLead)
        <form method="POST" action="{{ route('lead_transfer.request.store') }}" class="form-grid">
            @csrf

            @php
                $selectedCustomerName = trim((string) (($selectedLead->customer?->title ? $selectedLead->customer->title . '. ' : '') . ($selectedLead->customer?->name ?? 'Unknown')));
                $selectedVehicleName = trim((string) (($selectedLead->vehicle?->model ?? '') . ' ' . ($selectedLead->vehicle?->variant ?? '')));
            @endphp
            <input type="hidden" name="enquiry_id" value="{{ $selectedLead->id }}">
            <label>
                Lead
                <input
                    type="text"
                    value="#{{ $selectedLead->id }} - {{ $selectedCustomerName }}{{ $selectedVehicleName !== '' ? ' - ' . $selectedVehicleName : '' }}"
                    readonly
                >
            </label>

            <label>
                Transfer To
                <select name="to_user_id" required>
                    <option value="">Select Sales Consultant</option>
                    @foreach($targetConsultants as $consultant)
                        <option value="{{ $consultant->id }}" @selected((string) old('to_user_id') === (string) $consultant->id)>
                            {{ $consultant->name }} ({{ $consultant->email }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                Reason
                <textarea name="reason" rows="4" required placeholder="Explain why this lead should be transferred.">{{ old('reason') }}</textarea>
            </label>

            <button type="submit" class="btn-primary" @disabled($targetConsultants->isEmpty())>Send Request</button>
        </form>
    @else
        <p class="muted-note">Open a lead from All Leads and click Transfer to request transfer for that relevant lead.</p>
    @endif

    @if($targetConsultants->isEmpty())
        <p class="muted-note">No other Sales Consultants are assigned under your Area Manager yet.</p>
    @endif
</section>

<section class="card">
    <h2>My Transfer Requests</h2>
    <ul class="list">
        @forelse($requests as $transferRequest)
            @php
                $customerName = trim((string) (($transferRequest->enquiry?->customer?->title ? $transferRequest->enquiry->customer->title . '. ' : '') . ($transferRequest->enquiry?->customer?->name ?? 'Lead')));
            @endphp
            <li>
                <strong>#{{ $transferRequest->enquiry_id }} - {{ $customerName }}</strong>
                <span>To: {{ $transferRequest->toUser?->name ?? 'Unknown' }}</span>
                <span>Status: {{ ucfirst($transferRequest->status) }}</span>
                <span>Reason: {{ $transferRequest->reason }}</span>
                @if($transferRequest->decision_note)
                    <span>Manager note: {{ $transferRequest->decision_note }}</span>
                @endif
            </li>
        @empty
            <li>No transfer requests yet.</li>
        @endforelse
    </ul>
</section>
@endsection
