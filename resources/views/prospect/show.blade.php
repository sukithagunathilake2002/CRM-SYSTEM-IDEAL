@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/prospect.css') }}">

@php
    $customer = $enquiry->customer;
    $vehicle = $enquiry->vehicle;
    $mobileString = collect($customer->mobile_numbers ?? [])->implode(', ');
    $selectedQuote = old('quote_taken', $prospect->quote_taken);
    $selectedTestDrive = old('test_drive_given', $prospect->test_drive_given);
    $selectedPurchaseMode = old('purchase_mode', $prospect->purchase_mode);
    $selectedCompetition = old('interested_in_competition', $prospect->interested_in_competition);
    $selectedFirstTimeBuyer = old('first_time_buyer', $prospect->first_time_buyer);
    $selectedBrand = old('competition_brand', $prospect->competition_brand);
    $selectedModel = old('competition_model', $prospect->competition_model);
    $selectedCustomerType = old('customer_type', $prospect->customer_type);
    $selectedProfession = old('profession', $prospect->profession);
    $isInterestedVehicleEdit = old('edit_interested_vehicle') === '1';
    $selectedInterestedModel = old('interested_model', $vehicle->model);
    $selectedInterestedEngine = old('interested_engine', $vehicle->engine_type);
    $selectedInterestedVariant = old('interested_variant', $vehicle->variant);
    $selectedInterestedColor = old('interested_vehicle_color', $prospect->interested_vehicle_color);
    $vehicleColorOptions = ['White', 'Black', 'Silver', 'Grey', 'Red', 'Blue', 'Green', 'Brown', 'Orange', 'Other'];
    $selectedLeadSource = old('lead_source', $enquiry->lead_source);
    $selectedSourceOfInformation = old('source_of_information', $prospect->source_of_information);
    $selectedInterestedExchange = old('interested_in_exchange', $prospect->interested_in_exchange);
    $hasExistingExchangeImages =
        !empty($prospect->blue_book_image) ||
        !empty($prospect->lot_no_image) ||
        !empty($prospect->car_pic_1_image) ||
        !empty($prospect->car_pic_2_image) ||
        !empty($prospect->exchange_extra_images);
    $isExchangeImageAdd = old('add_exchange_images', $hasExistingExchangeImages ? '1' : '0') === '1';
    $extraExchangeImages = is_array($prospect->exchange_extra_images) ? $prospect->exchange_extra_images : [];
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
    $isRescheduleFollowup = old('reschedule_followup', (int) ($prospect->reschedule_followup ?? 0)) === 1 || old('reschedule_followup') === '1';
    $selectedFollowType = old('follow_type', $enquiry->follow_type ?: 'Home Visit');
    $selectedFollowDate = old('follow_date', $enquiry->follow_date);
    $selectedFollowTimeRaw = old('follow_time', $enquiry->follow_time);
    $selectedFollowTime = $selectedFollowTimeRaw ? substr((string) $selectedFollowTimeRaw, 0, 5) : null;
    $selectedLeadStatus = old('lead_status', $prospect->lead_status);
    $customerRemark = old('customer_remark', $prospect->customer_remark);

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

        <div class="top-icons-right"></div>
    </header>

    @if(session('success'))
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
        <div class="summary-row"><span>Customer Name</span><strong>{{ $customer->title }} {{ $customer->name }}</strong></div>
        <div class="summary-row"><span>Interested In</span><strong>{{ $vehicle->model }} {{ $vehicle->variant }}</strong></div>
        <div class="summary-row"><span>Mobile No.</span><strong>{{ $mobileString }}</strong></div>
        <div class="summary-row"><span>DMS ID</span><strong>ENQ-{{ $enquiry->id }}</strong></div>
        <div class="summary-row"><span>SC Name</span><strong>N/A</strong></div>
    </div>

    <form method="POST" action="{{ route('prospect.store', $enquiry->id) }}" id="prospectForm" data-initial-step="{{ old('active_step', $initialStep) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="active_step" id="active_step" value="{{ old('active_step', $initialStep) }}">
        <input type="hidden" name="exit_after_save" id="exit_after_save" value="0">
        <section class="prospect-step personal-step" data-step="1">
            <div class="section-title-line">
                <h3>Personal Details</h3>
                <label class="switch-label">
                    <input type="checkbox" id="allowPersonalEdit">
                    <span>Edit</span>
                </label>
            </div>

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
                    <label>Name</label>
                    <input class="lockable" type="text" name="name" value="{{ old('name', $customer->name) }}">
                </div>

                <div class="field-pill field-pill-dob">
                    <label>DOB</label>
                    <input class="lockable" type="date" name="date_of_birth" value="{{ old('date_of_birth', $prospect->date_of_birth) }}">
                </div>

                <div class="field-pill field-pill-contact">
                    <label>Contact No</label>
                    <div class="contact-input-wrap">
                        <input class="lockable" type="text" name="mobile_numbers" value="{{ old('mobile_numbers', $mobileString) }}">
                        <button type="button" id="addContactNumberBtn" class="contact-add-btn" aria-label="Add contact">+</button>
                    </div>
                </div>
            </div>

            <div class="personal-row personal-row-secondary">
                <div class="field-pill">
                    <label>District</label>
                    <input class="lockable" type="text" name="district" value="{{ old('district', $customer->district) }}">
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

            <input type="hidden" name="address2" value="{{ old('address2', $customer->address2) }}">

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
        </section>

        <section class="prospect-step buying-step" data-step="2">
            <h3>Buying Details</h3>

            <div class="section-title-line">
                <label>Interested In Vehicle</label>
                <label class="switch-label">
                    <input type="checkbox" id="toggleInterestedVehicleEdit" name="edit_interested_vehicle" value="1" @checked($isInterestedVehicleEdit)>
                    <span>Edit</span>
                </label>
            </div>

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

            <label>Select Color</label>
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

            <label>Source Of Information</label>
            <select name="source_of_information" id="source_of_information" data-selected-source-info="{{ $selectedSourceOfInformation }}">
                <option value="">Select Source of Information</option>
            </select>

            <label>Did the Customer Take a Quote?</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="quote_taken" value="yes" @checked($selectedQuote === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="quote_taken" value="no" @checked($selectedQuote === 'no')><span>No</span></label>
            </div>

            <div class="buying-quote-when" data-conditional="quote_taken" data-value="yes">
                <label>When?</label>
                <input type="date" name="quote_date" value="{{ old('quote_date', $prospect->quote_date) }}">
            </div>

            <label>Test Drive Given</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="test_drive_given" value="yes" @checked($selectedTestDrive === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="test_drive_given" value="no" @checked($selectedTestDrive === 'no')><span>No</span></label>
            </div>

            <div class="buying-test-yes" data-conditional="test_drive_given" data-value="yes">
                <label>When?</label>
                <input type="date" name="test_drive_date" value="{{ old('test_drive_date', $prospect->test_drive_date) }}">

                <label>Vehicle Model</label>
                <input type="text" name="test_drive_vehicle_model" value="{{ old('test_drive_vehicle_model', $prospect->test_drive_vehicle_model) }}">

                <label>To Whom?</label>
                <input type="text" name="test_drive_to_whom" value="{{ old('test_drive_to_whom', $prospect->test_drive_to_whom) }}">
            </div>

            <div class="buying-test-no" data-conditional="test_drive_given" data-value="no">
                <label>Why Not Given?</label>
                <input type="text" name="test_drive_not_given_reason" value="{{ old('test_drive_not_given_reason', $prospect->test_drive_not_given_reason) }}" placeholder="Reason">
            </div>

            <label>Mode Of Purchase</label>
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

            <label>First Time Buyer</label>
            <div class="segmented buying-segment-2">
                <label><input type="radio" name="first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="first_time_buyer" value="no" @checked($selectedFirstTimeBuyer === 'no')><span>No</span></label>
            </div>

            <div class="grid-3 buying-existing-grid" data-conditional="first_time_buyer" data-value="no">
                <div>
                    <label>Existing Vehicle Brand</label>
                    <input type="text" name="existing_vehicle_brand" value="{{ old('existing_vehicle_brand', $prospect->existing_vehicle_brand) }}">
                </div>
                <div>
                    <label>Existing Vehicle Model</label>
                    <input type="text" name="existing_vehicle_model" value="{{ old('existing_vehicle_model', $prospect->existing_vehicle_model) }}">
                </div>
                <div>
                    <label>Year</label>
                    <input type="number" name="existing_vehicle_year" min="1950" max="2100" value="{{ old('existing_vehicle_year', $prospect->existing_vehicle_year) }}">
                </div>
            </div>
        </section>
        <section class="prospect-step exchange-step" data-step="3">
            <h3>Exchange Details</h3>

            <label>Interested In Exchange?</label>
            <div class="segmented exchange-interest-segment">
                <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange === 'no')><span>No</span></label>
            </div>

            <div class="exchange-detail-wrap" data-conditional="interested_in_exchange" data-value="yes">
                <h4 class="sub-title">Exchange detail</h4>

                @php
                    $exchangeYearSelected = old('exchange_manufacture_year', $prospect->exchange_manufacture_year);
                @endphp

                <div class="grid-2 exchange-input-grid">
                    <div>
                        <select name="exchange_manufacture_year">
                            <option value="">Year</option>
                            @for($year = now()->year + 1; $year >= 1950; $year--)
                                <option value="{{ $year }}" @selected((string) $exchangeYearSelected === (string) $year)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <input type="text" name="exchange_color" value="{{ old('exchange_color', $prospect->exchange_color) }}" placeholder="Color">
                    </div>
                </div>

                <div class="grid-2 exchange-input-grid">
                    <div>
                        <input type="number" name="exchange_mileage_km" min="0" value="{{ old('exchange_mileage_km', $prospect->exchange_mileage_km) }}" placeholder="Mileage Km">
                    </div>
                    <div>
                        <input type="text" name="exchange_registration_no" value="{{ old('exchange_registration_no', $prospect->exchange_registration_no) }}" placeholder="Registration No.">
                    </div>
                </div>

                <div class="grid-3 exchange-price-grid">
                    <div>
                        <input type="number" step="0.01" min="0" name="exchange_expected_price" value="{{ old('exchange_expected_price', $prospect->exchange_expected_price) }}" placeholder="Expected price">
                    </div>
                    <div>
                        <input type="number" step="0.01" min="0" name="exchange_quoted_price" value="{{ old('exchange_quoted_price', $prospect->exchange_quoted_price) }}" placeholder="Quoted Price">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="exchange_price_difference" readonly value="{{ old('exchange_price_difference', $prospect->exchange_price_difference) }}" placeholder="Difference">
                    </div>
                </div>

                <input type="hidden" name="exchange_vehicle_brand" value="{{ old('exchange_vehicle_brand', $prospect->exchange_vehicle_brand) }}">
                <input type="hidden" name="exchange_vehicle_model" value="{{ old('exchange_vehicle_model', $prospect->exchange_vehicle_model) }}">

                <div class="section-title-line exchange-switch-row">
                    <label>Add images</label>
                    <label class="switch-label exchange-switch">
                        <input type="checkbox" id="addExchangeImages" name="add_exchange_images" value="1" @checked($isExchangeImageAdd)>
                        <span></span>
                    </label>
                </div>

                <div id="exchangeImageFields" class="exchange-images-wrap">
                    <div class="exchange-upload-grid">
                        <label class="exchange-upload-tile">Blue Book<input type="file" name="blue_book_image" accept="image/*"></label>
                        <label class="exchange-upload-tile">Lot No<input type="file" name="lot_no_image" accept="image/*"></label>
                        <label class="exchange-upload-tile">Car picture 1<input type="file" name="car_pic_1_image" accept="image/*"></label>
                        <label class="exchange-upload-tile">Car picture 2<input type="file" name="car_pic_2_image" accept="image/*"></label>
                    </div>

                    @if(!empty($prospect->blue_book_image) || !empty($prospect->lot_no_image) || !empty($prospect->car_pic_1_image) || !empty($prospect->car_pic_2_image))
                        <div class="existing-files">
                            <small>Previously uploaded images are saved.</small>
                        </div>
                    @endif

                    <div class="section-title-line exchange-switch-row exchange-more-row">
                        <label>Add more images</label>
                        <button type="button" class="exchange-more-btn" id="addMoreExchangeImagesBtn" aria-label="Add more images">+</button>
                    </div>

                    <div id="extraExchangeImagesContainer" class="exchange-upload-grid exchange-upload-grid-extra">
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra">Car picture 3<input type="file" name="extra_exchange_images[]" accept="image/*"></label>
                        </div>
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra">Car picture 4<input type="file" name="extra_exchange_images[]" accept="image/*"></label>
                        </div>
                        <div class="extra-image-row">
                            <label class="exchange-upload-tile exchange-upload-tile-extra">Car picture 5<input type="file" name="extra_exchange_images[]" accept="image/*"></label>
                        </div>
                    </div>

                    @if(!empty($extraExchangeImages))
                        <div class="existing-files">
                            <small>{{ count($extraExchangeImages) }} extra image(s) already uploaded.</small>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        <section class="prospect-step offer-step" data-step="4">
            <div class="section-title-line offer-title-line">
                <h3>Offer Details</h3>
                <label class="switch-label offer-edit-label">
                    <input type="checkbox" id="allowOfferEdit" checked>
                    <span>Edit</span>
                </label>
            </div>

            <div class="offer-panel-card">
                <div class="offer-panel-head">
                    <span>Unit price - without VAT</span>
                    <button type="button" class="offer-close-btn" aria-label="Close">Ã—</button>
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
                    <input type="number" name="offer_unit_price_discount" id="offer_unit_price_discount" step="0.01" min="0" value="{{ $offerUnitPriceDiscount }}" placeholder="Discount">
                </div>
            </div>

            <div class="offer-panel-card">
                <div class="offer-panel-head">
                    <span>VAT</span>
                    <button type="button" class="offer-close-btn" aria-label="Close">Ã—</button>
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
                    <input type="number" name="offer_vat_discount" id="offer_vat_discount" step="0.01" min="0" value="{{ $offerVatDiscount }}" placeholder="Discount">
                </div>
            </div>

            <div class="offer-total-strip">
                <div class="offer-total-strip-head">
                    <span>Total</span>
                    <span>Cost</span>
                    <span>Offer</span>
                    <span>Final offer price</span>
                </div>
                <div class="offer-total-strip-values">
                    <span></span>
                    <strong id="offerTotalCostDisplay">{{ number_format((float) $offerTotalCost, 2, '.', '') }}</strong>
                    <strong id="offerTotalDiscountDisplay">{{ number_format((float) $offerTotalDiscount, 2, '.', '') }}</strong>
                    <strong id="offerFinalPriceDisplay">{{ number_format((float) $offerFinalPrice, 2, '.', '') }}</strong>
                </div>
            </div>

            <input type="hidden" name="offer_total_cost" id="offer_total_cost" value="{{ $offerTotalCost }}">
            <input type="hidden" name="offer_total_discount" id="offer_total_discount" value="{{ $offerTotalDiscount }}">
            <input type="hidden" name="offer_final_price" id="offer_final_price" value="{{ $offerFinalPrice }}">
        </section>
        <section class="prospect-step plan-step" data-step="5">
            <h3>Plan Followup</h3>

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
                        <label>Followup Details</label>
                        <input type="date" name="follow_date" value="{{ $selectedFollowDate }}">
                        <span class="plan-input-icon calendar" aria-hidden="true"></span>
                    </div>
                    <div class="plan-schedule-field">
                        <label>Follow up time</label>
                        <input type="time" name="follow_time" value="{{ $selectedFollowTime }}">
                        <span class="plan-input-icon clock" aria-hidden="true"></span>
                    </div>
                </div>
            </div>

            <label class="plan-question">What according to you is the lead status?</label>
            <div class="plan-status-row">
                <label class="plan-status-item hot">
                    <input type="radio" name="lead_status" value="hot" @checked($selectedLeadStatus === 'hot')>
                    <span class="face">:-)</span>
                    <em>Hot</em>
                </label>
                <label class="plan-status-item warm">
                    <input type="radio" name="lead_status" value="warm" @checked($selectedLeadStatus === 'warm')>
                    <span class="face">:-|</span>
                    <em>Warm</em>
                </label>
                <label class="plan-status-item cold">
                    <input type="radio" name="lead_status" value="cold" @checked($selectedLeadStatus === 'cold')>
                    <span class="face">:-(</span>
                    <em>Cold</em>
                </label>
            </div>

            <div class="plan-remark-wrap">
                <input type="text" name="customer_remark" value="{{ $customerRemark }}" placeholder="Add customer remark here">
                <span class="arrow"></span>
            </div>
        </section>

        <div class="summary-modal" id="offerSummaryModal">
            <div class="summary-modal-card">
                <h3>SUMMARY</h3>

                <div class="summary-modern-vehicle">
                    Interested in <strong id="summaryInterestedVehicle">{{ $vehicle->model }} {{ $vehicle->variant }}</strong>
                </div>

                <div class="summary-modern-grid-head">
                    <span></span>
                    <span>Cost</span>
                    <span>Offer</span>
                    <span>Payable</span>
                </div>

                <div class="summary-modern-row">
                    <span class="summary-modern-label">Vat</span>
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

                <button type="button" class="btn btn-primary summary-confirm-btn" id="summaryLooksGoodBtn">Looks Good</button>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-secondary" id="backBtn">Back</button>
            <button type="button" class="btn btn-secondary" id="saveExitBtn">Save & Exit</button>
            <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
        </div>
    </form>
</div>

<script>
    window.PROSPECT_COMPETITION_MAP = @json($competitionMap);
    window.PROSPECT_SOURCE_INFO_MAP = @json($sourceInfoMap);
</script>
<script src="{{ asset('js/prospect.js') }}"></script>
@endsection








































