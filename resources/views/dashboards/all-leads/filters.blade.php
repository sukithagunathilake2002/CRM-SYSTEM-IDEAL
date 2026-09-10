@php
    $allLeadsRoute = auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN ? 'dashboard.super_admin.leads' : 'dashboard.admin.leads';
    $allLeadsSelection = old() ?: ($filters ?? []);
    $allLeadsFrom = now('Asia/Colombo')->startOfMonth()->toDateString();
    $allLeadsTo = now('Asia/Colombo')->endOfMonth()->toDateString();
@endphp
<link rel="stylesheet" href="{{ asset('css/all-leads.css') }}?v={{ filemtime(public_path('css/all-leads.css')) }}">
<dialog id="allLeadsFilters" class="all-leads-dialog" aria-labelledby="allLeadsFilterTitle"
    data-options-url="{{ route($allLeadsRoute.'.options') }}"
    data-default-from="{{ $allLeadsFrom }}" data-default-to="{{ $allLeadsTo }}"
    data-has-errors="{{ $errors->getBag('allLeads')->any() ? '1' : '0' }}">
    <div class="all-leads-dialog-heading">
        <h2 id="allLeadsFilterTitle">Select Filters</h2>
        <button type="button" class="all-leads-close" data-all-leads-close aria-label="Close filters">&times;</button>
    </div>
    <form id="allLeadsFilterForm" action="{{ route($allLeadsRoute) }}" method="GET">
        <div class="all-leads-filter-body">
            <p id="allLeadsFilterError" class="all-leads-error" role="alert" @unless($errors->getBag('allLeads')->any()) hidden @endunless>{{ implode(' ', $errors->getBag('allLeads')->all()) }}</p>
            <div class="all-leads-date-grid">
                <label for="allLeadsFrom">From Date
                    <input type="date" id="allLeadsFrom" name="from_date" value="{{ $allLeadsSelection['from_date'] ?? $allLeadsFrom }}">
                </label>
                <label for="allLeadsTo">To Date
                    <input type="date" id="allLeadsTo" name="to_date" value="{{ $allLeadsSelection['to_date'] ?? $allLeadsTo }}">
                </label>
            </div>
            <div class="all-leads-filter-grid">
                @foreach([
                    'area_managers' => 'Area Manager', 'consultants' => 'Consultant Name', 'vehicle_models' => 'Vehicle Model',
                    'test_drive' => 'Test Drive Given', 'first_buyer' => 'First Time Buyer', 'interested_exchange' => 'Interested in Exchange',
                    'competition' => 'Interested in Competition', 'lead_sources' => 'Lead Source',
                ] as $key => $label)
                    <div class="all-leads-filter-field">
                        <span id="allLeadsLabel-{{ $key }}">{{ $label }}</span>
                        <details class="all-leads-select" data-group="{{ $key }}">
                            <summary aria-labelledby="allLeadsLabel-{{ $key }} allLeadsCount-{{ $key }}">
                                <span id="allLeadsCount-{{ $key }}" data-selection-count>None selected</span>
                            </summary>
                            <div class="all-leads-options-panel">
                                <input type="search" class="all-leads-option-search" placeholder="Search options" aria-label="Search {{ $label }}" data-option-search>
                                <div class="all-leads-options" data-options role="group" aria-labelledby="allLeadsLabel-{{ $key }}"></div>
                            </div>
                        </details>
                        @if($key === 'competition')<small>Based on leads lost to a competitor.</small>@endif
                    </div>
                @endforeach
            </div>
            <p class="all-leads-filter-hint">Select filters separately or combine them. No selection includes all values.</p>
            <div class="all-leads-dialog-footer">
                <p>Select a date range of up to 180 days. Empty dates or a longer range will show the current month.</p>
                <div class="all-leads-dialog-actions">
                    <button type="button" class="all-leads-button secondary" id="allLeadsClear">Clear all</button>
                    <button type="submit" class="all-leads-button" id="allLeadsSubmit" disabled>Submit</button>
                </div>
            </div>
            <p id="allLeadsOptionsStatus" role="status" hidden></p>
            <button type="button" class="all-leads-button secondary" id="allLeadsRetry" hidden>Retry loading filters</button>
        </div>
    </form>
</dialog>
<script type="application/json" id="allLeadsInitialSelection">@json($allLeadsSelection)</script>
<script src="{{ asset('js/all-leads.js') }}?v={{ filemtime(public_path('js/all-leads.js')) }}" defer></script>
