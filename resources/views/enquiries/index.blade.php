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
                <div class="epr-card-top">
                    <div class="lead-flags">
                        @if($e->exchange)
                            <span class="flag-pill" title="Exchange">EX</span>
                        @endif
                        @if($e->finance)
                            <span class="flag-pill money" title="Finance">$</span>
                        @endif
                    </div>

                    <div class="epr-customer">
                        <span class="epr-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <circle cx="12" cy="8" r="3.4"></circle>
                                <path d="M5 19c0-3.2 3.1-5.8 7-5.8s7 2.6 7 5.8"></path>
                            </svg>
                        </span>
                        <div class="epr-customer-text">
                            <h3 class="lead-name">{{ strtoupper($customerName) }}</h3>
                            <span class="epr-name-underline"></span>
                        </div>
                    </div>

                    <a href="{{ $whatsAppPhone !== '' ? 'tel:' . $primaryPhone : '#' }}" class="lead-phone-pill" aria-label="Call {{ $primaryPhone }}">
                        <span class="lead-phone-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M7.7 10.5c1.4 2.8 3 4.4 5.8 5.8l1.9-1.9a1.1 1.1 0 0 1 1.1-.3 11.4 11.4 0 0 0 3.6.6 1.2 1.2 0 0 1 1.2 1.2V20a1.2 1.2 0 0 1-1.2 1.2A18.8 18.8 0 0 1 2.8 3.9 1.2 1.2 0 0 1 4 2.8h4.1A1.2 1.2 0 0 1 9.3 4a11.4 11.4 0 0 0 .6 3.6 1.1 1.1 0 0 1-.3 1.1Z"></path>
                            </svg>
                        </span>
                        <span class="lead-phone">{{ $primaryPhone }}</span>
                    </a>
                </div>

                <div class="epr-card-body">
                    <div class="epr-vehicle-panel">
                        <div class="epr-vehicle-row">
                            <span class="epr-vehicle-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M3 13h14l2 3v3h-2a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H3v-6Z"></path>
                                    <path d="M6 13 8 8h7l2 5"></path>
                                    <circle cx="7" cy="19" r="1.2"></circle>
                                    <circle cx="15" cy="19" r="1.2"></circle>
                                </svg>
                            </span>
                            <div class="epr-vehicle-text">
                                <p class="epr-meta-label">VEHICLE / INTEREST</p>
                                <p class="vehicle-line">{{ strtoupper($vehicleName ?: 'VEHICLE NOT SET') }}</p>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="chip-row">
                                <a href="{{ route('followup.show', $e->id) }}" class="chip-btn">Followup</a>
                                <a href="{{ route('prospect.show', $e->id) }}" class="chip-btn">Prospect Sheet</a>
                                <a href="{{ route('booking.show', $e->id) }}" class="chip-btn">Booking</a>
                            </div>
                        </div>
                    </div>

                    <div class="epr-date-panel">
                        <div class="epr-date-item">
                            <span class="epr-date-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M7 3v3M17 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path>
                                </svg>
                            </span>
                            <p>Date of Inquiry</p>
                            <strong>{{ $inquiryDate ?: '--' }}</strong>
                        </div>
                        <div class="epr-date-item">
                            <span class="epr-date-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M7 3v3M17 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path>
                                </svg>
                            </span>
                            <p>{{ $followLabel }}</p>
                            <strong>{{ $followDate }}</strong>
                        </div>
                    </div>
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
