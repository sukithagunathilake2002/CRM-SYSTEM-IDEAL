@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/followup.css') }}">

@php
    $monthLabels = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];
    $lostReasonLabels = [
        'issue_with_product' => 'Issue with product',
        'got_better_discount' => 'Got better discount',
        'other' => 'Other',
    ];
@endphp

<div class="followup-page">
    <header class="followup-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>

        <div class="followup-top-actions">
            <a href="{{ url('/epr') }}" class="top-circle" aria-label="Back to EPR">&larr;</a>
        </div>
    </header>

    <main class="followup-shell">
        @if(session('success'))
            <div class="followup-flash success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="followup-flash error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="lead-summary-card">
            <p><strong>Name :</strong> {{ $customerName }}</p>
            <p><strong>Interested In :</strong> {{ strtoupper($interestedIn) }}</p>
            <p><strong>Total price :</strong> {{ number_format($totalPrice, 2) }}</p>
            <p><strong>DMS ID :</strong> {{ $primaryPhone }}</p>
            @if($enquiry->follow_type)
                <p><strong>Followup Type :</strong> {{ $enquiry->follow_type }}</p>
            @endif
        </section>

        <section class="followup-card">
            <form method="POST" action="{{ route('followup.update_status', $enquiry->id) }}" enctype="multipart/form-data" id="followupForm">
                @csrf
                <input type="hidden" name="followup_status" id="followupStatusInput" value="{{ $selectedFollowupStatus }}">
                <input type="hidden" name="is_home_visit" id="isHomeVisit" value="{{ $isHomeVisit ? '1' : '0' }}">

                <div class="followup-head-grid">
                    <div class="followup-left">
                        <p class="followup-date">{{ strtoupper($followDateLabel) }}</p>
                        <p class="followup-type">{{ $followTypeLabel }}</p>
                        <p class="followup-status {{ $followupStatus }}">{{ $statusLabel }}</p>
                    </div>

                    <div class="followup-right">
                        <p class="followup-title">Follow up Status</p>
                        <div class="status-actions">
                            <button type="button" class="status-btn status-toggle-btn done-btn {{ $selectedFollowupStatus === 'done' ? 'active' : '' }}" data-status="done">Done</button>
                            <button type="button" class="status-btn status-toggle-btn not-done-btn {{ $selectedFollowupStatus === 'not_done' ? 'active' : '' }}" data-status="not_done">Not Done</button>
                        </div>
                    </div>
                </div>

                <div id="doneQuestionWrap" class="done-question-wrap {{ $selectedFollowupStatus === 'done' ? '' : 'hidden' }}">
                    {{-- Visit Date, Met Whom, and Image Upload - Only for Home Visit EPRs --}}
                    @if($showPhysicalVisitFields)
                        <div class="done-question-row">
                            <div class="done-field">
                                <label>Visit Date</label>
                                <input type="date" name="followup_visit_date" value="{{ $selectedVisitDate }}">
                            </div>
                            <div class="done-field">
                                <label>Met whom?</label>
                                <input type="text" name="followup_met_whom" value="{{ $selectedMetWhom }}" placeholder="Mr Sampath">
                            </div>
                        </div>

                        <div class="photo-tile-grid">
                            <label class="photo-tile">
                                <input type="file" name="followup_picture_1" accept=".jpg,.jpeg,.png,.webp">
                                <span>Picture 1</span>
                            </label>
                            <label class="photo-tile">
                                <input type="file" name="followup_picture_2" accept=".jpg,.jpeg,.png,.webp">
                                <span>Picture 2</span>
                            </label>
                        </div>

                        <div class="photo-links">
                            @if(!empty($enquiry->followup_picture_1))
                                <a href="{{ asset('storage/' . $enquiry->followup_picture_1) }}" target="_blank" rel="noopener">View Picture 1</a>
                            @endif
                            @if(!empty($enquiry->followup_picture_2))
                                <a href="{{ asset('storage/' . $enquiry->followup_picture_2) }}" target="_blank" rel="noopener">View Picture 2</a>
                            @endif
                        </div>
                    @endif

                    <label class="done-label">Interested in Competition</label>
                    <div class="result-segment">
                        <label><input type="radio" name="followup_result" value="active" @checked($selectedResult === 'active')><span>Active</span></label>
                        <label><input type="radio" name="followup_result" value="lost" @checked($selectedResult === 'lost')><span>Lost</span></label>
                        <label><input type="radio" name="followup_result" value="closed" @checked($selectedResult === 'closed')><span>Closed</span></label>
                    </div>
                </div>

                <div id="activeQuestionWrap" class="active-question-wrap {{ $selectedFollowupStatus === 'done' && $selectedResult === 'active' ? '' : 'hidden' }}">
                    <div class="row">
                        <input type="text" name="followup_customer_comment" value="{{ $selectedCustomerComment }}" class="pill-input" placeholder="Enter Customer Comments here......">
                    </div>

                    <div class="row split">
                        <div>
                            <label>Expected month of conversion</label>
                            <div class="row split tight">
                                <select name="followup_conversion_year" class="pill-select">
                                    @for($year = now()->year - 4; $year <= now()->year + 6; $year++)
                                        <option value="{{ $year }}" @selected((string) $selectedConversionYear === (string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                                <select name="followup_conversion_month" class="pill-select">
                                    @foreach($monthLabels as $monthNumber => $monthLabel)
                                        <option value="{{ $monthNumber }}" @selected((string) $selectedConversionMonth === (string) $monthNumber)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <label>Test Drive Given?</label>
                    <div class="simple-segment two">
                        <label><input type="radio" name="followup_test_drive_given" value="yes" @checked($selectedTestDriveGiven === 'yes')><span>Yes</span></label>
                        <label><input type="radio" name="followup_test_drive_given" value="no" @checked($selectedTestDriveGiven === 'no')><span>No</span></label>
                    </div>

                    <div id="testDriveNoWrap" class="row {{ $selectedTestDriveGiven === 'no' ? '' : 'hidden' }}">
                        <label>Why not given?</label>
                        <select name="followup_test_drive_not_given_reason" class="pill-select">
                            <option value="">Select reason</option>
                            @foreach(['Not interested', 'Vehicle not available', 'Vehicle damaged/under repair', 'Not met in person', 'Already driven', 'I Did Not Offer', 'Others'] as $reasonOption)
                                <option value="{{ $reasonOption }}" @selected($selectedTestDriveNoReason === $reasonOption)>{{ $reasonOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="testDriveYesWrap" class="row split {{ $selectedTestDriveGiven === 'yes' ? '' : 'hidden' }}">
                        <div>
                            <label>When?</label>
                            <input type="date" name="followup_test_drive_when" value="{{ $selectedTestDriveWhen }}" class="pill-input">
                        </div>
                        <div>
                            <label>Vehicle used</label>
                            <input type="text" name="followup_test_drive_vehicle_used" value="{{ $selectedTestDriveVehicleUsed }}" class="pill-input" placeholder="Vehicle model">
                        </div>
                        <div>
                            <label>To whom?</label>
                            <input type="text" name="followup_test_drive_to_whom" value="{{ $selectedTestDriveToWhom }}" class="pill-input" placeholder="Mr Sampath">
                        </div>
                    </div>

                    <label>First time buyer?</label>
                    <div class="simple-segment two">
                        <label><input type="radio" name="followup_first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer === 'yes')><span>Yes</span></label>
                        <label><input type="radio" name="followup_first_time_buyer" value="no" @checked($selectedFirstTimeBuyer === 'no')><span>No</span></label>
                    </div>

                    <div id="firstTimeBuyerNoWrap" class="row {{ $selectedFirstTimeBuyer === 'no' ? '' : 'hidden' }}">
                        <label>Why no first conversion?</label>
                        <select name="followup_first_time_buyer_reason" class="pill-select">
                            <option value="">Select reason</option>
                            @foreach(['Budget issue', 'Need family decision', 'Considering competitors', 'No immediate need', 'Other'] as $reasonOption)
                                <option value="{{ $reasonOption }}" @selected($selectedFirstTimeBuyerReason === $reasonOption)>{{ $reasonOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label>What according to you is the Lead Status?</label>
                    <div class="simple-segment three">
                        <label><input type="radio" name="followup_lead_temperature" value="hot" @checked($selectedLeadTemperature === 'hot')><span>Hot</span></label>
                        <label><input type="radio" name="followup_lead_temperature" value="warm" @checked($selectedLeadTemperature === 'warm')><span>Warm</span></label>
                        <label><input type="radio" name="followup_lead_temperature" value="cold" @checked($selectedLeadTemperature === 'cold')><span>Cold</span></label>
                    </div>

                    <label>Next Follow up</label>
                    <div class="simple-segment three">
                        <label><input type="radio" name="followup_next_type" value="Home visit" @checked($selectedNextType === 'Home visit')><span>Home visit</span></label>
                        <label><input type="radio" name="followup_next_type" value="Showroom visit" @checked($selectedNextType === 'Showroom visit')><span>Showroom visit</span></label>
                        <label><input type="radio" name="followup_next_type" value="Call" @checked($selectedNextType === 'Call')><span>Call</span></label>
                    </div>

                    <div class="row split">
                        <div>
                            <label>Scheduled for</label>
                            <input type="date" name="followup_next_date" value="{{ $selectedNextDate }}" class="pill-input">
                        </div>
                        <div>
                            <label>&nbsp;</label>
                            <input type="time" name="followup_next_time" value="{{ $selectedNextTime }}" class="pill-input">
                        </div>
                    </div>
                </div>

                <div id="lostQuestionWrap" class="lost-question-wrap {{ $selectedFollowupStatus === 'done' && $selectedResult === 'lost' ? '' : 'hidden' }}">
                    <label>Lost To</label>
                    <div class="simple-segment two">
                        <label><input type="radio" name="followup_lost_to" value="competitor" @checked($selectedLostTo === 'competitor')><span>Competitor</span></label>
                        <label><input type="radio" name="followup_lost_to" value="co_dealer" @checked($selectedLostTo === 'co_dealer')><span>Co-dealer</span></label>
                    </div>

                    <div id="lostCompetitorWrap" class="row split {{ $selectedLostTo === 'competitor' ? '' : 'hidden' }}">
                        <div>
                            <label>Select brand</label>
                            <select id="followup_lost_competition_brand" name="followup_lost_competition_brand" class="pill-select">
                                <option value="">Select brand</option>
                                @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedLostCompetitionBrand === $brandOption)>{{ $brandOption }}</option>
                                @endforeach
                                @if(!empty($selectedLostCompetitionBrand) && !in_array($selectedLostCompetitionBrand, $competitionBrands, true))
                                    <option value="{{ $selectedLostCompetitionBrand }}" selected>{{ $selectedLostCompetitionBrand }}</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label>Select model</label>
                            <select id="followup_lost_competition_model" name="followup_lost_competition_model" class="pill-select" data-selected-model="{{ $selectedLostCompetitionModel }}">
                                <option value="">Select model</option>
                                @if(!empty($selectedLostCompetitionModel))
                                    <option value="{{ $selectedLostCompetitionModel }}" selected>{{ $selectedLostCompetitionModel }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div id="lostCodealerWrap" class="row {{ $selectedLostTo === 'co_dealer' ? '' : 'hidden' }}">
                        <label>Type Co-dealer name</label>
                        <input type="text" name="followup_lost_codealer_name" value="{{ $selectedLostCodealerName }}" class="pill-input" placeholder="Co-dealer name">
                    </div>

                    <label>Reason for rejecting Mahindra</label>
                    <div class="reason-checklist">
                        @foreach($lostReasonLabels as $reasonKey => $reasonLabel)
                            <label class="reason-item">
                                <input
                                    type="checkbox"
                                    name="followup_lost_reject_reasons[]"
                                    value="{{ $reasonKey }}"
                                    @checked(in_array($reasonKey, $selectedLostRejectReasons, true))
                                    {{ $reasonKey === 'other' ? 'id=followupLostReasonOtherCheckbox' : '' }}
                                >
                                <span>{{ $reasonLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div id="lostOtherReasonWrap" class="row {{ in_array('other', $selectedLostRejectReasons, true) ? '' : 'hidden' }}">
                        <input type="text" name="followup_lost_reject_other_text" value="{{ $selectedLostRejectOtherText }}" class="pill-input" placeholder="Other reason">
                    </div>
                </div>

                {{-- Action buttons - only shown when Done is clicked --}}
                <div id="formActions" class="followup-form-actions {{ $selectedFollowupStatus === 'done' ? '' : 'hidden' }}">
                    <a href="{{ url('/epr') }}" class="status-btn cancel-btn">Cancel</a>
                    <button type="submit" class="status-btn save-btn">Save</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script type="application/json" id="followupCompetitionMapJson">@json($competitionMap)</script>
<script>
    (function () {
        const statusInput = document.getElementById('followupStatusInput');
        const doneWrap = document.getElementById('doneQuestionWrap');
        const activeWrap = document.getElementById('activeQuestionWrap');
        const lostWrap = document.getElementById('lostQuestionWrap');
        const formActions = document.getElementById('formActions');
        const toggleButtons = Array.from(document.querySelectorAll('.status-toggle-btn'));
        const resultRadios = Array.from(document.querySelectorAll('input[name="followup_result"]'));
        const testDriveNoWrap = document.getElementById('testDriveNoWrap');
        const testDriveYesWrap = document.getElementById('testDriveYesWrap');
        const firstTimeBuyerNoWrap = document.getElementById('firstTimeBuyerNoWrap');
        const lostToRadios = Array.from(document.querySelectorAll('input[name="followup_lost_to"]'));
        const lostCompetitorWrap = document.getElementById('lostCompetitorWrap');
        const lostCodealerWrap = document.getElementById('lostCodealerWrap');
        const lostOtherReasonWrap = document.getElementById('lostOtherReasonWrap');
        const lostOtherReasonCheckbox = document.getElementById('followupLostReasonOtherCheckbox');
        const lostBrandSelect = document.getElementById('followup_lost_competition_brand');
        const lostModelSelect = document.getElementById('followup_lost_competition_model');
        const mapScript = document.getElementById('followupCompetitionMapJson');
        let competitionMap = {};

        if (mapScript) {
            try {
                competitionMap = JSON.parse(mapScript.textContent || '{}') || {};
            } catch (error) {
                competitionMap = {};
            }
        }

        function picked(name) {
            const selected = document.querySelector('input[name="' + name + '"]:checked');
            return selected ? selected.value : '';
        }

        function setSelectOptions(select, values, placeholder, selectedValue) {
            if (!select) return;

            const uniqueValues = Array.from(new Set((values || []).filter(Boolean).map(String)));
            select.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);

            uniqueValues.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                select.appendChild(option);
            });

            if (selectedValue && !uniqueValues.includes(selectedValue)) {
                const customOption = document.createElement('option');
                customOption.value = selectedValue;
                customOption.textContent = selectedValue;
                select.appendChild(customOption);
            }

            select.value = selectedValue || '';
        }

        function syncLostModelOptions() {
            if (!lostBrandSelect || !lostModelSelect) return;
            const selectedModel = lostModelSelect.dataset.selectedModel || lostModelSelect.value || '';
            const models = competitionMap[lostBrandSelect.value || ''] || [];
            setSelectOptions(lostModelSelect, models, 'Select model', selectedModel);
            lostModelSelect.dataset.selectedModel = '';
        }

        function syncState() {
            const selectedStatus = statusInput ? statusInput.value : '';
            const selectedResult = picked('followup_result');
            const selectedTestDriveGiven = picked('followup_test_drive_given');
            const selectedFirstTimeBuyer = picked('followup_first_time_buyer');
            const selectedLostTo = picked('followup_lost_to');
            const isLostOtherChecked = lostOtherReasonCheckbox ? lostOtherReasonCheckbox.checked : false;

            if (doneWrap) {
                doneWrap.classList.toggle('hidden', selectedStatus !== 'done');
            }

            if (activeWrap) {
                activeWrap.classList.toggle('hidden', !(selectedStatus === 'done' && selectedResult === 'active'));
            }

            if (lostWrap) {
                lostWrap.classList.toggle('hidden', !(selectedStatus === 'done' && selectedResult === 'lost'));
            }

            // Show/hide form action buttons based on Done selection
            if (formActions) {
                formActions.classList.toggle('hidden', selectedStatus !== 'done');
            }

            if (testDriveNoWrap) {
                testDriveNoWrap.classList.toggle('hidden', selectedTestDriveGiven !== 'no');
            }

            if (testDriveYesWrap) {
                testDriveYesWrap.classList.toggle('hidden', selectedTestDriveGiven !== 'yes');
            }

            if (firstTimeBuyerNoWrap) {
                firstTimeBuyerNoWrap.classList.toggle('hidden', selectedFirstTimeBuyer !== 'no');
            }

            if (lostCompetitorWrap) {
                lostCompetitorWrap.classList.toggle(
                    'hidden',
                    !(selectedStatus === 'done' && selectedResult === 'lost' && selectedLostTo === 'competitor')
                );
            }

            if (lostCodealerWrap) {
                lostCodealerWrap.classList.toggle(
                    'hidden',
                    !(selectedStatus === 'done' && selectedResult === 'lost' && selectedLostTo === 'co_dealer')
                );
            }

            if (lostOtherReasonWrap) {
                lostOtherReasonWrap.classList.toggle(
                    'hidden',
                    !(selectedStatus === 'done' && selectedResult === 'lost' && isLostOtherChecked)
                );
            }

            toggleButtons.forEach((btn) => {
                btn.classList.toggle('active', btn.dataset.status === selectedStatus);
            });
        }

        toggleButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                if (statusInput) {
                    statusInput.value = btn.dataset.status || '';
                }
                syncState();
            });
        });

        resultRadios.forEach((radio) => {
            radio.addEventListener('change', syncState);
        });

        document.querySelectorAll('input[name="followup_test_drive_given"]').forEach((input) => {
            input.addEventListener('change', syncState);
        });

        document.querySelectorAll('input[name="followup_first_time_buyer"]').forEach((input) => {
            input.addEventListener('change', syncState);
        });

        lostToRadios.forEach((input) => {
            input.addEventListener('change', syncState);
        });

        document.querySelectorAll('input[name="followup_lost_reject_reasons[]"]').forEach((input) => {
            input.addEventListener('change', syncState);
        });

        if (lostBrandSelect) {
            lostBrandSelect.addEventListener('change', function () {
                if (lostModelSelect) {
                    lostModelSelect.dataset.selectedModel = '';
                }
                syncLostModelOptions();
            });
        }

        syncLostModelOptions();
        syncState();
    })();
</script>
@endsection