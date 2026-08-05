@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Delivery Approvals</h1>
    <p>Approve or reject delivery submissions from Sales Consultants in your area.</p>
</section>

<section class="card delivery-approvals-card">
    <div class="consultant-pending-head">
        <div>
            <h2>Pending and Approved Delivery</h2>
            <p>Open the delivery form to review full customer, vehicle, payment, and receipt details.</p>
        </div>
    </div>

    <div class="analytics-table-wrap">
        <table class="analytics-table delivery-approvals-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Vehicle</th>
                    <th>Sales Consultant</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Delivery Date</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                    @php
                        $customerName = trim((string) (($delivery->enquiry?->customer?->title ? $delivery->enquiry->customer->title . '. ' : '') . ($delivery->enquiry?->customer?->name ?? $delivery->name ?? 'Customer')));
                        $vehicleName = trim((string) (($delivery->enquiry?->vehicle?->model ?? $delivery->interested_model ?? '') . ' ' . ($delivery->enquiry?->vehicle?->variant ?? $delivery->interested_variant ?? '')));
                        $status = (string) ($delivery->approval_status ?? \App\Models\Delivery::APPROVAL_DRAFT);
                        $statusLabel = ucfirst($status);
                        $statusClass = 'delivery-status-' . $status;
                    @endphp
                    <tr>
                        <td>
                            <strong>#{{ $delivery->enquiry_id }} - {{ $customerName }}</strong>
                            <span>{{ $delivery->enquiry?->customer?->mobile_numbers ?? $delivery->mobile_numbers ?? '' }}</span>
                        </td>
                        <td>{{ $vehicleName !== '' ? $vehicleName : '-' }}</td>
                        <td>
                            <strong>{{ $delivery->enquiry?->user?->name ?? 'Sales Consultant' }}</strong>
                            <span>{{ $delivery->enquiry?->user?->email ?? '' }}</span>
                        </td>
                        <td>
                            <span class="delivery-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($delivery->approval_note)
                                <small>{{ $delivery->approval_note }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $delivery->submitted_at?->format('M d, Y h:i A') ?? '-' }}
                            @if($delivery->submittedBy)
                                <small>By {{ $delivery->submittedBy->name }}</small>
                            @endif
                        </td>
                        <td>{{ $delivery->date_of_delivery?->format('M d, Y') ?? '-' }}</td>
                        <td>
                            <strong>{{ number_format((float) ($delivery->payment_delivery_amount ?? 0), 2) }}</strong>
                            <span>Delivery amount</span>
                        </td>
                        <td>
                            <div class="delivery-approval-actions">
                                <a class="btn-link alt" href="{{ route('delivery.show', ['enquiry' => $delivery->enquiry_id, 'step' => 6]) }}">Review</a>

                                @if($status === \App\Models\Delivery::APPROVAL_PENDING)
                                    <form method="POST" action="{{ route('delivery.approve', $delivery) }}">
                                        @csrf
                                        <button type="submit" class="btn-link">Approve</button>
                                    </form>

                                    <form method="POST" action="{{ route('delivery.reject', $delivery) }}" class="delivery-reject-form">
                                        @csrf
                                        <input type="text" name="approval_note" placeholder="Reject note">
                                        <button type="submit" class="btn-link alt">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No delivery approvals found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
