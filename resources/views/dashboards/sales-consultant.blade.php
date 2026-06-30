@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Sales Consultant Dashboard</h1>
    <p>Manage your leads, followups, and CRM workflow.</p>

    <div class="quick-links">
        <a class="btn-link alt" href="{{ route('enquiries.list') }}">Open EPR</a>
        <a class="btn-link alt" href="{{ url('/new-enquiry') }}">Create New Enquiry</a>
        <a class="btn-link" href="{{ route('lead_transfer.request.create') }}">
            Transfer Requests{{ ($pendingTransferRequestCount ?? 0) > 0 ? ' (' . $pendingTransferRequestCount . ')' : '' }}
        </a>
    </div>
</section>

@endsection
