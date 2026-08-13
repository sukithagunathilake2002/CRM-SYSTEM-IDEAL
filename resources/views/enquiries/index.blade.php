@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/enquiries.css') }}?v={{ filemtime(public_path('css/enquiries.css')) }}">

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
                    <button type="button" class="epr-filter-pill" data-filter-tab="model">Model <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="lead_source">Lead Source <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="exchange">Exchange <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="due_followup">Due Date of Followup <span>&rsaquo;</span></button>
                    <button type="button" class="epr-filter-pill" data-filter-tab="followup_type">Followup Type <span>&rsaquo;</span></button>
                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <button type="button" class="epr-filter-pill" data-filter-tab="role">Role <span>&rsaquo;</span></button>
                    @endif
                </div>

                <div class="epr-filter-options">
                    <div class="epr-filter-fields active" data-filter-panel="inquiry_period">
                        <input type="date" id="filterInquiryFrom" placeholder="Date From">
                        <input type="date" id="filterInquiryTo" placeholder="Date To">
                    </div>

                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <div class="epr-filter-fields" data-filter-panel="role">
                            <label class="epr-filter-option-search">
                                <input type="search" id="filterRoleSearch" placeholder="Search roles">
                            </label>
                            <div class="epr-filter-choice-list" id="filterRoleOptions"></div>
                        </div>

                    @endif

                    <div class="epr-filter-fields" data-filter-panel="model">
                        <label class="epr-filter-option-search">
                            <input type="search" id="filterModelSearch" placeholder="Search model">
                        </label>
                        <div class="epr-filter-choice-list" id="filterModelOptions"></div>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="lead_source">
                        <label class="epr-filter-option-search">
                            <input type="search" id="filterLeadSourceSearch" placeholder="Search lead source">
                        </label>
                        <div class="epr-filter-choice-list" id="filterLeadSourceOptions"></div>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="exchange">
                        <div class="epr-filter-choice-list" id="filterExchangeOptions"></div>
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="due_followup">
                        <input type="date" id="filterDueFrom" placeholder="Date From">
                        <input type="date" id="filterDueTo" placeholder="Date To">
                    </div>

                    <div class="epr-filter-fields" data-filter-panel="followup_type">
                        <label class="epr-filter-option-search">
                            <input type="search" id="filterFollowupTypeSearch" placeholder="Search followup type">
                        </label>
                        <div class="epr-filter-choice-list" id="filterFollowupTypeOptions"></div>
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
                $vehicleItems = $e->selectedVehicleItems();
                $vehicleName = $e->selectedVehicleDisplay();
                if ($vehicleName === '') {
                    $vehicleName = trim((optional($vehicle)->model ?? '') . ' ' . (optional($vehicle)->variant ?? ''));
                }
                $inquiryDate = optional($e->created_at)->format('d F Y');
                $inquiryDateIso = optional($e->created_at)->format('Y-m-d');
                $followLabel = $e->follow_type ? $e->follow_type . ' On' : 'Followup On';
                $followDate = $e->follow_date ? \Carbon\Carbon::parse($e->follow_date)->format('d F Y') : '--';
                $followDateIso = $e->follow_date ? \Carbon\Carbon::parse($e->follow_date)->format('Y-m-d') : '';
                $modelFilterValues = collect($vehicleItems)
                    ->map(fn(array $item) => trim((string) ($item['model'] ?? '')))
                    ->filter()
                    ->whenEmpty(fn($items) => $items->push(trim((string) (optional($vehicle)->model ?? ''))))
                    ->map(fn($model) => strtolower((string) $model))
                    ->filter()
                    ->unique()
                    ->values();
                $modelValue = $modelFilterValues->first() ?? strtolower((string) (optional($vehicle)->model ?? ''));
                $leadSourceValue = strtolower((string) ($e->lead_source ?? ''));
                $followTypeValue = strtolower((string) ($e->follow_type ?? ''));
                $exchangeValue = (int) $e->exchange === 1 ? 'yes' : 'no';
                $ownerUser = $e->user;
                $ownerName = trim((string) ($ownerUser?->name ?? 'Unassigned'));
                $ownerRole = strtolower((string) ($ownerUser?->role ?? 'unassigned'));
                $ownerRoleLabel = trim((string) ($ownerUser?->role_label ?? 'Unassigned'));
                $ownerIdValue = $ownerUser?->id ? (string) $ownerUser->id : '';
                $searchKeywords = collect([
                    $e->id,
                    'ENQ-' . $e->id,
                    $customerName,
                    $primaryPhone,
                    preg_replace('/\D+/', '', $primaryPhone),
                    $vehicleName,
                    optional($vehicle)->model,
                    optional($vehicle)->engine_type,
                    optional($vehicle)->variant,
                    $e->lead_source,
                    $e->source_of_information,
                    $e->follow_type,
                    $inquiryDate,
                    $inquiryDateIso,
                    $followDate,
                    $followDateIso,
                    $ownerName,
                    $ownerRoleLabel,
                ])
                    ->merge(collect($vehicleItems)->map(fn(array $item) => $item['label'] ?? ''))
                    ->map(fn($value) => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->implode(' ');
                $leadStatus = strtolower(trim((string) ($e->prospectSheet?->lead_status ?? '')));
                $leadStatusLabel = in_array($leadStatus, ['hot', 'warm', 'cold'], true) ? ucfirst($leadStatus) : '';
                $terminalLeadResult = $e->terminalLeadResult();
                $terminalLeadLabel = $terminalLeadResult ? ucfirst($terminalLeadResult) . ' Lead' : '';
                $bookingAvailable = $e->canOpenBooking();
                $deliveryAvailable = $e->canOpenDelivery();
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
                data-models="{{ $modelFilterValues->implode('|') }}"
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
                data-lead-status="{{ $leadStatus }}"
                data-search="{{ strtolower($searchKeywords) }}"
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
                        @if($leadStatusLabel !== '')
                            <span class="flag-pill {{ $leadStatus }}" title="Lead Status">{{ $leadStatusLabel }}</span>
                        @endif
                        @if($terminalLeadLabel !== '')
                            <span class="flag-pill terminal {{ $terminalLeadResult }}" title="Lead Result">{{ $terminalLeadLabel }}</span>
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
                                @if(count($vehicleItems) > 1)
                                    <div class="vehicle-selection-list" aria-label="Selected vehicle models">
                                        @foreach($vehicleItems as $vehicleItem)
                                            @php
                                                $vehicleItemLabel = trim((string) ($vehicleItem['label'] ?? ''));
                                            @endphp
                                            @if($vehicleItemLabel !== '')
                                                <span>{{ strtoupper($vehicleItemLabel) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="chip-row">
                                @if($terminalLeadResult)
                                    <span class="terminal-lead-note">{{ $terminalLeadLabel }} - workflow closed</span>
                                @else
                                    <a href="{{ route('followup.show', $e->id) }}" class="chip-btn">Followup</a>
                                    <a href="{{ route('prospect.show', $e->id) }}" class="chip-btn">Prospect Sheet</a>
                                    @if($bookingAvailable)
                                        <a href="{{ route('booking.show', $e->id) }}" class="chip-btn">Booking</a>
                                    @else
                                        <span class="chip-btn chip-btn-disabled" aria-disabled="true">Booking</span>
                                    @endif
                                    @if($deliveryAvailable)
                                        <a href="{{ route('delivery.show', $e->id) }}" class="chip-btn">Delivery</a>
                                    @else
                                        <span class="chip-btn chip-btn-disabled" aria-disabled="true">Delivery</span>
                                    @endif
                                @endif
                                @if(!$terminalLeadResult && auth()->user()?->role === \App\Models\User::ROLE_SALES_CONSULTANT && (int) $e->user_id === (int) auth()->id())
                                    <a href="{{ route('lead_transfer.request.create', ['enquiry_id' => $e->id]) }}" class="chip-btn">Transfer</a>
                                @endif
                                @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                    <form method="POST" action="{{ route('enquiries.destroy', $e->id) }}" class="lead-delete-form" onsubmit="return confirm('Delete this lead permanently?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="chip-btn chip-btn-danger">Delete</button>
                                    </form>
                                @endif
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
                        <div class="epr-date-item epr-created-user">
                            <span class="epr-date-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <circle cx="12" cy="8" r="3.4"></circle>
                                    <path d="M5 19c0-3.2 3.1-5.8 7-5.8s7 2.6 7 5.8"></path>
                                </svg>
                            </span>
                            <p>Created User</p>
                            <strong title="{{ $ownerRoleLabel }}">{{ $ownerName }}</strong>
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

<script src="{{ asset('js/enquiries.js') }}?v={{ filemtime(public_path('js/enquiries.js')) }}"></script>
@endsection
