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
            <input type="search" id="eprSearch" placeholder="Search" value="{{ request('q') }}">
        </label>
        <div class="toolbar-actions">
            <button type="button" class="tool-btn" id="eprFilterBtn">Filter</button>
            <button type="button" class="tool-btn" id="eprSortBtn" data-sort="{{ request('sort') === 'oldest' ? 'oldest' : 'newest' }}">Sort: {{ request('sort') === 'oldest' ? 'Old' : 'New' }}</button>
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

    <p id="eprLoadStatus" role="status" class="epr-page-summary" hidden></p>
    <div id="eprResults">
        @include('enquiries.partials.results')
    </div>
</div>

<script src="{{ asset('js/enquiries.js') }}?v={{ filemtime(public_path('js/enquiries.js')) }}"></script>
@endsection
