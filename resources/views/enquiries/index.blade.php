@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/enquiries.css') }}">

<div class="epr-page">
    <header class="epr-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>

        <div class="top-icons-right">
            <button type="button" class="top-icon menu" id="eprMenuFilterBtn" aria-label="Open filters" hidden></button>
        </div>
    </header>

    <section class="toolbar">
        <label class="search-box" for="eprSearch">
            <input type="search" id="eprSearch" placeholder="Search">
        </label>
        <div class="toolbar-actions">
            <button type="button" class="tool-btn" id="eprFilterBtn">Filter</button>
            <button type="button" class="tool-btn" id="eprSortBtn" data-sort="newest">Sort: New</button>
            <a href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}" class="tool-btn map-link">Map</a>
        </div>
    </section>

    <div class="epr-filter-overlay" id="eprFilterOverlay" aria-hidden="true">
        <div class="epr-filter-sheet" role="dialog" aria-modal="true" aria-labelledby="eprFilterTitle">
            <div class="epr-filter-head">
                <h2 id="eprFilterTitle">FILTER BY</h2>
                <button type="button" id="eprFilterClose" class="epr-filter-close" aria-label="Close filter">&times;</button>
            </div>

            <label class="epr-filter-search" for="eprFilterSearch">
                <input type="search" id="eprFilterSearch" placeholder="Search">
            </label>

            <div class="epr-filter-layout">
                <div class="epr-filter-nav">
                    <button type="button" class="epr-filter-pill active" data-filter-tab="inquiry_period">Inquiry Period <span>&rsaquo;</span></button>
                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <button type="button" class="epr-filter-pill" data-filter-tab="role">Role <span>&rsaquo;</span></button>
                        <button type="button" class="epr-filter-pill" data-filter-tab="assigned_user">User <span>&rsaquo;</span></button>
                    @endif
                    <button type="button" class="epr-filter-pill" data-filter-tab="model">Model <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="lead_source">Lead Source <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="exchange">Exchange <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="due_followup">Due Date of Followup <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="followup_type">Followup Type <span>&rsaquo;</span></button>
                </div>

                <div class="epr-filter-options">
                    <div class="epr-filter-fields active" data-filter-panel="inquiry_period">
                        <input type="date" id="filterInquiryFrom" placeholder="Date From">
                        <input type="date" id="filterInquiryTo" placeholder="Date To">
                    </div>

                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <div class="epr-filter-fields" data-filter-panel="role">
                            <select id="filterRole">
                                <option value="">All Roles</option>
                            </select>
                        </div>

                        <div class="epr-filter-fields" data-filter-panel="assigned_user">
                            <select id="filterAssignedUser">
                                <option value="">All Users</option>
                            </select>
                        </div>
                    @endif

                    <div class="epr-filter-fields" data-filter-panel="model">
                        <select id="filterModel">
                            <option value="">All Models</option>
                        </select>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="lead_source">
                        <select id="filterLeadSource">
                            <option value="">All Lead Sources</option>
                        </select>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="exchange">
                        <select id="filterExchange">
                            <option value="">All</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="due_followup">
                        <input type="date" id="filterDueFrom" placeholder="Date From">
                        <input type="date" id="filterDueTo" placeholder="Date To">
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="followup_type">
                        <select id="filterFollowupType">
                            <option value="">All Followup Types</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="epr-filter-actions">
                <button type="button" class="epr-filter-action secondary" id="eprFilterClearBtn">CLEAR</button>
                <button type="button" class="epr-filter-action primary" id="eprFilterApplyBtn">APPLY</button>
            </div>
        </div>
    </div>

    <main class="epr-list" id="eprList">
        @forelse($enquiries as $e)
            @php
                $customer = $e->customer;
                $vehicle = $e->vehicle;
                $mobiles = is_array(optional($customer)->mobile_numbers) ? $customer->mobile_numbers : [];
                $primaryPhone = count($mobiles) ? (string) $mobiles[0] : 'N/A';
                $customerName = trim((optional($customer)->title ? optional($customer)->title . '. ' : '') . (optional($customer)->name ?? 'Unknown'));
                $vehicleName = trim((optional($vehicle)->model ?? '') . ' ' . (optional($vehicle)->variant ?? ''));
                $inquiryDate = optional($e->created_at)->format('d F Y');
                $inquiryDateIso = optional($e->created_at)->format('Y-m-d');
                $followLabel = $e->follow_type ? $e->follow_type . ' On' : 'Followup On';
                $followDate = $e->follow_date ? \Carbon\Carbon::parse($e->follow_date)->format('d F Y') : '--';
                $followDateIso = $e->follow_date ? \Carbon\Carbon::parse($e->follow_date)->format('Y-m-d') : '';
                $modelValue = strtolower((string) (optional($vehicle)->model ?? ''));
                $leadSourceValue = strtolower((string) ($e->lead_source ?? ''));
                $followTypeValue = strtolower((string) ($e->follow_type ?? ''));
                $exchangeValue = (int) $e->exchange === 1 ? 'yes' : 'no';
                $ownerUser = $e->user;
                $ownerName = trim((string) ($ownerUser?->name ?? 'Unassigned'));
                $ownerRole = strtolower((string) ($ownerUser?->role ?? 'unassigned'));
                $ownerRoleLabel = trim((string) ($ownerUser?->role_label ?? 'Unassigned'));
                $ownerIdValue = $ownerUser?->id ? (string) $ownerUser->id : '';
                $whatsAppPhone = preg_replace('/\D+/', '', $primaryPhone);
                if (substr($whatsAppPhone, 0, 1) === '0') {
                    $whatsAppPhone = '94' . substr($whatsAppPhone, 1);
                }
            @endphp

            <article
                class="epr-card"
                data-name="{{ strtolower($customerName) }}"
                data-phone="{{ strtolower($primaryPhone) }}"
                data-vehicle="{{ strtolower($vehicleName) }}"
                data-model="{{ $modelValue }}"
                data-lead-source="{{ $leadSourceValue }}"
                data-follow-type="{{ $followTypeValue }}"
                data-inquiry-date="{{ $inquiryDateIso }}"
                data-follow-date="{{ $followDateIso }}"
                data-exchange="{{ $exchangeValue }}"
                data-owner-id="{{ $ownerIdValue }}"
                data-owner-name="{{ strtolower($ownerName) }}"
                data-owner-name-label="{{ $ownerName }}"
                data-owner-role="{{ $ownerRole }}"
                data-owner-role-label="{{ $ownerRoleLabel }}"
                data-date="{{ optional($e->created_at)->timestamp ?? 0 }}"
            >
                <div class="card-head-pill">
                    <div class="lead-flags">
                        @if($e->exchange)
                            <span class="flag-pill" title="Exchange">EX</span>
                        @endif
                        @if($e->finance)
                            <span class="flag-pill money" title="Finance">$</span>
                        @endif
                        @if(!$e->exchange && !$e->finance)
                            <span class="flag-pill muted">-</span>
                        @endif
                    </div>

                    <h3 class="lead-name">{{ strtoupper($customerName) }}</h3>
                    <p class="lead-phone">{{ $primaryPhone }}</p>
                </div>

                <div class="card-info">
                    <p class="vehicle-line">{{ strtoupper($vehicleName ?: 'VEHICLE NOT SET') }}</p>
                    <div class="date-block">
                        <p>Date of Inquiry : {{ $inquiryDate ?: '--' }}</p>
                        <p>{{ $followLabel }} : {{ $followDate }}</p>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="chip-row">
                        <a href="{{ route('followup.show', $e->id) }}" class="chip-btn">Followup</a>
                        <a href="{{ route('prospect.show', $e->id) }}" class="chip-btn">Prospect Sheet</a>
                        <a href="{{ route('booking.show', $e->id) }}" class="chip-btn">Booking</a>
                    </div>

                    <button type="button" class="menu-dot-btn" onclick="toggleCardMenu(this)" aria-label="More actions" aria-expanded="false">
                        <span></span><span></span><span></span>
                    </button>

                    <nav class="card-menu">
                        <a href="tel:{{ $primaryPhone }}">Call</a>
                        <a href="sms:{{ $primaryPhone }}">Message</a>
                        <a href="{{ $whatsAppPhone ? 'https://wa.me/' . $whatsAppPhone : '#' }}" target="_blank">WhatsApp</a>
                        <a href="mailto:">Email</a>
                        <a href="/transfer/{{ $e->id }}">Transfer Lead</a>
                        <a href="/followup-history/{{ $e->id }}">Followup History</a>
                        <a href="/test-drive/{{ $e->id }}">Test Drive</a>
                    </nav>
                </div>
            </article>
        @empty
            <div class="empty-state">
                No enquiries available.
            </div>
        @endforelse
    </main>
</div>

<script src="{{ asset('js/enquiries.js') }}"></script>
@endsection

