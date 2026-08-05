@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/prospect.css') }}?v={{ filemtime(public_path('css/prospect.css')) }}">

@php
    $customer = $enquiry->customer;
    $vehicle = $enquiry->vehicle;
    $mobileString = collect($customer->mobile_numbers ?? [])->implode(', ');
    $selectedQuote = old('quote_taken', $prospect->quote_taken);
    $selectedTestDrive = old('test_drive_given', $prospect->test_drive_given);
    $selectedTestDriveNoReasonRaw = old('test_drive_not_given_reason', $prospect->test_drive_not_given_reason);
    $testDriveNoReasons = [
        'Not interested',
        'Vehicle not available',
        'Vehicle damaged/under repair',
        'Not met in person',
        'Already driven',
        'I Did Not Offer',
        'Others',
    ];
    $isSelectedTestDriveNoReasonOther = $selectedTestDriveNoReasonRaw === 'Others'
        || (trim((string) $selectedTestDriveNoReasonRaw) !== '' && !in_array($selectedTestDriveNoReasonRaw, $testDriveNoReasons, true));
    $selectedTestDriveNoReason = $isSelectedTestDriveNoReasonOther ? 'Others' : $selectedTestDriveNoReasonRaw;
    $selectedTestDriveNoReasonOther = old(
        'test_drive_not_given_reason_other',
        $isSelectedTestDriveNoReasonOther && $selectedTestDriveNoReasonRaw !== 'Others' ? $selectedTestDriveNoReasonRaw : ''
    );
    $selectedPurchaseMode = old('purchase_mode', $prospect->purchase_mode);
    $selectedCompetition = old('interested_in_competition', $prospect->interested_in_competition);
    $selectedFirstTimeBuyer = old('first_time_buyer', $prospect->first_time_buyer);
    $existingVehicleDetails = trim(implode(' ', array_filter([
        old('existing_vehicle_brand', $prospect->existing_vehicle_brand),
        old('existing_vehicle_model', $prospect->existing_vehicle_model),
        old('existing_vehicle_year', $prospect->existing_vehicle_year),
    ])));
    $firstTimeBuyerSummary = $selectedFirstTimeBuyer === 'yes'
        ? 'Yes'
        : ($selectedFirstTimeBuyer === 'no'
            ? 'No' . ($existingVehicleDetails !== '' ? ' - ' . $existingVehicleDetails : ' - Vehicle Details')
            : 'N/A');
    $selectedBrand = old('competition_brand', $prospect->competition_brand);
    $selectedModel = old('competition_model', $prospect->competition_model);
    $selectedExistingBrand = old('existing_vehicle_brand', $prospect->existing_vehicle_brand);
    $selectedExistingModel = old('existing_vehicle_model', $prospect->existing_vehicle_model);
    $selectedExistingYear = old('existing_vehicle_year', $prospect->existing_vehicle_year);
    $selectedCustomerType = old('customer_type', $prospect->customer_type);
    $selectedProfession = old('profession', $prospect->profession);
    $selectedDistrict = old('district', $customer->district);
    $selectedInterestedModel = old('interested_model', $vehicle->model);
    $selectedInterestedEngine = old('interested_engine', $vehicle->engine_type);
    $selectedInterestedVariant = old('interested_variant', $vehicle->variant);
    $selectedTestDriveVehicleRaw = old('test_drive_vehicle_model', $prospect->test_drive_vehicle_model);
    $testDriveVehicleOptions = collect([$selectedInterestedModel])
        ->merge($vehicleModels)
        ->map(fn($model) => trim((string) $model))
        ->filter()
        ->unique()
        ->values();
    $isSelectedTestDriveVehicleOther = $selectedTestDriveVehicleRaw === 'Other'
        || (trim((string) $selectedTestDriveVehicleRaw) !== '' && !$testDriveVehicleOptions->contains($selectedTestDriveVehicleRaw));
    $selectedTestDriveVehicleSelect = $isSelectedTestDriveVehicleOther
        ? 'Other'
        : ($selectedTestDriveVehicleRaw ?: $testDriveVehicleOptions->first());
    $selectedTestDriveVehicleOther = old(
        'test_drive_vehicle_model_other',
        $isSelectedTestDriveVehicleOther && $selectedTestDriveVehicleRaw !== 'Other' ? $selectedTestDriveVehicleRaw : ''
    );
    $selectedInterestedColor = old('interested_vehicle_color', $prospect->interested_vehicle_color);
    $vehicleColorOptions = ['White', 'Black', 'Silver', 'Grey', 'Red', 'Blue', 'Green', 'Brown', 'Orange', 'Other'];
    $selectedLeadSource = old('lead_source', $enquiry->lead_source);
    $selectedSourceOfInformation = old('source_of_information', $prospect->source_of_information ?? $enquiry->source_of_information);
    $selectedInterestedExchange = old('interested_in_exchange', $prospect->interested_in_exchange);
    $hasExistingExchangeImages =
        !empty($prospect->blue_book_image) ||
        !empty($prospect->lot_no_image) ||
        !empty($prospect->car_pic_1_image) ||
        !empty($prospect->car_pic_2_image) ||
        !empty($prospect->exchange_extra_images);
    $isExchangeImageAdd = old('add_exchange_images', $hasExistingExchangeImages ? '1' : '0') === '1';
    $extraExchangeImages = is_array($prospect->exchange_extra_images) ? $prospect->exchange_extra_images : [];
    $selectedExchangeBrand = old('exchange_vehicle_brand', $prospect->exchange_vehicle_brand);
    $selectedExchangeModel = old('exchange_vehicle_model', $prospect->exchange_vehicle_model);
    $selectedExchangeOwnership = old('exchange_ownership', $prospect->exchange_ownership);
    $storedExchangeInsuranceValidity = $prospect->exchange_insurance_validity;
    if ($storedExchangeInsuranceValidity instanceof \Carbon\CarbonInterface) {
        $storedExchangeInsuranceValidity = $storedExchangeInsuranceValidity->format('Y-m-d');
    }
    $selectedExchangeInsuranceValidity = old('exchange_insurance_validity', $storedExchangeInsuranceValidity);
    $selectedExchangeMileageKm = old('exchange_mileage_km', $prospect->exchange_mileage_km);
    $exchangeVehicleLabel = trim(implode(' ', array_filter([$selectedExchangeBrand, $selectedExchangeModel])));
    if ($exchangeVehicleLabel === '') {
        $exchangeVehicleLabel = trim(($vehicle->model ?? '') . ' ' . ($vehicle->variant ?? ''));
    }
    $vehicleUnitPrice = (float) ($vehicle->unit_price ?? 0);
    $vehicleVatAmount = (float) ($vehicle->vat_amount ?? 0);
    $offerUnitPrice = old('offer_unit_price', $prospect->offer_unit_price ?? $vehicleUnitPrice);
    $offerUnitPriceDiscount = old('offer_unit_price_discount', $prospect->offer_unit_price_discount ?? 0);
    $offerUnitPriceFree = old('offer_unit_price_free', (int) ($prospect->offer_unit_price_free ?? 0)) === 1 || old('offer_unit_price_free') === '1';
    $offerVatAmount = old('offer_vat_amount', $prospect->offer_vat_amount ?? $vehicleVatAmount);
    $offerVatDiscount = old('offer_vat_discount', $prospect->offer_vat_discount ?? 0);
    $offerVatFree = old('offer_vat_free', (int) ($prospect->offer_vat_free ?? 0)) === 1 || old('offer_vat_free') === '1';
    $offerTotalCost = old('offer_total_cost', $prospect->offer_total_cost ?? ((float) $offerUnitPrice + (float) $offerVatAmount));
    $offerTotalDiscount = old('offer_total_discount', $prospect->offer_total_discount ?? ((float) $offerUnitPriceDiscount + (float) $offerVatDiscount));
    $offerFinalPrice = old('offer_final_price', $prospect->offer_final_price ?? max(0, (float) $offerTotalCost - (float) $offerTotalDiscount));
    $offerRemark = old('offer_remark', $prospect->offer_remark);
    $hasOfferRemark = trim((string) $offerRemark) !== '';
    $isRescheduleFollowup = old('reschedule_followup', (int) ($prospect->reschedule_followup ?? 0)) === 1 || old('reschedule_followup') === '1';
    $selectedFollowType = old('follow_type', $enquiry->follow_type ?: 'Home Visit');
    $selectedFollowDate = old('follow_date', $enquiry->follow_date);
    $selectedFollowTimeRaw = old('follow_time', $enquiry->follow_time);
    $selectedFollowTime = $selectedFollowTimeRaw ? substr((string) $selectedFollowTimeRaw, 0, 5) : null;
    $rescheduleReason = old('reschedule_reason', $prospect->reschedule_reason);
    $selectedLeadStatus = old('lead_status', $prospect->lead_status);
    $remarkOptions = [
        'Customer visited showroom and is interested in this model.',
        'Customer asked for a better offer and will confirm after discussion.',
        'Customer requested a follow up call after comparing other brands.',
        'Customer wants exchange valuation before final decision.',
        'Customer requested a test drive before booking.',
    ];
    $defaultCustomerRemark = $remarkOptions[0];
    $customerRemark = old('customer_remark', !empty($prospect->customer_remark) ? $prospect->customer_remark : $defaultCustomerRemark);
    $summaryTotalPrice = (float) ($vehicle->unit_price ?? 0) + (float) ($vehicle->vat_amount ?? 0);
    $hasSavedPersonalDetails = !empty($prospect->customer_type) && !empty($prospect->profession);
    $completedProspectStep = (int) ($prospect->current_step ?? 0);
    $isStepCompleted = fn(int $step): bool => $step === 1
        ? $hasSavedPersonalDetails
        : $completedProspectStep >= $step;
    $shouldOpenStep = fn(int $step): bool => !$isStepCompleted($step)
        || ($errors->any() && (int) old('active_step', $initialStep) === $step);
    $shouldOpenPersonalEdit = $shouldOpenStep(1);

    $latestFollowupText = 'No followup scheduled yet';
    if (!empty($enquiry->follow_type) || !empty($enquiry->follow_date) || !empty($enquiry->follow_time)) {
        $datePart = null;
        $timePart = null;

        if (!empty($enquiry->follow_date)) {
            try {
                $datePart = \Carbon\Carbon::parse($enquiry->follow_date)->format('d - M - Y');
            } catch (\Throwable $e) {
                $datePart = (string) $enquiry->follow_date;
            }
        }

        if (!empty($enquiry->follow_time)) {
            try {
                $timePart = \Carbon\Carbon::parse($enquiry->follow_time)->format('h.i a');
            } catch (\Throwable $e) {
                $timePart = substr((string) $enquiry->follow_time, 0, 5);
            }
        }

        $latestFollowupText = trim(implode(' ', array_filter([
            $enquiry->follow_type,
            $datePart,
            $timePart,
        ])));
    }
