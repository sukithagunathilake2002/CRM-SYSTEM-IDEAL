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
    $existingFollowupPicture1Url = !empty($enquiry->followup_picture_1) ? asset('storage/' . $enquiry->followup_picture_1) : null;
    $existingFollowupPicture2Url = !empty($enquiry->followup_picture_2) ? asset('storage/' . $enquiry->followup_picture_2) : null;
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
            <div class="lead-summary-grid">
                <article class="summary-item">
                    <span class="summary-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <circle cx="12" cy="8" r="3.2"></circle>
                            <path d="M5.8 18c1.4-2.8 3.8-4.2 6.2-4.2s4.8 1.4 6.2 4.2"></path>
                        </svg>
                    </span>
                    <p><strong>Name :</strong> {{ $customerName }}</p>
                </article>

                <article class="summary-item">
                    <span class="summary-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M4 15.2v2.3h1.8"></path>
                            <path d="M18.2 17.5H20v-2.3"></path>
                            <path d="M6 15.2h12l-1.1-4.2a2.2 2.2 0 0 0-2.1-1.6H9.2a2.2 2.2 0 0 0-2.1 1.6L6 15.2Z"></path>
                            <circle cx="8.3" cy="17.6" r="1.4"></circle>
                            <circle cx="15.7" cy="17.6" r="1.4"></circle>
                            <path d="M8.6 12.5h6.8"></path>
                        </svg>
                    </span>
                    <p><strong>Interested In :</strong> {{ strtoupper($interestedIn) }}</p>
                </article>

                <article class="summary-item">
                    <span class="summary-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M21 16.4v2a1.7 1.7 0 0 1-1.8 1.7A16.8 16.8 0 0 1 3.9 4.8 1.7 1.7 0 0 1 5.6 3h2a1.7 1.7 0 0 1 1.7 1.5c.1 1 .3 2 .7 2.8a1.7 1.7 0 0 1-.4 1.8l-.8.8a13.6 13.6 0 0 0 5.7 5.7l.8-.8a1.7 1.7 0 0 1 1.8-.4c.9.4 1.8.6 2.8.7A1.7 1.7 0 0 1 21 16.4Z"></path>
                        </svg>
                    </span>
                    <p><strong>Contact Number :</strong> {{ $primaryPhone }}</p>
                </article>

                <article class="summary-item">
                    <span class="summary-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M3 11.2 12 4l9 7.2"></path>
                            <path d="M5.5 10.6V20h13V10.6"></path>
                            <path d="M10 20v-5h4v5"></path>
                        </svg>
                    </span>
                    <p><strong>Followup Type :</strong> {{ $enquiry->follow_type ?: 'N/A' }}</p>
                </article>
            </div>
        </section>

        <section class="followup-card">
            <form method="POST" action="{{ route('followup.update_status', $enquiry->id) }}" enctype="multipart/form-data" id="followupForm">
                @csrf
                <input type="hidden" name="followup_status" id="followupStatusInput" value="{{ $selectedFollowupStatus }}">
                <input type="hidden" name="is_home_visit" id="isHomeVisit" value="{{ $isHomeVisit ? '1' : '0' }}">

                <div class="followup-head-grid">
                    <div class="followup-left followup-metrics">
                        <article class="metric-item">
                            <span class="metric-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <rect x="3.5" y="5" width="17" height="15" rx="2"></rect>
                                    <path d="M7 3.5v3M17 3.5v3M3.5 9.5h17"></path>
                                    <path d="M8 13h2M12 13h2M16 13h2M8 16h2M12 16h2"></path>
                                </svg>
                            </span>
                            <div>
                                <p class="followup-date">{{ strtoupper($followDateLabel) }}</p>
                                <p class="metric-label">Date</p>
                            </div>
                        </article>

                        <article class="metric-item">
                            <span class="metric-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M3 11.2 12 4l9 7.2"></path>
                                    <path d="M5.5 10.6V20h13V10.6"></path>
                                    <path d="M10 20v-5h4v5"></path>
                                </svg>
                            </span>
                            <div>
                                <p class="followup-type">{{ $followTypeLabel }}</p>
                                <p class="metric-label">Follow-up Type</p>
                            </div>
                        </article>

                        <article class="metric-item">
                            <span class="metric-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <circle cx="12" cy="12" r="7.8"></circle>
                                    <path d="M12 7.8v4.4l2.8 1.7"></path>
                                </svg>
                            </span>
                            <div>
                                <p class="followup-status {{ $followupStatus }}">{{ $statusLabel }}</p>
                                <p class="metric-label">Status</p>
                            </div>
                        </article>
                    </div>

                    <div class="followup-right">
                        <p class="followup-title">Follow up Status</p>
                        <div class="status-actions">
                            <button type="button" class="status-btn status-toggle-btn done-btn {{ $selectedFollowupStatus === 'done' ? 'active' : '' }}" data-status="done">Done</button>
                            <button type="button" class="status-btn status-toggle-btn not-done-btn {{ $selectedFollowupStatus === 'not_done' ? 'active' : '' }}" data-status="not_done">Not Done</button>
                        </div>
                    </div>
                </div>

                {{-- Done Form --}}
                <div id="doneQuestionWrap" class="done-question-wrap {{ $selectedFollowupStatus === 'done' ? '' : 'hidden' }}">
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
                            <div class="photo-tile" data-photo-tile="1" data-existing-url="{{ $existingFollowupPicture1Url ?? '' }}">
                                <input type="hidden" name="followup_remove_picture_1" id="followup_remove_picture_1" value="{{ old('followup_remove_picture_1', '0') }}">
                                <label class="photo-pick" for="followup_picture_1">
                                    <input
                                        type="file"
                                        id="followup_picture_1"
                                        name="followup_picture_1"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        data-preview-input="1"
                                    >
                                    <img
                                        src="{{ $existingFollowupPicture1Url ?? '' }}"
                                        alt="Picture 1 preview"
                                        class="photo-preview {{ $existingFollowupPicture1Url ? '' : 'hidden' }}"
                                        data-preview-img="1"
                                    >
                                    <span class="photo-tile-text {{ $existingFollowupPicture1Url ? 'hidden' : '' }}" data-preview-text="1">Picture 1</span>
                                </label>
                                <div class="photo-actions">
                                    @if($existingFollowupPicture1Url)
                                        <a href="{{ $existingFollowupPicture1Url }}" target="_blank" rel="noopener" class="photo-view-link" data-preview-view="1">View</a>
                                    @else
                                        <a href="#" class="photo-view-link hidden" data-preview-view="1">View</a>
                                    @endif
                                    <button type="button" class="photo-remove-btn {{ $existingFollowupPicture1Url ? '' : 'hidden' }}" data-preview-remove="1">Remove</button>
                                </div>
                            </div>

                            <div class="photo-tile" data-photo-tile="2" data-existing-url="{{ $existingFollowupPicture2Url ?? '' }}">
                                <input type="hidden" name="followup_remove_picture_2" id="followup_remove_picture_2" value="{{ old('followup_remove_picture_2', '0') }}">
                                <label class="photo-pick" for="followup_picture_2">
                                    <input
                                        type="file"
                                        id="followup_picture_2"
                                        name="followup_picture_2"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        data-preview-input="2"
                                    >
                                    <img
                                        src="{{ $existingFollowupPicture2Url ?? '' }}"
                                        alt="Picture 2 preview"
                                        class="photo-preview {{ $existingFollowupPicture2Url ? '' : 'hidden' }}"
                                        data-preview-img="2"
                                    >
                                    <span class="photo-tile-text {{ $existingFollowupPicture2Url ? 'hidden' : '' }}" data-preview-text="2">Picture 2</span>
                                </label>
                                <div class="photo-actions">
                                    @if($existingFollowupPicture2Url)
                                        <a href="{{ $existingFollowupPicture2Url }}" target="_blank" rel="noopener" class="photo-view-link" data-preview-view="2">View</a>
                                    @else
                                        <a href="#" class="photo-view-link hidden" data-preview-view="2">View</a>
                                    @endif
                                    <button type="button" class="photo-remove-btn {{ $existingFollowupPicture2Url ? '' : 'hidden' }}" data-preview-remove="2">Remove</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <label class="done-label">Interested in Competition</label>
                    <div class="result-segment">
                        <label><input type="radio" name="followup_result" value="active" @checked($selectedResult === 'active')><span>Active</span></label>
                        <label><input type="radio" name="followup_result" value="lost" @checked($selectedResult === 'lost')><span>Lost</span></label>
                        <label><input type="radio" name="followup_result" value="closed" @checked($selectedResult === 'closed')><span>Closed</span></label>
                    </div>
                </div>

                {{-- Active Form --}}
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

                {{-- Lost Form --}}
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

                {{-- Not Done Form --}}
                <div id="notDoneQuestionWrap" class="not-done-question-wrap {{ $selectedFollowupStatus === 'not_done' ? '' : 'hidden' }}">
                    <div class="not-done-form">
                        <label class="not-done-label">Reason for Not Done</label>
                        <div class="not-done-segment">
                            <label class="reason-option" data-reason="I was busy">
                                <input type="radio" name="followup_not_done_reason" value="I was busy" @checked($selectedNotDoneReason === 'I was busy')>
                                <span class="reason-card">
                                    <span class="reason-text">I was busy</span>
                                </span>
                            </label>
                            <label class="reason-option" data-reason="Vehicle was not available">
                                <input type="radio" name="followup_not_done_reason" value="Vehicle was not available" @checked($selectedNotDoneReason === 'Vehicle was not available')>
                                <span class="reason-card">
                                    <span class="reason-text">Vehicle was not available</span>
                                </span>
                            </label>
                            <label class="reason-option" data-reason="Other">
                                <input type="radio" name="followup_not_done_reason" value="Other" @checked($selectedNotDoneReason === 'Other')>
                                <span class="reason-card">
                                    <span class="reason-text">Other</span>
                                </span>
                            </label>
                        </div>
                        
                        <div id="notDoneOtherTextWrap" class="not-done-other-wrap {{ $selectedNotDoneReason === 'Other' ? '' : 'hidden' }}">
                            <label class="not-done-label">Please specify:</label>
                            <input type="text" name="followup_not_done_reason_other" id="followup_not_done_reason_other" class="pill-input" placeholder="Enter other reason" value="{{ $selectedNotDoneReasonOther }}">
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div id="formActions" class="followup-form-actions {{ $selectedFollowupStatus !== 'pending' ? '' : 'hidden' }}">
                    <a href="{{ url('/epr') }}" class="status-btn cancel-btn">Cancel</a>
                    <button type="submit" class="status-btn save-btn">Save</button>
                </div>
            </form>
        </section>
    </main>
