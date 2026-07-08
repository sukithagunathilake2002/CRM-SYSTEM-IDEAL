@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Lead Transfer Approvals</h1>
    <p>Approve or reject transfer requests from Sales Consultants in your hierarchy.</p>
</section>

<section class="card">
    <h2>Requests</h2>
    <ul class="list">
        @forelse($requests as $transferRequest)
            @php
                $customerName = trim((string) (($transferRequest->enquiry?->customer?->title ? $transferRequest->enquiry->customer->title . '. ' : '') . ($transferRequest->enquiry?->customer?->name ?? 'Lead')));
                $vehicleName = trim((string) (($transferRequest->enquiry?->vehicle?->model ?? '') . ' ' . ($transferRequest->enquiry?->vehicle?->variant ?? '')));
            @endphp
            <li>
                <strong>#{{ $transferRequest->enquiry_id }} - {{ $customerName }}</strong>
                @if($vehicleName !== '')
                    <span>{{ $vehicleName }}</span>
                @endif
                <span>From: {{ $transferRequest->fromUser?->name ?? 'Unknown' }}</span>
                <span>To: {{ $transferRequest->toUser?->name ?? 'Unknown' }}</span>
                <span>Status: {{ ucfirst($transferRequest->status) }}</span>
                <span>Reason: {{ $transferRequest->reason }}</span>
                @if($transferRequest->decision_note)
                    <span>Manager note: {{ $transferRequest->decision_note }}</span>
                @endif

                @if($transferRequest->status === \App\Models\LeadTransferRequest::STATUS_PENDING)
                    <div class="quick-links transfer-actions">
                        <form method="POST" action="{{ route('lead_transfer.approve', $transferRequest) }}">
                            @csrf
                            <button type="submit" class="btn-link">Approve & Transfer</button>
                        </form>

                        <form method="POST" action="{{ route('lead_transfer.reject', $transferRequest) }}" class="transfer-reject-form">
                            @csrf
                            <input type="text" name="decision_note" placeholder="Reject note (optional)">
                            <button type="submit" class="btn-link alt">Reject</button>
                        </form>
                    </div>
                @endif
            </li>
        @empty
            <li>No transfer requests found.</li>
        @endforelse
    </ul>
</section>
@endsection