@endphp

<div class="prospect-page">
    <header class="prospect-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>

        <label class="prospect-top-search" for="prospectSearch">
            <input id="prospectSearch" type="search" placeholder="Search here">
        </label>

        <div class="top-icons-right"></div>
    </header>

    @if(session('success') === 'Prospect sheet submitted successfully.')
        <div class="prospect-submit-popup" id="prospectSubmitPopup" role="dialog" aria-modal="true" aria-labelledby="prospectSubmitTitle">
            <div class="prospect-submit-popup-card">
                <div class="prospect-submit-icon" aria-hidden="true">✓</div>
                <h4 id="prospectSubmitTitle">Prospect Sheet Submitted</h4>
                <p>Prospect sheet submit correctly.</p>
                <button type="button" class="btn btn-primary prospect-submit-popup-btn" id="prospectSubmitPopupOk">OK</button>
            </div>
        </div>
    @elseif(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="flash flash-error">
            <strong>Please check the form:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="stepper" id="stepper">
        @foreach([
            1 => 'Personal Details',
            2 => 'Buying Details',
            3 => 'Exchange Details',
            4 => 'Offer Details',
            5 => 'Plan Followup'
        ] as $index => $label)
            <button type="button" class="stepper-item" data-step-button="{{ $index }}">
                <span class="step-number">{{ str_pad((string)$index, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="step-label">{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="summary-card">
        <div class="summary-row summary-base-row summary-customer-row"><span>Customer Name</span><strong data-summary-field="name">{{ $customer->title }} {{ $customer->name }}</strong></div>
        <div class="summary-row summary-base-row summary-interested-row"><span>Interested In</span><strong data-summary-field="interested_in">{{ $vehicle->model }} {{ $vehicle->variant }}</strong></div>
        <div class="summary-row summary-base-row summary-sc-row"><span>SC Name</span><strong>{{ $enquiry->user?->name ?? 'N/A' }}</strong></div>

        <div class="summary-row summary-detail-row" data-summary-step="1"><span>Contact No</span><strong data-summary-field="mobile_numbers">{{ $mobileString ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="1"><span>Date of Birth</span><strong data-summary-field="date_of_birth">{{ old('date_of_birth', $prospect->date_of_birth) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="1"><span>Address</span><strong data-summary-field="location">{{ trim(($customer->location ?? '') . (($customer->district ?? '') ? ', ' . $customer->district : '')) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="1"><span>Customer Type</span><strong data-summary-field="customer_type">{{ $selectedCustomerType ? ucfirst($selectedCustomerType) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="1"><span>Profession</span><strong data-summary-field="profession">{{ $selectedProfession ? ucwords(str_replace('_', ' ', $selectedProfession)) : 'N/A' }}</strong></div>

        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Color</span><strong data-summary-field="interested_vehicle_color">{{ $selectedInterestedColor ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Lead Source</span><strong data-summary-field="lead_source">{{ trim(($selectedLeadSource ?? '') . (($selectedSourceOfInformation ?? '') ? ' - ' . $selectedSourceOfInformation : '')) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Quote Taken</span><strong data-summary-field="quote_taken">{{ $selectedQuote ? ucfirst($selectedQuote) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Test Drive</span><strong data-summary-field="test_drive_given">{{ $selectedTestDrive ? ucfirst($selectedTestDrive) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Purchase Mode</span><strong data-summary-field="purchase_mode">{{ $selectedPurchaseMode ? ucfirst($selectedPurchaseMode) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>Competition</span><strong data-summary-field="competition">{{ $selectedCompetition ? ucfirst(str_replace('_', ' ', $selectedCompetition)) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="2"><span>First Time Buyer</span><strong data-summary-field="first_time_buyer">{{ $firstTimeBuyerSummary }}</strong></div>

        <div class="summary-row summary-detail-row summary-step3-mobile" data-summary-step="3"><span>Mobile No</span><strong data-summary-field="exchange_mobile">{{ $mobileString ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Exchange?</span><strong data-summary-field="interested_in_exchange">{{ ucfirst($selectedInterestedExchange ?: 'No') }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Exchange Vehicle</span><strong data-summary-field="exchange_vehicle">{{ $exchangeVehicleLabel ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Model Year</span><strong data-summary-field="exchange_year">{{ old('exchange_manufacture_year', $prospect->exchange_manufacture_year) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Ownership</span><strong data-summary-field="exchange_ownership">{{ $selectedExchangeOwnership ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Insurance Validity</span><strong data-summary-field="exchange_insurance_validity">{{ $selectedExchangeInsuranceValidity ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Registration No</span><strong data-summary-field="exchange_registration_no">{{ old('exchange_registration_no', $prospect->exchange_registration_no) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Price</span><strong data-summary-field="exchange_price">{{ old('exchange_expected_price', $prospect->exchange_expected_price) || old('exchange_quoted_price', $prospect->exchange_quoted_price) ? 'Expected: ' . old('exchange_expected_price', $prospect->exchange_expected_price) . ' / Quoted: ' . old('exchange_quoted_price', $prospect->exchange_quoted_price) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="3"><span>Total KM</span><strong data-summary-field="exchange_mileage_km">{{ $selectedExchangeMileageKm ?: 'N/A' }}</strong></div>

        <div class="summary-row summary-detail-row" data-summary-step="4"><span>Unit Price</span><strong data-summary-field="offer_unit_price">{{ number_format((float) $offerUnitPrice, 2) }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="4"><span>VAT</span><strong data-summary-field="offer_vat_amount">{{ number_format((float) $offerVatAmount, 2) }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="4"><span>Total Cost</span><strong data-summary-field="offer_total_cost">{{ number_format((float) $offerTotalCost, 2) }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="4"><span>Total Discount</span><strong data-summary-field="offer_total_discount">{{ number_format((float) $offerTotalDiscount, 2) }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="4"><span>Final Price</span><strong data-summary-field="offer_final_price">{{ number_format((float) $offerFinalPrice, 2) }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="4"><span>Offer Remark</span><strong data-summary-field="offer_remark">{{ $offerRemark ?: 'N/A' }}</strong></div>

        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Latest Followup</span><strong>{{ $latestFollowupText }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Reschedule</span><strong data-summary-field="reschedule_followup">{{ $isRescheduleFollowup ? 'Yes' : 'No' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Followup Plan</span><strong data-summary-field="followup_plan">{{ trim(($selectedFollowType ?? '') . (($selectedFollowDate ?? '') ? ' on ' . $selectedFollowDate : '') . (($selectedFollowTime ?? '') ? ' at ' . $selectedFollowTime : '')) ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Reschedule Reason</span><strong data-summary-field="reschedule_reason">{{ $rescheduleReason ?: 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Lead Status</span><strong data-summary-field="lead_status">{{ $selectedLeadStatus ? ucfirst($selectedLeadStatus) : 'N/A' }}</strong></div>
        <div class="summary-row summary-detail-row" data-summary-step="5"><span>Remark</span><strong data-summary-field="customer_remark">{{ $customerRemark ?: 'N/A' }}</strong></div>
    </div>

    <form method="POST" action="{{ route('prospect.store', $enquiry->id) }}" id="prospectForm" data-initial-step="{{ old('active_step', $initialStep) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="active_step" id="active_step" value="{{ old('active_step', $initialStep) }}">
        <input type="hidden" name="exit_after_save" id="exit_after_save" value="0">
        <section class="prospect-step personal-step step-collapsible {{ $shouldOpenPersonalEdit ? '' : 'step-collapsed personal-collapsed' }}" data-step="1" data-step-completed="{{ $isStepCompleted(1) ? '1' : '0' }}">
            <div class="section-title-line step-edit-line">
                <h3>Personal Details</h3>
                <label class="switch-label">
                    <input type="checkbox" id="allowPersonalEdit" class="step-edit-toggle" data-step-edit-toggle @checked($shouldOpenPersonalEdit)>
                    <span>Edit</span>
                </label>
            </div>

            <div class="personal-edit-body" id="personalEditBody">
                <div class="personal-row personal-row-primary">
                    <div class="field-pill field-pill-title">
                        <label>Title</label>
                        <select name="title" class="lockable lockable-select">
                            @foreach(['Mr', 'Mrs', 'Ms', 'Dr'] as $title)
                                <option value="{{ $title }}" @selected(old('title', $customer->title) === $title)>{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-pill">
                        <label>Customer Name</label>
                        <input class="lockable" type="text" name="name" value="{{ old('name', $customer->name) }}">
                    </div>

                    <div class="field-pill field-pill-dob">
                        <label>DOB</label>
                        <input class="lockable" type="date" name="date_of_birth" value="{{ old('date_of_birth', $prospect->date_of_birth) }}">
                    </div>

                    <div class="field-pill field-pill-contact">
                        <label>Contact No</label>
                        <input type="hidden" name="mobile_numbers" id="prospectMobileNumbers" value="{{ old('mobile_numbers', $mobileString) }}">
                        <div class="contact-list" id="prospectContactList">
                            @foreach(collect(explode(',', old('mobile_numbers', $mobileString)))->map(fn($mobile) => trim($mobile))->filter()->values() as $mobileNumber)
                                <div class="contact-input-wrap">
                                    <input class="lockable prospect-mobile-input" type="text" value="{{ $mobileNumber }}">
                                    <button type="button" class="contact-remove-btn" aria-label="Remove contact">&times;</button>
                                </div>
                            @endforeach
                            @if(collect(explode(',', old('mobile_numbers', $mobileString)))->map(fn($mobile) => trim($mobile))->filter()->isEmpty())
                                <div class="contact-input-wrap">
                                    <input class="lockable prospect-mobile-input" type="text" value="">
                                    <button type="button" class="contact-remove-btn" aria-label="Remove contact">&times;</button>
                                </div>
                            @endif
                        </div>
                        <button type="button" id="addContactNumberBtn" class="contact-add-btn" aria-label="Add contact">+</button>
                    </div>
                </div>

                <div class="personal-row personal-row-secondary">
                    <div class="field-pill">
                        <label>District</label>
                        <select name="district" class="lockable lockable-select">
                            <option value="">Select District</option>
                            @foreach($districtOptions as $districtOption)
                                <option value="{{ $districtOption }}" @selected($selectedDistrict === $districtOption)>{{ $districtOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-pill">
                        <label>Location</label>
                        <input class="lockable" type="text" name="location" value="{{ old('location', $customer->location) }}">
                    </div>
                </div>

                <div class="personal-row personal-row-secondary">
                    <div class="field-pill">
                        <label>State</label>
                        <input class="lockable" type="text" name="state" value="{{ old('state', $customer->state) }}">
                    </div>
                    <div class="field-pill">
                        <label>Address Line 1</label>
                        <input class="lockable" type="text" name="address1" value="{{ old('address1', $customer->address1) }}">
                    </div>
                </div>

                <div class="personal-row personal-row-full">
                    <div class="field-pill">
                        <label>Address Line 2</label>
                        <input class="lockable" type="text" name="address2" value="{{ old('address2', $customer->address2) }}" placeholder="Address Line 2">
                    </div>
                </div>

                <label>Type Of Customer</label>
                <div class="segmented customer-type-segment">
                    <label><input class="lockable-choice" type="radio" name="customer_type" value="individual" @checked($selectedCustomerType === 'individual')><span>Individual</span></label>
                    <label><input class="lockable-choice" type="radio" name="customer_type" value="corporate" @checked($selectedCustomerType === 'corporate')><span>Corporate</span></label>
                </div>

                <div data-conditional="customer_type" data-value="corporate">
                    <label>Corporate Name</label>
                    <input class="lockable" type="text" name="corporate_name" value="{{ old('corporate_name', $prospect->corporate_name) }}">
                </div>

                <label>Profession</label>
                <div class="segmented segmented-4 profession-segment">
                    <label><input class="lockable-choice" type="radio" name="profession" value="salaried" @checked($selectedProfession === 'salaried')><span>Salaried</span></label>
                    <label><input class="lockable-choice" type="radio" name="profession" value="self_employed" @checked($selectedProfession === 'self_employed')><span>Self Employed</span></label>
                    <label><input class="lockable-choice" type="radio" name="profession" value="other" @checked($selectedProfession === 'other')><span>Other</span></label>
                    <label><input class="lockable-choice" type="radio" name="profession" value="not_asked" @checked($selectedProfession === 'not_asked')><span>I Did Not Ask</span></label>
                </div>
            </div>
        </section>

        <section class="prospect-step buying-step step-collapsible {{ $shouldOpenStep(2) ? '' : 'step-collapsed' }}" data-step="2" data-step-completed="{{ $isStepCompleted(2) ? '1' : '0' }}">
            <div class="section-title-line step-edit-line">
                <h3>Buying Details</h3>
                <label class="switch-label">
                    <input type="checkbox" class="step-edit-toggle" data-step-edit-toggle @checked($shouldOpenStep(2))>
                    <span>Edit</span>
                </label>
            </div>

            <label class="buying-field-label">Interested In</label>
            <div class="vehicle-pill">
                {{ $vehicle->model }} / {{ $vehicle->engine_type }} / {{ $vehicle->variant }}
            </div>

            <div class="grid-3" id="interestedVehicleEditFields">
                <div>
                    <label>Vehicle Model</label>
                    <select name="interested_model" id="interested_model" data-selected-model="{{ $selectedInterestedModel }}">
                        <option value="">Select Model</option>
                        @foreach($vehicleModels as $modelItem)
                            <option value="{{ $modelItem }}" @selected($selectedInterestedModel === $modelItem)>{{ $modelItem }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Engine Type</label>
                    <select name="interested_engine" id="interested_engine" data-selected-engine="{{ $selectedInterestedEngine }}">
                        <option value="">Select Engine Type</option>
                    </select>
                </div>
                <div>
                    <label>Variant</label>
                    <select name="interested_variant" id="interested_variant" data-selected-variant="{{ $selectedInterestedVariant }}">
                        <option value="">Select Variant</option>
                    </select>
                </div>
            </div>

            <label class="buying-color-label">Select Color</label>
            <select name="interested_vehicle_color" id="interested_vehicle_color">
                <option value="">Select Color</option>
                @foreach($vehicleColorOptions as $colorOption)
                    <option value="{{ $colorOption }}" @selected($selectedInterestedColor === $colorOption)>{{ $colorOption }}</option>
                @endforeach
            </select>

            <label>Lead Source</label>
            <div class="segmented segmented-6" id="leadSourceGroup">
                @foreach(['Walk-In', 'Tele-In', 'Activity', 'Digital', 'Referral', 'Press'] as $leadSourceOption)
                    <label>
                        <input type="radio" name="lead_source" value="{{ $leadSourceOption }}" @checked($selectedLeadSource === $leadSourceOption)>
                        <span>{{ $leadSourceOption }}</span>
                    </label>
                @endforeach
            </div>

            <label>Source of Information</label>
            <select name="source_of_information" id="source_of_information" data-selected-source-info="{{ $selectedSourceOfInformation }}">
                <option value="">Select Source of Information</option>
            </select>

            <label>Did the customer take a quote?</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="quote_taken" value="yes" @checked($selectedQuote === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="quote_taken" value="no" @checked($selectedQuote === 'no')><span>No</span></label>
            </div>

            <div class="buying-quote-when" data-conditional="quote_taken" data-value="yes">
                <label>When?</label>
                <input type="date" name="quote_date" value="{{ old('quote_date', $prospect->quote_date) }}">
            </div>

            <label>Test Drive Given?</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="test_drive_given" value="yes" @checked($selectedTestDrive === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="test_drive_given" value="no" @checked($selectedTestDrive === 'no')><span>No</span></label>
            </div>

            <div class="buying-test-yes" data-conditional="test_drive_given" data-value="yes">
                <div>
                    <label>When?</label>
                    <input type="date" name="test_drive_date" value="{{ old('test_drive_date', $prospect->test_drive_date) }}">
                </div>

                <div class="buying-test-vehicle-field">
                    <label>Vehicle Used?</label>
                    <select name="test_drive_vehicle_model" id="prospectTestDriveVehicleSelect">
                        <option value="">Select Vehicle</option>
                        @foreach($testDriveVehicleOptions as $modelOption)
                            <option value="{{ $modelOption }}" @selected($selectedTestDriveVehicleSelect === $modelOption)>{{ $modelOption }}</option>
                        @endforeach
                        <option value="Other" @selected($selectedTestDriveVehicleSelect === 'Other')>Other</option>
                    </select>
                    <div id="prospectTestDriveVehicleOtherWrap" style="{{ $selectedTestDriveVehicleSelect === 'Other' ? '' : 'display:none;' }}">
                        <label>Other Details</label>
                        <input type="text" name="test_drive_vehicle_model_other" value="{{ $selectedTestDriveVehicleOther }}" placeholder="Enter vehicle details">
                    </div>
                </div>
            </div>

            <div class="buying-test-no" data-conditional="test_drive_given" data-value="no">
                <label>Why Not Given?</label>
                <select name="test_drive_not_given_reason" id="prospectTestDriveNoReasonSelect">
                    <option value="">Select reason</option>
                    @foreach($testDriveNoReasons as $reasonOption)
                        <option value="{{ $reasonOption }}" @selected($selectedTestDriveNoReason === $reasonOption)>{{ $reasonOption }}</option>
                    @endforeach
                </select>
                <div id="prospectTestDriveNoReasonOtherWrap" style="{{ $selectedTestDriveNoReason === 'Others' ? '' : 'display:none;' }}">
                    <label>Other Details</label>
                    <input type="text" name="test_drive_not_given_reason_other" value="{{ $selectedTestDriveNoReasonOther }}" placeholder="Enter reason">
                </div>
            </div>

            <label>Mode of Purchase</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="purchase_mode" value="cash" @checked($selectedPurchaseMode === 'cash')><span>Cash</span></label>
                <label><input type="radio" name="purchase_mode" value="finance" @checked($selectedPurchaseMode === 'finance')><span>Finance</span></label>
            </div>

            <label>Interested in Competition</label>
            <div class="segmented segmented-3 buying-segment-3">
                <label><input type="radio" name="interested_in_competition" value="yes" @checked($selectedCompetition === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="interested_in_competition" value="no" @checked($selectedCompetition === 'no')><span>No</span></label>
                <label><input type="radio" name="interested_in_competition" value="not_asked" @checked($selectedCompetition === 'not_asked')><span>I Did Not Ask</span></label>
            </div>

            <div class="grid-2 buying-competition-grid" data-conditional="interested_in_competition" data-value="yes">
                <div>
                    <label>Vehicle Brand</label>
                    <select name="competition_brand" id="competition_brand">
                        <option value="">Select Brand</option>
                        @foreach($competitionMap->keys() as $brand)
                            <option value="{{ $brand }}" @selected($selectedBrand === $brand)>{{ strtoupper($brand) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Vehicle Model</label>
                    <select name="competition_model" id="competition_model" data-selected-model="{{ $selectedModel ?? '' }}">
                        <option value="">Select Model</option>
                    </select>
                </div>
            </div>

            <label>First time buyer?</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="first_time_buyer" value="no" @checked($selectedFirstTimeBuyer === 'no')><span>No</span></label>
            </div>

            <div class="grid-3 buying-existing-grid" data-conditional="first_time_buyer" data-value="no">
                <div>
                    <label>Existing Vehicle Brand</label>
                    <select name="existing_vehicle_brand" id="existing_vehicle_brand">
                        <option value="">Select Brand</option>
                        @foreach($competitionMap->keys() as $brand)
                            <option value="{{ $brand }}" @selected($selectedExistingBrand === $brand)>{{ strtoupper($brand) }}</option>
                        @endforeach
                        @if(!empty($selectedExistingBrand) && !$competitionMap->has($selectedExistingBrand))
                            <option value="{{ $selectedExistingBrand }}" selected>{{ strtoupper($selectedExistingBrand) }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label>Existing Vehicle Model</label>
                    <select name="existing_vehicle_model" id="existing_vehicle_model" data-selected-model="{{ $selectedExistingModel }}">
                        <option value="">Select Model</option>
                    </select>
                </div>
                <div>
                    <label>Year</label>
                    <select name="existing_vehicle_year">
                        <option value="">Model year</option>
                        @for($year = now()->year + 1; $year >= 1950; $year--)
                            <option value="{{ $year }}" @selected((string) $selectedExistingYear === (string) $year)>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </section>
        <section class="prospect-step exchange-step step-collapsible {{ $shouldOpenStep(3) ? '' : 'step-collapsed' }}" data-step="3" data-step-completed="{{ $isStepCompleted(3) ? '1' : '0' }}">
            <div class="section-title-line step-edit-line">
                <h3>Exchange Details</h3>
                <label class="switch-label">
                    <input type="checkbox" class="step-edit-toggle" data-step-edit-toggle @checked($shouldOpenStep(3))>
                    <span>Edit</span>
                </label>
            </div>
            <label class="exchange-question-label">Interested in Exchange?</label>
            <div class="segmented exchange-interest-segment">
                <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange === 'no')><span>No</span></label>
            </div>

            <div class="exchange-detail-wrap" data-conditional="interested_in_exchange" data-value="yes">
                <div class="exchange-card-head">
                    <h3>Exchange Details</h3>
                    <label class="exchange-inline-edit">
                        <input type="checkbox" checked>
                        <span>Edit</span>
                    </label>
                </div>
                <div class="exchange-selected-row">
                    <input class="exchange-interested-input" type="text" value="{{ strtoupper($exchangeVehicleLabel) }}" readonly>
                </div>

                @php
                    $exchangeYearSelected = old('exchange_manufacture_year', $prospect->exchange_manufacture_year);
                @endphp

                <div class="grid-2 exchange-input-grid">
                    <div>
                        <label>Select Brand</label>
                        <select name="exchange_vehicle_brand" id="exchange_vehicle_brand">
                            <option value="">Select Brand</option>
                            @foreach($competitionMap->keys() as $brand)
                                <option value="{{ $brand }}" @selected($selectedExchangeBrand === $brand)>{{ strtoupper($brand) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Select Model</label>
                        <select name="exchange_vehicle_model" id="exchange_vehicle_model" data-selected-model="{{ $selectedExchangeModel ?? '' }}">
                            <option value="">Select Model</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2 exchange-input-grid">
                    <div>
                        <label>Model Year</label>
                        <select name="exchange_manufacture_year">
                            <option value="">Model Year</option>
                            @for($year = now()->year + 1; $year >= 1950; $year--)
                                <option value="{{ $year }}" @selected((string) $exchangeYearSelected === (string) $year)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label>Select Ownership</label>
                        <select name="exchange_ownership">
                            <option value="">Select</option>
                            <option value="1st Owner" @selected($selectedExchangeOwnership === '1st Owner')>1st Owner</option>
                            <option value="2nd Owner" @selected($selectedExchangeOwnership === '2nd Owner')>2nd Owner</option>
                            <option value="3rd Owner" @selected($selectedExchangeOwnership === '3rd Owner')>3rd Owner</option>
                        </select>
                    </div>
                </div>

                <div class="exchange-wide-field">
                    <label>Insurance Validity</label>
                    <input type="date" name="exchange_insurance_validity" value="{{ $selectedExchangeInsuranceValidity }}">
                </div>

                <div class="exchange-tyre-row">
                    <label>Tyre Replacement</label>
                    <label class="exchange-mini-switch">
                        <input type="checkbox" checked>
                        <i aria-hidden="true"></i>
                    </label>
                </div>

                <div class="segmented exchange-tyre-segment segmented-4">
                    <label><input type="checkbox"><span>Front LHS</span></label>
                    <label><input type="checkbox" checked><span>Front RHS</span></label>
                    <label><input type="checkbox"><span>Rear LHS</span></label>
                    <label><input type="checkbox" checked><span>Rear RHS</span></label>
                </div>

                <div class="grid-2 exchange-input-grid">
                    <div>
                        <label>Color</label>
                        <input type="text" name="exchange_color" value="{{ old('exchange_color', $prospect->exchange_color) }}">
                    </div>
                    <div>
                        <label>Total KM</label>
                        <input type="number" name="exchange_mileage_km" min="0" value="{{ $selectedExchangeMileageKm }}">
                    </div>
                </div>

                <div class="exchange-wide-field">
                    <label>Registration No</label>
                    <input type="text" name="exchange_registration_no" value="{{ old('exchange_registration_no', $prospect->exchange_registration_no) }}">
                </div>

                <h4 class="sub-title exchange-price-title">Price details</h4>
                <div class="grid-3 exchange-price-grid">
                    <div>
                        <input type="number" step="0.01" min="0" name="exchange_expected_price" value="{{ old('exchange_expected_price', $prospect->exchange_expected_price) }}" placeholder="Expected price">
                    </div>
                    <div>
                        <input type="number" step="0.01" min="0" name="exchange_quoted_price" value="{{ old('exchange_quoted_price', $prospect->exchange_quoted_price) }}" placeholder="Quoted Price">
                    </div>
                    <div>
                        <input class="exchange-difference-input" type="number" step="0.01" name="exchange_price_difference" readonly value="{{ old('exchange_price_difference', $prospect->exchange_price_difference) }}" placeholder="Difference">
                    </div>
                </div>

                <div class="section-title-line exchange-switch-row">
                    <label>Add images</label>
                    <label class="switch-label exchange-switch">
                        <input type="checkbox" id="addExchangeImages" name="add_exchange_images" value="1" @checked($isExchangeImageAdd)>
                        <span></span>
                    </label>
                </div>

                <div id="exchangeImageFields" class="exchange-images-wrap">
                    <div class="exchange-upload-grid">
                        <div class="exchange-upload-field">
                            <label class="exchange-upload-tile" data-upload-tile>
                                <span class="exchange-upload-text">Blue Book</span>
                                <img class="exchange-upload-preview" alt="Blue Book preview" hidden>
                                <input
                                    type="file"
                                    name="blue_book_image"
                                    accept="image/*"
                                    data-existing-src="{{ !empty($prospect->blue_book_image) ? asset('storage/' . $prospect->blue_book_image) : '' }}"
                                >
                            </label>
                            <input type="hidden" name="remove_blue_book_image" value="0" data-exchange-remove-input>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                            @error('blue_book_image')
                                <small class="exchange-upload-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="exchange-upload-field">
                            <label class="exchange-upload-tile" data-upload-tile>
                                <span class="exchange-upload-text">Lot No</span>
                                <img class="exchange-upload-preview" alt="Lot No preview" hidden>
                                <input
                                    type="file"
                                    name="lot_no_image"
                                    accept="image/*"
                                    data-existing-src="{{ !empty($prospect->lot_no_image) ? asset('storage/' . $prospect->lot_no_image) : '' }}"
                                >
                            </label>
                            <input type="hidden" name="remove_lot_no_image" value="0" data-exchange-remove-input>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                            @error('lot_no_image')
                                <small class="exchange-upload-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="exchange-upload-field">
                            <label class="exchange-upload-tile" data-upload-tile>
                                <span class="exchange-upload-text">Car picture 1</span>
                                <img class="exchange-upload-preview" alt="Car picture 1 preview" hidden>
                                <input
                                    type="file"
                                    name="car_pic_1_image"
                                    accept="image/*"
                                    data-existing-src="{{ !empty($prospect->car_pic_1_image) ? asset('storage/' . $prospect->car_pic_1_image) : '' }}"
                                >
                            </label>
                            <input type="hidden" name="remove_car_pic_1_image" value="0" data-exchange-remove-input>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                            @error('car_pic_1_image')
                                <small class="exchange-upload-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="exchange-upload-field">
                            <label class="exchange-upload-tile" data-upload-tile>
                                <span class="exchange-upload-text">Car picture 2</span>
                                <img class="exchange-upload-preview" alt="Car picture 2 preview" hidden>
                                <input
                                    type="file"
                                    name="car_pic_2_image"
                                    accept="image/*"
                                    data-existing-src="{{ !empty($prospect->car_pic_2_image) ? asset('storage/' . $prospect->car_pic_2_image) : '' }}"
                                >
                            </label>
                            <input type="hidden" name="remove_car_pic_2_image" value="0" data-exchange-remove-input>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                            @error('car_pic_2_image')
                                <small class="exchange-upload-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    @error('extra_exchange_images')
                        <small class="exchange-upload-error exchange-upload-error-wide">{{ $message }}</small>
                    @enderror
                    @foreach($errors->get('extra_exchange_images.*') as $extraImageErrors)
                        @foreach($extraImageErrors as $extraImageError)
                            <small class="exchange-upload-error exchange-upload-error-wide">{{ $extraImageError }}</small>
                        @endforeach
                    @endforeach

                    @if(!empty($prospect->blue_book_image) || !empty($prospect->lot_no_image) || !empty($prospect->car_pic_1_image) || !empty($prospect->car_pic_2_image))
                        <div class="existing-files">
                            <small>Previously uploaded images are saved.</small>
                        </div>
                    @endif

                    <div class="section-title-line exchange-switch-row exchange-more-row">
                        <label>Additional Images</label>
                        <button type="button" class="exchange-more-btn" id="addMoreExchangeImagesBtn" aria-label="Add more images">+</button>
                    </div>

                    <div id="extraExchangeImagesContainer" class="exchange-upload-grid exchange-upload-grid-extra">
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra" data-upload-tile>
                                <span class="exchange-upload-text">Car Picture 3</span>
                                <img class="exchange-upload-preview" alt="Image 1 preview" hidden>
                                <button type="button" class="extra-image-remove-top" aria-label="Remove image slot">-</button>
                                <input type="file" name="extra_exchange_images[]" accept="image/*">
                            </label>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                        </div>
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra" data-upload-tile>
                                <span class="exchange-upload-text">Car Picture 4</span>
                                <img class="exchange-upload-preview" alt="Image 2 preview" hidden>
                                <button type="button" class="extra-image-remove-top" aria-label="Remove image slot">-</button>
                                <input type="file" name="extra_exchange_images[]" accept="image/*">
                            </label>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                        </div>
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra" data-upload-tile>
                                <span class="exchange-upload-text">Car Picture 5</span>
                                <img class="exchange-upload-preview" alt="Image 3 preview" hidden>
                                <button type="button" class="extra-image-remove-top" aria-label="Remove image slot">-</button>
                                <input type="file" name="extra_exchange_images[]" accept="image/*">
                            </label>
                            <div class="exchange-upload-actions">
                                <button type="button" data-exchange-upload-action="choose">Add</button>
                                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
                            </div>
                        </div>
                    </div>

                    @if(!empty($extraExchangeImages))
                        <div class="existing-files">
                            <small>{{ count($extraExchangeImages) }} extra image(s) already uploaded.</small>
                        </div>
                        <div class="exchange-existing-preview-grid">
                            @foreach($extraExchangeImages as $index => $extraImagePath)
                                @if(!empty($extraImagePath))
                                    <div class="exchange-existing-preview-item" data-existing-extra-image>
                                        <img src="{{ asset('storage/' . $extraImagePath) }}" alt="Existing extra exchange image {{ $index + 1 }}">
                                        <input type="checkbox" name="remove_extra_exchange_images[]" value="{{ $extraImagePath }}" hidden>
                                        <div class="exchange-upload-actions">
                                            <button type="button" data-existing-extra-action="view" data-image-src="{{ asset('storage/' . $extraImagePath) }}">View</button>
                                            <button type="button" data-existing-extra-action="remove">Remove</button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
        <section class="prospect-step offer-step" data-step="4" data-step-completed="{{ $isStepCompleted(4) ? '1' : '0' }}">
            <div class="section-title-line offer-title-line">
                <label class="switch-label offer-edit-label">
                    <input type="checkbox" id="allowOfferEdit" checked>
                    <span>Edit Buying Details</span>
                </label>
            </div>

            <div class="offer-page-summary">
                <h3>SUMMARY</h3>
                <p>Customer Name: <strong data-summary-field="name">{{ $customer->title }} {{ $customer->name }}</strong></p>
                <p>Interested in: <strong data-summary-field="interested_in">{{ $vehicle->model }} {{ $vehicle->variant }}</strong></p>
            </div>

            <div class="offer-summary-panel offer-hidden" id="prospectOfferSummaryPanel">
                <div class="offer-summary-table">
                    <div class="offer-summary-head">
                        <span></span>
                        <span>Cost</span>
                        <span>Offer</span>
                        <span>Payable</span>
                    </div>
                    <div class="offer-summary-row">
                        <strong>Vat</strong>
                        <span id="prospectOfferSummaryVatCost">0</span>
                        <span id="prospectOfferSummaryVatOffer">0</span>
                        <span id="prospectOfferSummaryVatPayable">0</span>
                    </div>
                    <div class="offer-summary-row">
                        <strong>Unit price (without vat)</strong>
                        <span id="prospectOfferSummaryUnitCost">0</span>
                        <span id="prospectOfferSummaryUnitOffer">0</span>
                        <span id="prospectOfferSummaryUnitPayable">0</span>
                    </div>
                    <div class="offer-summary-total">
                        <strong>Total</strong>
                        <span id="prospectOfferSummaryTotalCost">0</span>
                        <span id="prospectOfferSummaryTotalOffer">0</span>
                        <span id="prospectOfferSummaryFinalPrice">0</span>
                    </div>
                </div>
            </div>

            <div class="offer-remarks">
                <label class="offer-remarks-toggle">
                    <span>Add Remarks</span>
                    <input type="checkbox" id="offerRemarksToggle" @checked($hasOfferRemark)>
                    <i></i>
                </label>
                <textarea id="offerRemarksText" name="offer_remark" placeholder="Type comment here......">{{ $offerRemark }}</textarea>
            </div>

            <div class="offer-edit-group" id="prospectOfferEditGroup">
                <div class="offer-panel-card">
                    <div class="offer-panel-head">
                        <span>Unit price (without vat)</span>
                    </div>

                    <div class="offer-price-value-row">
                        <input type="number" name="offer_unit_price" id="offer_unit_price" step="0.01" min="0" readonly value="{{ $offerUnitPrice }}">
                    </div>

                    <div class="offer-meta-row">
                        <label class="offer-free-check">
                            <input type="hidden" name="offer_unit_price_free" value="0">
                            <input type="checkbox" name="offer_unit_price_free" id="offer_unit_price_free" value="1" @checked($offerUnitPriceFree)>
                            <span>Free</span>
                        </label>
                        <span class="offer-discount-label">Discount</span>
                        <input type="number" name="offer_unit_price_discount" id="offer_unit_price_discount" step="0.01" min="0" value="{{ $offerUnitPriceDiscount }}" placeholder="Discount">
                    </div>
                </div>

                <div class="offer-panel-card">
                    <div class="offer-panel-head">
                        <span>Vat</span>
                    </div>

                    <div class="offer-price-value-row">
                        <input type="number" name="offer_vat_amount" id="offer_vat_amount" step="0.01" min="0" readonly value="{{ $offerVatAmount }}">
                    </div>

                    <div class="offer-meta-row">
                        <label class="offer-free-check">
                            <input type="hidden" name="offer_vat_free" value="0">
                            <input type="checkbox" name="offer_vat_free" id="offer_vat_free" value="1" @checked($offerVatFree)>
                            <span>Free</span>
                        </label>
                        <span class="offer-discount-label">Discount</span>
                        <input type="number" name="offer_vat_discount" id="offer_vat_discount" step="0.01" min="0" value="{{ $offerVatDiscount }}" placeholder="Discount">
                    </div>
                </div>

                <div class="offer-total-strip">
                    <div class="offer-total-strip-head">
                        <span>Total</span>
                        <span>Cost</span>
                        <span>Offer</span>
                        <span>Final Offer Price</span>
                    </div>
                    <div class="offer-total-strip-values">
                        <span></span>
                        <strong id="offerTotalCostDisplay">{{ number_format((float) $offerTotalCost, 2, '.', '') }}</strong>
                        <strong id="offerTotalDiscountDisplay">{{ number_format((float) $offerTotalDiscount, 2, '.', '') }}</strong>
                        <strong id="offerFinalPriceDisplay">{{ number_format((float) $offerFinalPrice, 2, '.', '') }}</strong>
                    </div>
                </div>
            </div>

            <input type="hidden" name="offer_total_cost" id="offer_total_cost" value="{{ $offerTotalCost }}">
            <input type="hidden" name="offer_total_discount" id="offer_total_discount" value="{{ $offerTotalDiscount }}">
            <input type="hidden" name="offer_final_price" id="offer_final_price" value="{{ $offerFinalPrice }}">
        </section>
        <section class="prospect-step plan-step step-collapsible {{ $shouldOpenStep(5) ? '' : 'step-collapsed' }}" data-step="5" data-step-completed="{{ $isStepCompleted(5) ? '1' : '0' }}">
            <div class="section-title-line step-edit-line">
                <h3>Plan Followup</h3>
                <label class="switch-label">
                    <input type="checkbox" class="step-edit-toggle" data-step-edit-toggle @checked($shouldOpenStep(5))>
                    <span>Edit</span>
                </label>
            </div>

            <div class="plan-top-row">
                <div class="plan-latest-pill">
                    <small>Latest followup</small>
                    <strong>{{ $latestFollowupText }}</strong>
                </div>

                <label class="switch-label plan-reschedule-toggle">
                    <span>Reschedule</span>
                    <input type="hidden" name="reschedule_followup" value="0">
                    <input type="checkbox" id="rescheduleFollowupToggle" name="reschedule_followup" value="1" @checked($isRescheduleFollowup)>
                    <i></i>
                </label>
            </div>

            <div id="rescheduleFields" class="plan-reschedule-fields" style="display: none;">
                <label class="plan-follow-type-label">Plan Follow Up</label>
                <div class="segmented segmented-3 plan-follow-type-segment">
                    <label><input type="radio" name="follow_type" value="Home Visit" @checked($selectedFollowType === 'Home Visit')><span>Home Visit</span></label>
                    <label><input type="radio" name="follow_type" value="Showroom Visit" @checked($selectedFollowType === 'Showroom Visit')><span>Showroom Visit</span></label>
                    <label><input type="radio" name="follow_type" value="Call" @checked($selectedFollowType === 'Call')><span>Call</span></label>
                </div>

                <div class="plan-schedule-grid">
                    <div class="plan-schedule-field">
                        <label>Follow up date</label>
                        <div class="plan-schedule-input-wrap">
                            <input type="date" name="follow_date" value="{{ $selectedFollowDate }}">
                            <span class="plan-input-icon calendar" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="plan-schedule-field">
                        <label>Follow up time</label>
                        <div class="plan-schedule-input-wrap">
                            <input type="time" name="follow_time" value="{{ $selectedFollowTime }}">
                            <span class="plan-input-icon clock" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>

                <div class="plan-reschedule-reason-field">
                    <label>Reason</label>
                    <textarea name="reschedule_reason" placeholder="Enter reschedule reason">{{ $rescheduleReason }}</textarea>
                    @error('reschedule_reason')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <label class="plan-question">What according to you is the lead status?</label>
            <div class="plan-status-row">
                <label class="plan-status-item hot">
                    <input type="radio" name="lead_status" value="hot" @checked($selectedLeadStatus === 'hot')>
                    <span class="face">
                        <img src="{{ asset('icons/hot.png') }}" alt="Hot status" class="plan-status-icon">
                    </span>
                    <em>Hot</em>
                </label>
                <label class="plan-status-item warm">
                    <input type="radio" name="lead_status" value="warm" @checked($selectedLeadStatus === 'warm')>
                    <span class="face">
                        <img src="{{ asset('icons/warm.png') }}" alt="Warm status" class="plan-status-icon">
                    </span>
                    <em>Warm</em>
                </label>
                <label class="plan-status-item cold">
                    <input type="radio" name="lead_status" value="cold" @checked($selectedLeadStatus === 'cold')>
                    <span class="face">
                        <img src="{{ asset('icons/cold.png') }}" alt="Cold status" class="plan-status-icon">
                    </span>
                    <em>Cold</em>
                </label>
            </div>

            <div class="plan-remark-wrap">
                <label>Add customer remark here*</label>
                <textarea id="customerRemarkPreset" name="customer_remark" class="plan-remark-select" placeholder="Enter Customer remark here........">{{ $customerRemark }}</textarea>
            </div>
        </section>

        <div class="summary-modal" id="offerSummaryModal">
            <div class="summary-modal-card">
                <button type="button" class="summary-modal-close" id="summaryModalCloseBtn" aria-label="Close summary">×</button>
                <h3>SUMMARY</h3>

                <div class="summary-modern-vehicle">
                    <p>Customer Name: {{ $customer->title }} {{ $customer->name }}</p>
                    <p>Interested in: <strong id="summaryInterestedVehicle">{{ $vehicle->model }} {{ $vehicle->variant }}</strong></p>
                </div>

                <div class="summary-modern-grid-head">
                    <span></span>
                    <span>Cost</span>
                    <span>Offer</span>
                    <span>Payable</span>
                </div>

                <div class="summary-modern-row">
                    <span class="summary-modern-label">VAT</span>
                    <strong id="summaryVatCost">0</strong>
                    <strong id="summaryVatOffer">0</strong>
                    <strong id="summaryVatPayable">0</strong>
                </div>

                <div class="summary-modern-row">
                    <span class="summary-modern-label">Unit price (without vat)</span>
                    <strong id="summaryUnitCost">0</strong>
                    <strong id="summaryUnitOffer">0</strong>
                    <strong id="summaryUnitPayable">0</strong>
                </div>

                <div class="summary-modern-total">
                    <span>Total</span>
                    <strong id="summaryTotalCost">0</strong>
                    <strong id="summaryTotalOffer">0</strong>
                    <strong id="summaryFinalPrice">0</strong>
                </div>

                <button type="button" class="btn btn-primary summary-confirm-btn" id="summaryLooksGoodBtn">OK</button>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-secondary" id="backBtn">Back</button>
            <button type="button" class="btn btn-secondary" id="saveExitBtn">Save & Exit</button>
            <button type="button" class="btn btn-primary" id="nextBtn">Save &amp; Next</button>
        </div>
    </form>
</div>

<script>
    window.PROSPECT_COMPETITION_MAP = @json($competitionMap);
    window.PROSPECT_SOURCE_INFO_MAP = @json($sourceInfoMap);
</script>
<script src="{{ asset('js/prospect.js') }}?v={{ filemtime(public_path('js/prospect.js')) }}"></script>
<script>
    (() => {
        const popup = document.getElementById('prospectSubmitPopup');
        if (!popup) {
            return;
        }

        const closeBtn = document.getElementById('prospectSubmitPopupOk');
        const eprUrl = @json(url('/epr'));
        const closePopup = () => popup.classList.add('hidden');

        closeBtn?.addEventListener('click', () => {
            window.location.href = eprUrl;
        });
        popup.addEventListener('click', (event) => {
            if (event.target === popup) {
                closePopup();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePopup();
            }
        });
    })();
</script>
@endsection