</div>

<div id="photoLightbox" class="photo-lightbox hidden" aria-hidden="true">
    <button type="button" id="photoLightboxClose" class="photo-lightbox-close" aria-label="Close image viewer">&times;</button>
    <img id="photoLightboxImage" src="" alt="Follow-up uploaded image">
</div>

<script type="application/json" id="followupCompetitionMapJson">@json($competitionMap)</script>

<style>
/* Not Done Form Styles */
.not-done-question-wrap {
    padding: 14px 16px 16px;
    background: #ffffff;
    border-top: 1px solid #e6e8ef;
}

.not-done-label {
    display: block;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.not-done-segment {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.reason-option {
    flex: 1;
    min-width: 140px;
    cursor: pointer;
}

.reason-option input {
    display: none;
}

.reason-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    background: #f8fafc;
    transition: all 0.2s ease;
    cursor: pointer;
}

.reason-card:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
    transform: translateY(-2px);
}

.reason-option input:checked + .reason-card {
    border-color: #dc2626;
    background: #fef2f2;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
}


.reason-text {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    text-align: center;
}

.reason-option input:checked + .reason-card .reason-text {
    color: #dc2626;
}

.not-done-other-wrap {
    margin-top: 12px;
}

.not-done-other-wrap input {
    width: 100%;
    max-width: 400px;
}

/* Dark mode styles */
html.theme-dark .not-done-question-wrap {
    background: #0f172a;
    border-top-color: #334155;
}

html.theme-dark .not-done-label {
    color: #e2e8f0;
}

html.theme-dark .reason-card {
    background: #1e293b;
    border-color: #334155;
}

html.theme-dark .reason-card:hover {
    background: #334155;
    border-color: #475569;
}

html.theme-dark .reason-text {
    color: #cbd5e1;
}

html.theme-dark .reason-option input:checked + .reason-card {
    border-color: #ef4444;
    background: #2a1a1a;
}

html.theme-dark .reason-option input:checked + .reason-card .reason-text {
    color: #f87171;
}

@media (max-width: 760px) {
    .not-done-segment {
        flex-direction: column;
        gap: 12px;
    }
    
    .reason-card {
        flex-direction: row;
        justify-content: center;
        padding: 12px;
    }
    
}
</style>

<script>
    (function () {
        const statusInput = document.getElementById('followupStatusInput');
        const doneWrap = document.getElementById('doneQuestionWrap');
        const activeWrap = document.getElementById('activeQuestionWrap');
        const lostWrap = document.getElementById('lostQuestionWrap');
        const notDoneWrap = document.getElementById('notDoneQuestionWrap');
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
        
        // Not Done elements
        const notDoneReasonRadios = Array.from(document.querySelectorAll('input[name="followup_not_done_reason"]'));
        const notDoneOtherTextWrap = document.getElementById('notDoneOtherTextWrap');
        const notDoneOtherInput = document.getElementById('followup_not_done_reason_other');
        
        const photoSlots = [1, 2].map((slot) => ({
            slot,
            tile: document.querySelector('[data-photo-tile="' + slot + '"]'),
            input: document.querySelector('[data-preview-input="' + slot + '"]'),
            preview: document.querySelector('[data-preview-img="' + slot + '"]'),
            text: document.querySelector('[data-preview-text="' + slot + '"]'),
            removeBtn: document.querySelector('[data-preview-remove="' + slot + '"]'),
            viewLink: document.querySelector('[data-preview-view="' + slot + '"]'),
            removeFlag: document.getElementById('followup_remove_picture_' + slot),
            objectUrl: null,
            hasPendingFile: false,
        }));
        const photoLightbox = document.getElementById('photoLightbox');
        const photoLightboxImage = document.getElementById('photoLightboxImage');
        const photoLightboxClose = document.getElementById('photoLightboxClose');
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

        function resetObjectUrl(slotState) {
            if (slotState.objectUrl) {
                URL.revokeObjectURL(slotState.objectUrl);
                slotState.objectUrl = null;
            }
        }

        function applyImageState(slotState, sourceUrl, keepAsExisting) {
            if (!slotState.preview || !slotState.text || !slotState.removeBtn || !slotState.viewLink) {
                return;
            }

            const hasImage = !!sourceUrl;

            slotState.preview.src = hasImage ? sourceUrl : '';
            slotState.preview.classList.toggle('hidden', !hasImage);
            slotState.text.classList.toggle('hidden', hasImage);
            slotState.removeBtn.classList.toggle('hidden', !hasImage);

            if (hasImage) {
                slotState.viewLink.classList.remove('hidden');
                slotState.viewLink.href = sourceUrl;
            } else {
                slotState.viewLink.classList.add('hidden');
                slotState.viewLink.href = '#';
            }

            if (keepAsExisting && slotState.removeFlag) {
                slotState.removeFlag.value = '0';
            }
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

        function bindPhotoPreview() {
            photoSlots.forEach((slotState) => {
                if (!slotState.tile || !slotState.input || !slotState.preview || !slotState.text || !slotState.removeBtn || !slotState.viewLink) {
                    return;
                }

                const existingUrl = slotState.tile.dataset.existingUrl || '';
                const shouldStartRemoved = slotState.removeFlag && slotState.removeFlag.value === '1';

                if (shouldStartRemoved) {
                    applyImageState(slotState, '', false);
                } else if (existingUrl) {
                    applyImageState(slotState, existingUrl, true);
                } else {
                    applyImageState(slotState, '', false);
                }

                slotState.input.addEventListener('change', () => {
                    const selectedFile = slotState.input.files && slotState.input.files[0] ? slotState.input.files[0] : null;
                    resetObjectUrl(slotState);

                    if (!selectedFile) {
                        slotState.hasPendingFile = false;
                        if (existingUrl && (!slotState.removeFlag || slotState.removeFlag.value !== '1')) {
                            applyImageState(slotState, existingUrl, false);
                        } else {
                            applyImageState(slotState, '', false);
                        }
                        return;
                    }

                    slotState.objectUrl = URL.createObjectURL(selectedFile);
                    slotState.hasPendingFile = true;
                    if (slotState.removeFlag) {
                        slotState.removeFlag.value = '0';
                    }
                    applyImageState(slotState, slotState.objectUrl, false);
                });

                slotState.removeBtn.addEventListener('click', () => {
                    if (slotState.hasPendingFile) {
                        slotState.input.value = '';
                        slotState.hasPendingFile = false;
                        resetObjectUrl(slotState);

                        if (existingUrl && (!slotState.removeFlag || slotState.removeFlag.value !== '1')) {
                            applyImageState(slotState, existingUrl, false);
                        } else {
                            applyImageState(slotState, '', false);
                        }
                        return;
                    }

                    if (slotState.removeFlag) {
                        slotState.removeFlag.value = '1';
                    }
                    applyImageState(slotState, '', false);
                });
            });
        }

        function openPhotoLightbox(url) {
            if (!photoLightbox || !photoLightboxImage || !url) {
                return;
            }

            photoLightboxImage.src = url;
            photoLightbox.classList.remove('hidden');
            photoLightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closePhotoLightbox() {
            if (!photoLightbox || !photoLightboxImage) {
                return;
            }

            photoLightbox.classList.add('hidden');
            photoLightbox.setAttribute('aria-hidden', 'true');
            photoLightboxImage.src = '';
            document.body.style.overflow = '';
        }

        function bindPhotoViewer() {
            photoSlots.forEach((slotState) => {
                if (!slotState.viewLink) {
                    return;
                }

                slotState.viewLink.addEventListener('click', (event) => {
                    const href = slotState.viewLink.getAttribute('href') || '';
                    if (!href || href === '#') {
                        event.preventDefault();
                        return;
                    }

                    event.preventDefault();
                    openPhotoLightbox(href);
                });
            });

            if (photoLightboxClose) {
                photoLightboxClose.addEventListener('click', closePhotoLightbox);
            }

            if (photoLightbox) {
                photoLightbox.addEventListener('click', (event) => {
                    if (event.target === photoLightbox) {
                        closePhotoLightbox();
                    }
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && photoLightbox && !photoLightbox.classList.contains('hidden')) {
                    closePhotoLightbox();
                }
            });
        }

        function updateNotDoneOtherField() {
            const selectedNotDoneReason = picked('followup_not_done_reason');
            const showOtherField = selectedNotDoneReason === 'Other';
            
            if (notDoneOtherTextWrap) {
                notDoneOtherTextWrap.classList.toggle('hidden', !showOtherField);
            }
            
            // If Other is not selected, clear the other text input
            if (!showOtherField && notDoneOtherInput) {
                notDoneOtherInput.value = '';
            }
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
            
            if (notDoneWrap) {
                notDoneWrap.classList.toggle('hidden', selectedStatus !== 'not_done');
            }

            // Show/hide form action buttons when status is done OR not_done (not pending)
            if (formActions) {
                formActions.classList.toggle('hidden', selectedStatus === 'pending');
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
            
            // Update Not Done other field visibility
            if (selectedStatus === 'not_done') {
                updateNotDoneOtherField();
            }
        }
        
        // Handle form submission to include other text when "Other" is selected
        function prepareFormSubmission() {
            const selectedStatus = statusInput ? statusInput.value : '';
            const selectedNotDoneReason = picked('followup_not_done_reason');
            
            if (selectedStatus === 'not_done' && selectedNotDoneReason === 'Other' && notDoneOtherInput) {
                const otherValue = notDoneOtherInput.value.trim();
                if (otherValue) {
                    // Find the radio with value "Other" and set its value to the custom text
                    const otherRadio = document.querySelector('input[name="followup_not_done_reason"][value="Other"]');
                    if (otherRadio) {
                        otherRadio.value = otherValue;
                    }
                }
            }
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
        
        // Not Done reason radio change handler
        notDoneReasonRadios.forEach((radio) => {
            radio.addEventListener('change', updateNotDoneOtherField);
        });

        if (lostBrandSelect) {
            lostBrandSelect.addEventListener('change', function () {
                if (lostModelSelect) {
                    lostModelSelect.dataset.selectedModel = '';
                }
                syncLostModelOptions();
            });
        }
        
        // Add form submit handler to prepare data
        const followupForm = document.getElementById('followupForm');
        if (followupForm) {
            followupForm.addEventListener('submit', function(e) {
                prepareFormSubmission();
            });
        }

        bindPhotoPreview();
        bindPhotoViewer();
        syncLostModelOptions();
        syncState();
    })();
</script>
@endsection
