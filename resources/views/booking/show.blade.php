@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/booking.css') }}">

@php
    $summaryName = trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'N/A'));
    $summaryMobiles = collect($customer?->mobile_numbers ?? [])->filter()->values();
    $summaryMobile = $summaryMobiles->isNotEmpty() ? $summaryMobiles->implode(', ') : 'N/A';
    $summaryAddress = collect([$customer?->address1, $customer?->address2, $customer?->location, $customer?->district, $customer?->state])
        ->filter()
        ->implode(', ');

    $customerTypeLabel = match ($prospect?->customer_type) {
        'individual' => 'Individual',
        'corporate' => 'Corporate',
        default => 'N/A',
    };

    $professionLabel = match ($prospect?->profession) {
        'salaried' => 'Salaried',
        'self_employed' => 'Self Employed',
        'other' => 'Other',
        'not_asked' => 'I Did Not Ask',
        default => 'N/A',
    };

    $dobLabel = $prospect?->date_of_birth
        ? \Carbon\Carbon::parse($prospect->date_of_birth)->format('d-M-Y')
        : 'N/A';

    $selectedTitle = old('title', $defaultValues['title']);
    $selectedName = old('name', $defaultValues['name']);
    $selectedContactType = old('contact_type', $defaultValues['contact_type']);
    $selectedMobile = old('mobile_numbers', $defaultValues['mobile_numbers']);
    $selectedDistrict = old('district', $defaultValues['district']);
    $selectedLocation = old('location', $defaultValues['location']);
    $selectedState = old('state', $defaultValues['state']);
    $selectedAddress1 = old('address1', $defaultValues['address1']);
    $selectedAddress2 = old('address2', $defaultValues['address2']);
    $selectedCustomerType = old('customer_type', $defaultValues['customer_type']);
    $selectedProfession = old('profession', $defaultValues['profession']);
    $selectedDob = old('date_of_birth', $defaultValues['date_of_birth']);
    $selectedInterestedModel = old('interested_model', $defaultValues['interested_model']);
    $selectedInterestedEngine = old('interested_engine', $defaultValues['interested_engine']);
    $selectedInterestedVariant = old('interested_variant', $defaultValues['interested_variant']);
    $selectedVehicleColor = old('interested_vehicle_color', $defaultValues['interested_vehicle_color']);
    $isBuyingVehicleEdit = old('edit_buying_vehicle') === '1';
    $selectedQuote = old('quote_taken', $defaultValues['quote_taken']);
    $selectedQuoteDate = old('quote_date', $defaultValues['quote_date']);
    $selectedTestDrive = old('test_drive_given', $defaultValues['test_drive_given']);
    $selectedTestDriveDate = old('test_drive_date', $defaultValues['test_drive_date']);
    $selectedTestDriveModel = old('test_drive_vehicle_model', $defaultValues['test_drive_vehicle_model']);
    $selectedTestDriveToWhom = old('test_drive_to_whom', $defaultValues['test_drive_to_whom']);
    $selectedTestDriveReason = old('test_drive_not_given_reason', $defaultValues['test_drive_not_given_reason']);
    $testDriveNoReasons = [
        'Not interested',
        'Vehicle not available',
        'Vehicle damaged/under repair',
        'Not met in person',
        'Already driven',
        'I Did Not Offer',
        'Others',
    ];
    $selectedPurchaseMode = old('purchase_mode', $defaultValues['purchase_mode']);
    $selectedFinanceForm = old('finance_form', $defaultValues['finance_form']);
    $selectedCompetition = old('interested_in_competition', $defaultValues['interested_in_competition']);
    $selectedCompetitionBrand = old('competition_brand', $defaultValues['competition_brand']);
    $selectedCompetitionModel = old('competition_model', $defaultValues['competition_model']);
    $selectedFirstTimeBuyer = old('first_time_buyer', $defaultValues['first_time_buyer']);
    $selectedExistingBrand = old('existing_vehicle_brand', $defaultValues['existing_vehicle_brand']);
    $selectedExistingModel = old('existing_vehicle_model', $defaultValues['existing_vehicle_model']);
    $selectedExistingYear = old('existing_vehicle_year', $defaultValues['existing_vehicle_year']);
    $selectedInterestedExchange = old('interested_in_exchange', $defaultValues['interested_in_exchange']);
    $selectedExchangeType = old('exchange_type', $defaultValues['exchange_type']);
    $selectedExchangeBrand = old('exchange_vehicle_brand', $defaultValues['exchange_vehicle_brand']);
    $selectedExchangeModel = old('exchange_vehicle_model', $defaultValues['exchange_vehicle_model']);
    $selectedExchangeYear = old('exchange_manufacture_year', $defaultValues['exchange_manufacture_year']);
    $selectedExchangeColor = old('exchange_color', $defaultValues['exchange_color']);
    $selectedExchangeMileage = old('exchange_mileage_km', $defaultValues['exchange_mileage_km']);
    $selectedExchangeRegNo = old('exchange_registration_no', $defaultValues['exchange_registration_no']);
    $selectedExchangeExpectedPrice = old('exchange_expected_price', $defaultValues['exchange_expected_price']);
    $selectedExchangeQuotedPrice = old('exchange_quoted_price', $defaultValues['exchange_quoted_price']);
    $selectedExchangeDifference = old('exchange_price_difference', $defaultValues['exchange_price_difference']);
    $isExchangeEdit = old('edit_exchange_details') === '1';

    $exchangeInterestLabel = match ($prospect?->interested_in_exchange) {
        'yes' => 'Yes',
        'no' => 'No',
        default => 'N/A',
    };

    $money = fn($value) => $value === null ? 'N/A' : number_format((float) $value, 2);

    $backUrl = $currentStep > 1
        ? route('booking.show', ['enquiry' => $enquiry->id, 'step' => $currentStep - 1])
        : route('prospect.show', ['enquiry' => $enquiry->id, 'step' => 4]);
    $isExchangeNoMode = $currentStep === 3 && $selectedInterestedExchange === 'no';
    $showExchangeDetails = $selectedInterestedExchange === 'yes' && in_array($selectedExchangeType, ['in_house', 'outhouse'], true);
    $selectedOfferUnitPrice = old('offer_unit_price', $defaultValues['offer_unit_price']);
    $selectedOfferUnitPriceDiscount = old('offer_unit_price_discount', $defaultValues['offer_unit_price_discount']);
    $selectedOfferUnitPriceFree = old('offer_unit_price_free', (int) ($defaultValues['offer_unit_price_free'] ?? 0)) == 1;
    $selectedOfferVatAmount = old('offer_vat_amount', $defaultValues['offer_vat_amount']);
    $selectedOfferVatDiscount = old('offer_vat_discount', $defaultValues['offer_vat_discount']);
    $selectedOfferVatFree = old('offer_vat_free', (int) ($defaultValues['offer_vat_free'] ?? 0)) == 1;
    $selectedOfferTotalCost = old('offer_total_cost', $defaultValues['offer_total_cost']);
    $selectedOfferTotalDiscount = old('offer_total_discount', $defaultValues['offer_total_discount']);
    $selectedOfferFinalPrice = old('offer_final_price', $defaultValues['offer_final_price']);
    $isOfferEdit = old('edit_offer_details') === '1';

    $interestedVehicleLine = collect([$selectedInterestedModel, $selectedInterestedEngine, $selectedInterestedVariant])
        ->filter()
        ->implode(' / ');
    $interestedVehicleLine = $interestedVehicleLine ?: 'Not selected';
    $exchangeVehicleLine = collect([$selectedExchangeBrand, $selectedExchangeModel])
        ->filter()
        ->implode(' ');
    $exchangeVehicleLine = $exchangeVehicleLine ?: 'Not selected';

    $vehicleColorOptions = ['White', 'Black', 'Silver', 'Grey', 'Red', 'Blue', 'Green', 'Brown', 'Orange', 'Other'];
    $competitionMap = collect($competitionMap ?? []);
    $competitionBrands = $competitionMap->keys()->values()->all();
    $stepTitleMap = [
        1 => 'Personal Details',
        2 => 'Buying Details',
        3 => 'Exchange Details',
        4 => 'Offer Details',
        5 => 'Booking Form',
    ];
    $pageTitle = $stepTitleMap[$currentStep] ?? 'Booking Detail';
@endphp

<div class="booking-page">
    <header class="booking-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
    </header>

    <div class="booking-stepper">
        @foreach([
            1 => 'Personal Details',
            2 => 'Buying Details',
            3 => 'Exchange Details',
            4 => 'Offer Details',
            5 => 'Booking Form'
        ] as $index => $label)
            <div class="stepper-item {{ $index === $currentStep ? 'active' : ($index < $currentStep ? 'complete' : '') }}">
                <span class="step-number">{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <main class="booking-shell">
        @if(session('success'))
            <div class="booking-flash success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="booking-flash error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($currentStep > 2)
            @if($currentStep !== 4)
                <h2>{{ $pageTitle }}</h2>
            @endif

            @if($currentStep === 4)
                <div class="offer-page-summary">
                    <h3>SUMMARY</h3>
                    <p>Customer Name: <strong>{{ $summaryName }}</strong></p>
                    <p>Interested in: <strong>{{ strtoupper($interestedVehicleLine) }}</strong></p>
                </div>
            @else
                <div class="booking-summary">
                    <p>{{ $summaryName }}</p>
                    <p>{{ $summaryMobile }}</p>
                    <p>{{ $summaryAddress ?: 'N/A' }}</p>
                    <p>{{ $customerTypeLabel }}</p>
                    <p>Profession - {{ $professionLabel }}</p>
                    <p>DOB: {{ $dobLabel }}</p>
                </div>
            @endif
        @endif

        <form method="POST" action="{{ route('booking.store', $enquiry->id) }}" enctype="multipart/form-data" id="bookingForm">
            @csrf
            <input type="hidden" name="booking_step" value="{{ $currentStep }}">

            <section class="booking-section personal-section {{ $currentStep === 1 ? 'active' : '' }}">
                <div class="section-head-inline personal-head">
                    <h3 class="section-heading">Personal Details</h3>
                    <label class="inline-edit-check">
                        <input type="checkbox" id="sameAsToggle" @checked(!$sameAsCustomer)>
                        <span>Edit Buying Details</span>
                    </label>
                    <input type="hidden" id="bookingSameAsCustomer" name="booking_same_as_customer" value="{{ $sameAsCustomer ? '1' : '0' }}">
                </div>

                <div id="editBlock" class="personal-edit-block">
                    <div class="row personal-row-top">
                        <div class="field-title">
                            <label>Title</label>
                            <select name="title" data-personal-editable>
                                @foreach(['Mr', 'Mrs', 'Ms', 'Dr'] as $titleOption)
                                    <option value="{{ $titleOption }}" @selected($selectedTitle === $titleOption)>{{ $titleOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-name">
                            <label>Name</label>
                            <input type="text" name="name" value="{{ $selectedName }}" data-personal-editable>
                        </div>

                        <div class="field-dob">
                            <label>DOB</label>
                            <input type="date" name="date_of_birth" value="{{ $selectedDob }}" data-personal-editable>
                        </div>

                        <div class="field-contact">
                            <label>Contact No</label>
                            <div class="contact-pill-wrap">
                                <select name="contact_type" class="contact-type-select" data-personal-editable>
                                    @foreach(['Mobile', 'Home', 'Office'] as $contactTypeOption)
                                        <option value="{{ $contactTypeOption }}" @selected($selectedContactType === $contactTypeOption)>{{ $contactTypeOption }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="mobile_numbers" value="{{ $selectedMobile }}" data-personal-editable>
                                <button type="button" class="mini-add-btn" aria-label="Add contact">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="row personal-row-two">
                        <div>
                            <label>District</label>
                            <input type="text" name="district" value="{{ $selectedDistrict }}" data-personal-editable>
                        </div>
                        <div>
                            <label>Location</label>
                            <input type="text" name="location" value="{{ $selectedLocation }}" data-personal-editable>
                        </div>
                    </div>

                    <div class="row personal-row-three">
                        <div>
                            <label>State</label>
                            <input type="text" name="state" value="{{ $selectedState }}" data-personal-editable>
                        </div>
                        <div>
                            <label>Address Line 1</label>
                            <input type="text" name="address1" value="{{ $selectedAddress1 }}" data-personal-editable>
                        </div>
                    </div>

                    <div class="row">
                        <label>Address Line 2</label>
                        <input type="text" name="address2" value="{{ $selectedAddress2 }}" data-personal-editable>
                    </div>

                    <label>Type Of Customer</label>
                    <div class="segment-row two personal-segment">
                        <label><input type="radio" name="customer_type" value="individual" data-personal-editable @checked($selectedCustomerType === 'individual')><span>Individual</span></label>
                        <label><input type="radio" name="customer_type" value="corporate" data-personal-editable @checked($selectedCustomerType === 'corporate')><span>Corporate</span></label>
                    </div>

                    <div id="corporateNameRow" class="row {{ $selectedCustomerType === 'corporate' ? '' : 'hidden' }}">
                        <label>Corporate Name</label>
                        <input type="text" placeholder="Corporate Name" data-personal-editable>
                    </div>

                    <label>Profession</label>
                    <div class="segment-row four personal-segment">
                        <label><input type="radio" name="profession" value="salaried" data-personal-editable @checked($selectedProfession === 'salaried')><span>Salaried</span></label>
                        <label><input type="radio" name="profession" value="self_employed" data-personal-editable @checked($selectedProfession === 'self_employed')><span>Self Employed</span></label>
                        <label><input type="radio" name="profession" value="other" data-personal-editable @checked($selectedProfession === 'other')><span>Other</span></label>
                        <label><input type="radio" name="profession" value="not_asked" data-personal-editable @checked($selectedProfession === 'not_asked')><span>I Did Not Ask</span></label>
                    </div>
                </div>

                <div class="purchase-order-box personal-purchase-order">
                    <label for="purchase_order_image">Purchase Order</label>
                    <input id="purchase_order_image" type="file" name="purchase_order_image" accept=".jpg,.jpeg,.png,.webp">

                    @if(!empty($booking->purchase_order_image))
                        <p class="existing-file">
                            Uploaded:
                            <a href="{{ asset('storage/' . $booking->purchase_order_image) }}" target="_blank" rel="noopener">
                                View Current File
                            </a>
                        </p>
                    @endif
                </div>
            </section>

            <section class="booking-section buying-section {{ $currentStep === 2 ? 'active' : '' }}">
                <div class="buying-lead-summary">
                    <p><strong>Name :</strong> {{ $summaryName }}</p>
                    <p><strong>Interested In :</strong> {{ strtoupper($interestedVehicleLine) }}</p>
                    <p><strong>Mobile No :</strong> {{ $summaryMobile }}</p>
                    <p><strong>Dist :</strong> {{ $selectedDistrict ?: 'N/A' }}</p>
                </div>

                <div class="section-head-inline">
                    <h3 class="section-heading">Buying Details</h3>
                    <label class="inline-edit-check">
                        <input type="hidden" name="edit_buying_vehicle" value="0">
                        <input type="checkbox" id="toggleBuyingVehicleEdit" name="edit_buying_vehicle" value="1" @checked($isBuyingVehicleEdit)>
                        <span>Edit</span>
                    </label>
                </div>

                <div class="row">
                    <label>Interested In Vehicle</label>
                    <div id="vehicleReadPill" class="vehicle-pill-display">{{ $interestedVehicleLine }}</div>
                </div>

                <div id="vehicleEditFields" class="row triple {{ $isBuyingVehicleEdit ? '' : 'hidden' }}">
                    <div>
                        <label>Model</label>
                        <select id="interested_model" name="interested_model" class="buying-select" data-selected-model="{{ $selectedInterestedModel }}">
                            <option value="">Select Model</option>
                            @foreach($vehicleModels as $modelOption)
                                <option value="{{ $modelOption }}" @selected($selectedInterestedModel === $modelOption)>{{ $modelOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Engine Type</label>
                        <select id="interested_engine" name="interested_engine" class="buying-select" data-selected-engine="{{ $selectedInterestedEngine }}">
                            <option value="">Select Engine Type</option>
                        </select>
                    </div>
                    <div>
                        <label>Variant</label>
                        <select id="interested_variant" name="interested_variant" class="buying-select" data-selected-variant="{{ $selectedInterestedVariant }}">
                            <option value="">Select Variant</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <label>Select Color</label>
                    <select name="interested_vehicle_color" class="vehicle-color-select">
                        <option value="">Select Color</option>
                        @foreach($vehicleColorOptions as $colorOption)
                            <option value="{{ $colorOption }}" @selected($selectedVehicleColor === $colorOption)>{{ $colorOption }}</option>
                        @endforeach
                        @if(!empty($selectedVehicleColor) && !in_array($selectedVehicleColor, $vehicleColorOptions, true))
                            <option value="{{ $selectedVehicleColor }}" selected>{{ $selectedVehicleColor }}</option>
                        @endif
                    </select>
                </div>

                <label>Did the customer take a quote?</label>
                <div class="segment-row two">
                    <label><input type="radio" name="quote_taken" value="yes" @checked($selectedQuote === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="quote_taken" value="no" @checked($selectedQuote === 'no')><span>No</span></label>
                </div>

                <div class="row conditional" id="quoteDateWrap">
                    <label>When?</label>
                    <input type="date" name="quote_date" value="{{ $selectedQuoteDate }}">
                </div>

                <label>Test driven given?</label>
                <div class="segment-row two">
                    <label><input type="radio" name="test_drive_given" value="yes" @checked($selectedTestDrive === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="test_drive_given" value="no" @checked($selectedTestDrive === 'no')><span>No</span></label>
                </div>

                <div class="conditional" id="testDriveYesWrap">
                    <div class="row split">
                        <div>
                            <label>When?</label>
                            <input type="date" name="test_drive_date" value="{{ $selectedTestDriveDate }}">
                        </div>
                        <div>
                            <label>Vehicle Used?</label>
                            <select name="test_drive_vehicle_model" class="buying-select">
                                <option value="">Select Model</option>
                                @foreach($vehicleModels as $modelOption)
                                    <option value="{{ $modelOption }}" @selected($selectedTestDriveModel === $modelOption)>{{ $modelOption }}</option>
                                @endforeach
                                @if(!empty($selectedTestDriveModel) && !$vehicleModels->contains($selectedTestDriveModel))
                                    <option value="{{ $selectedTestDriveModel }}" selected>{{ $selectedTestDriveModel }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <label>Name</label>
                        <input type="text" name="test_drive_to_whom" value="{{ $selectedTestDriveToWhom }}">
                    </div>
                </div>

                <div class="row conditional" id="testDriveNoWrap">
                    <label>Why Not Given?</label>
                    <select name="test_drive_not_given_reason" class="buying-select">
                        <option value="">Select reason</option>
                        @foreach($testDriveNoReasons as $reasonOption)
                            <option value="{{ $reasonOption }}" @selected($selectedTestDriveReason === $reasonOption)>{{ $reasonOption }}</option>
                        @endforeach
                        @if(!empty($selectedTestDriveReason) && !in_array($selectedTestDriveReason, $testDriveNoReasons, true))
                            <option value="{{ $selectedTestDriveReason }}" selected>{{ $selectedTestDriveReason }}</option>
                        @endif
                    </select>
                </div>

                <label>Mode of Purchase</label>
                <div class="segment-row two">
                    <label><input type="radio" name="purchase_mode" value="cash" @checked($selectedPurchaseMode === 'cash')><span>Cash</span></label>
                    <label><input type="radio" name="purchase_mode" value="finance" @checked($selectedPurchaseMode === 'finance')><span>Finance</span></label>
                </div>

                <div class="row conditional" id="financeFormWrap">
                    <label>Finance form</label>
                    <select name="finance_form">
                        <option value="">Select finance form</option>
                        <option value="in_house" @selected($selectedFinanceForm === 'in_house')>In House</option>
                        <option value="self" @selected($selectedFinanceForm === 'self')>Self</option>
                        <option value="other" @selected($selectedFinanceForm === 'other')>Other</option>
                    </select>
                </div>

                <label>Interested in Competition</label>
                <div class="segment-row three">
                    <label><input type="radio" name="interested_in_competition" value="yes" @checked($selectedCompetition === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="interested_in_competition" value="no" @checked($selectedCompetition === 'no')><span>No</span></label>
                    <label><input type="radio" name="interested_in_competition" value="not_asked" @checked($selectedCompetition === 'not_asked')><span>I Did Not Ask</span></label>
                </div>

                <div class="conditional" id="competitionWrap">
                    <div class="row split">
                        <div>
                            <label>Competition Brand</label>
                            <select id="competition_brand" name="competition_brand" class="buying-select">
                                <option value="">Select Brand</option>
                                @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedCompetitionBrand === $brandOption)>{{ $brandOption }}</option>
                                @endforeach
                                @if(!empty($selectedCompetitionBrand) && !in_array($selectedCompetitionBrand, $competitionBrands, true))
                                    <option value="{{ $selectedCompetitionBrand }}" selected>{{ $selectedCompetitionBrand }}</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label>Competition Model</label>
                            <select id="competition_model" name="competition_model" class="buying-select" data-selected-model="{{ $selectedCompetitionModel }}">
                                <option value="">Select Model</option>
                            </select>
                        </div>
                    </div>
                </div>

                <label>First time buyer?</label>
                <div class="segment-row two">
                    <label><input type="radio" name="first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="first_time_buyer" value="no" @checked($selectedFirstTimeBuyer === 'no')><span>No</span></label>
                </div>

                <div class="conditional" id="existingVehicleWrap">
                    <div class="row three">
                        <div>
                            <label>Existing Vehicle Brand</label>
                            <select id="existing_vehicle_brand" name="existing_vehicle_brand" class="buying-select">
                                <option value="">Select Brand</option>
                                @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedExistingBrand === $brandOption)>{{ $brandOption }}</option>
                                @endforeach
                                @if(!empty($selectedExistingBrand) && !in_array($selectedExistingBrand, $competitionBrands, true))
                                    <option value="{{ $selectedExistingBrand }}" selected>{{ $selectedExistingBrand }}</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label>Existing Vehicle Model</label>
                            <select id="existing_vehicle_model" name="existing_vehicle_model" class="buying-select" data-selected-model="{{ $selectedExistingModel }}">
                                <option value="">Select Model</option>
                            </select>
                        </div>
                        <div>
                            <label>Existing Vehicle Year</label>
                            <select name="existing_vehicle_year" class="buying-select">
                                <option value="">Select Year</option>
                                @for($year = now()->year + 1; $year >= 1950; $year--)
                                    <option value="{{ $year }}" @selected((string) $selectedExistingYear === (string) $year)>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="booking-section exchange-section {{ $currentStep === 3 ? 'active' : '' }}">
                <h3 class="section-heading">Exchange Details</h3>

                <label>Interested In Exchange</label>
                <div class="segment-row two">
                    <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange === 'no')><span>No</span></label>
                </div>

                <div id="exchangeTypeRow" class="exchange-mode-row {{ $selectedInterestedExchange === 'yes' ? '' : 'hidden' }}">
                    <div class="segment-row two">
                        <label><input type="radio" name="exchange_type" value="in_house" @checked($selectedExchangeType === 'in_house') @disabled($selectedInterestedExchange !== 'yes')><span>In-House</span></label>
                        <label><input type="radio" name="exchange_type" value="outhouse" @checked($selectedExchangeType === 'outhouse') @disabled($selectedInterestedExchange !== 'yes')><span>Outhouse</span></label>
                    </div>
                </div>

                <div id="exchangeDetailsWrap" class="exchange-detail-wrap {{ $showExchangeDetails ? '' : 'hidden' }}">

                    <div class="section-head-inline">
                        <label>Exchange detail</label>
                        <label class="inline-edit-check">
                            <input type="hidden" name="edit_exchange_details" value="0">
                            <input type="checkbox" id="toggleExchangeEdit" name="edit_exchange_details" value="1" @checked($isExchangeEdit)>
                            <span>Edit</span>
                        </label>
                    </div>

                    <div id="exchangeEditFields" class="exchange-edit-fields {{ $showExchangeDetails ? '' : 'hidden' }}">
                        <div class="row exchange-interested-row">
                            <label>Interested In</label>
                            <div class="vehicle-pill-display">{{ $interestedVehicleLine }}</div>
                        </div>

                        <div class="row split">
                            <div>
                                <label>Brand</label>
                                <select id="exchange_vehicle_brand" name="exchange_vehicle_brand" class="buying-select">
                                    <option value="">Select Brand</option>
                                    @foreach($competitionBrands as $brandOption)
                                        <option value="{{ $brandOption }}" @selected($selectedExchangeBrand === $brandOption)>{{ $brandOption }}</option>
                                    @endforeach
                                    @if(!empty($selectedExchangeBrand) && !in_array($selectedExchangeBrand, $competitionBrands, true))
                                        <option value="{{ $selectedExchangeBrand }}" selected>{{ $selectedExchangeBrand }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label>Model</label>
                                <select id="exchange_vehicle_model" name="exchange_vehicle_model" class="buying-select" data-selected-model="{{ $selectedExchangeModel }}">
                                    <option value="">Select Model</option>
                                </select>
                            </div>
                        </div>

                        <div class="row split">
                            <div>
                                <label>Year</label>
                                <select name="exchange_manufacture_year" class="buying-select">
                                    <option value="">Select Year</option>
                                    @for($year = now()->year + 1; $year >= 1950; $year--)
                                        <option value="{{ $year }}" @selected((string) $selectedExchangeYear === (string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label>Color</label>
                                <input type="text" name="exchange_color" value="{{ $selectedExchangeColor }}" placeholder="Color">
                            </div>
                        </div>

                        <div class="row split">
                            <div>
                                <label>Mileage Km</label>
                                <input type="number" min="0" name="exchange_mileage_km" value="{{ $selectedExchangeMileage }}" placeholder="Mileage KM">
                            </div>
                            <div>
                                <label>Registration No.</label>
                                <input type="text" name="exchange_registration_no" value="{{ $selectedExchangeRegNo }}" placeholder="Registration No.">
                            </div>
                        </div>

                        <div class="row triple">
                            <div>
                                <label>Expected Price</label>
                                <input type="number" step="0.01" min="0" id="exchange_expected_price" name="exchange_expected_price" value="{{ $selectedExchangeExpectedPrice }}" placeholder="Expected Price">
                            </div>
                            <div>
                                <label>Quoted Price</label>
                                <input type="number" step="0.01" min="0" id="exchange_quoted_price" name="exchange_quoted_price" value="{{ $selectedExchangeQuotedPrice }}" placeholder="Quoted Price">
                            </div>
                            <div>
                                <label>Difference</label>
                                <input type="number" step="0.01" id="exchange_price_difference" name="exchange_price_difference" class="exchange-difference-input" value="{{ $selectedExchangeDifference }}" placeholder="Difference" readonly>
                            </div>
                        </div>

                        <div class="exchange-image-section">
                            <div class="exchange-image-head">
                                <label>Add images</label>
                                <label class="exchange-image-switch">
                                    <input type="checkbox" id="bookingImagesToggle" checked>
                                    <span></span>
                                </label>
                            </div>

                            <div id="bookingImageBody">
                                <div class="exchange-upload-grid exchange-upload-grid-primary">
                                    <div class="exchange-upload-tile">
                                        <span class="exchange-upload-title">Blue Book</span>
                                        <img class="exchange-upload-preview" alt="Blue Book preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="blue_book_image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="exchange-upload-tile">
                                        <span class="exchange-upload-title">Lot No</span>
                                        <img class="exchange-upload-preview" alt="Lot No preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="lot_no_image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="exchange-upload-tile">
                                        <span class="exchange-upload-title">Car picture 1</span>
                                        <img class="exchange-upload-preview" alt="Car picture 1 preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="car_pic_1_image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="exchange-upload-tile">
                                        <span class="exchange-upload-title">Car picture 2</span>
                                        <img class="exchange-upload-preview" alt="Car picture 2 preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="car_pic_2_image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                </div>

                                <div class="exchange-more-head">
                                    <label>Add more images</label>
                                    <button type="button" id="bookingAddMoreImagesBtn" class="exchange-more-add-btn" aria-label="Add more images">+</button>
                                </div>

                                <div id="bookingExtraImageGrid" class="exchange-upload-grid exchange-upload-grid-extra">
                                    <div class="exchange-upload-tile exchange-upload-tile-extra">
                                        <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                                        <span class="exchange-upload-title">Car picture 3</span>
                                        <img class="exchange-upload-preview" alt="Car picture 3 preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="exchange-upload-tile exchange-upload-tile-extra">
                                        <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                                        <span class="exchange-upload-title">Car picture 4</span>
                                        <img class="exchange-upload-preview" alt="Car picture 4 preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="exchange-upload-tile exchange-upload-tile-extra">
                                        <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                                        <span class="exchange-upload-title">Car picture 5</span>
                                        <img class="exchange-upload-preview" alt="Car picture 5 preview" hidden>
                                        <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                                        <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="booking-section offer-section {{ $currentStep === 4 ? 'active' : '' }}">
                <div class="section-head-inline">
                    <h3 class="section-heading">Offer Details</h3>
                    <label class="inline-edit-check">
                        <input type="hidden" name="edit_offer_details" value="0">
                        <input type="checkbox" id="toggleOfferEdit" name="edit_offer_details" value="1" @checked($isOfferEdit)>
                        <span>Edit Buying Details</span>
                    </label>
                </div>

                <div class="offer-summary-panel" id="offerSummaryPanel">
                    <div class="offer-summary-table">
                        <div class="offer-summary-head">
                            <span></span>
                            <span>Cost</span>
                            <span>Offer</span>
                            <span>Payable</span>
                        </div>
                        <div class="offer-summary-row">
                            <strong>VAT</strong>
                            <span id="offerSummaryVatCost">{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                            <span id="offerSummaryVatOffer">{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                            <span id="offerSummaryVatPayable">{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
                        </div>
                        <div class="offer-summary-row">
                            <strong>Unit price (without vat)</strong>
                            <span id="offerSummaryUnitCost">{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                            <span id="offerSummaryUnitOffer">{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                            <span id="offerSummaryUnitPayable">{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
                        </div>
                    </div>

                    <div class="offer-summary-total">
                        <strong>Total</strong>
                        <span id="offerSummaryTotalCost">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
                        <span id="offerSummaryTotalOffer">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
                        <span id="offerSummaryTotalPayable">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
                    </div>

                    <div class="offer-remarks">
                        <label class="offer-remarks-toggle">
                            <span>Add Remarks</span>
                            <input type="checkbox" id="offerRemarksToggle" checked>
                            <i></i>
                        </label>
                        <textarea id="offerRemarksText" rows="4" placeholder="Type comment here......"></textarea>
                    </div>
                </div>

                <div class="offer-edit-group" id="offerEditGroup">
                    <div class="offer-card">
                        <div class="offer-card-title">Unit price (without vat)</div>
                        <div class="offer-card-amount-row">
                            <input type="number" step="0.01" min="0" name="offer_unit_price" id="offer_unit_price" value="{{ $selectedOfferUnitPrice }}">
                        </div>
                        <div class="offer-card-bottom-row">
                            <label class="offer-free-check">
                                <input type="hidden" name="offer_unit_price_free" value="0">
                                <input type="checkbox" name="offer_unit_price_free" id="offer_unit_price_free" value="1" @checked($selectedOfferUnitPriceFree)>
                                <span>Free</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="offer_unit_price_discount" id="offer_unit_price_discount" value="{{ $selectedOfferUnitPriceDiscount }}">
                        </div>
                    </div>

                    <div class="offer-card">
                        <div class="offer-card-title">VAT</div>
                        <div class="offer-card-amount-row">
                            <input type="number" step="0.01" min="0" name="offer_vat_amount" id="offer_vat_amount" value="{{ $selectedOfferVatAmount }}">
                        </div>
                        <div class="offer-card-bottom-row">
                            <label class="offer-free-check">
                                <input type="hidden" name="offer_vat_free" value="0">
                                <input type="checkbox" name="offer_vat_free" id="offer_vat_free" value="1" @checked($selectedOfferVatFree)>
                                <span>Free</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="offer_vat_discount" id="offer_vat_discount" value="{{ $selectedOfferVatDiscount }}">
                        </div>
                    </div>

                    <div class="offer-total-panel">
                        <div class="offer-total-head">
                            <span>Total</span>
                            <span>Cost</span>
                            <span>Offer</span>
                            <span>Final Offer Price</span>
                        </div>
                        <div class="offer-total-values">
                            <span></span>
                            <strong id="offerTotalCostDisplay">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</strong>
                            <strong id="offerTotalDiscountDisplay">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</strong>
                            <strong id="offerFinalPriceDisplay">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong>
                        </div>
                    </div>

                    <input type="hidden" name="offer_total_cost" id="offer_total_cost" value="{{ $selectedOfferTotalCost }}">
                    <input type="hidden" name="offer_total_discount" id="offer_total_discount" value="{{ $selectedOfferTotalDiscount }}">
                    <input type="hidden" name="offer_final_price" id="offer_final_price" value="{{ $selectedOfferFinalPrice }}">
                </div>
            </section>

            <section class="booking-section booking-form-section {{ $currentStep === 5 ? 'active' : '' }}">
                <h3 class="section-heading">Part 5 - Booking Form</h3>
                <div class="booking-review-stack">
                    <article class="booking-review-card">
                        <h4>Enquiry Details</h4>
                        <div class="booking-review-grid">
                            <p><strong>Name</strong><span>{{ $summaryName }}</span></p>
                            <p><strong>Mobile No</strong><span>{{ $summaryMobile }}</span></p>
                            <p><strong>Interested In</strong><span>{{ strtoupper($interestedVehicleLine) }}</span></p>
                        </div>
                    </article>

                    <article class="booking-review-card">
                        <h4>Personal Details</h4>
                        <div class="booking-review-grid">
                            <p><strong>DOB</strong><span>{{ $selectedDob ?: 'N/A' }}</span></p>
                            <p><strong>District</strong><span>{{ $selectedDistrict ?: 'N/A' }}</span></p>
                            <p><strong>Location</strong><span>{{ $selectedLocation ?: 'N/A' }}</span></p>
                            <p><strong>Address</strong><span>{{ $selectedAddress1 ?: 'N/A' }}</span></p>
                            <p><strong>Customer Type</strong><span>{{ ucfirst(str_replace('_', ' ', $selectedCustomerType ?: 'N/A')) }}</span></p>
                            <p><strong>Profession</strong><span>{{ ucfirst(str_replace('_', ' ', $selectedProfession ?: 'N/A')) }}</span></p>
                        </div>
                    </article>

                    <article class="booking-review-card">
                        <h4>Buying Details</h4>
                        <div class="booking-review-grid">
                            <p><strong>Quote Taken</strong><span>{{ ucfirst($selectedQuote ?: 'N/A') }}</span></p>
                            <p><strong>Test Drive</strong><span>{{ ucfirst($selectedTestDrive ?: 'N/A') }}</span></p>
                            <p><strong>Purchase Mode</strong><span>{{ ucfirst($selectedPurchaseMode ?: 'N/A') }}</span></p>
                            <p><strong>Finance Form</strong><span>{{ $selectedFinanceForm ?: 'N/A' }}</span></p>
                            <p><strong>Competition</strong><span>{{ ucfirst($selectedCompetition ?: 'N/A') }}</span></p>
                            <p><strong>First Time Buyer</strong><span>{{ ucfirst($selectedFirstTimeBuyer ?: 'N/A') }}</span></p>
                        </div>
                    </article>

                    <article class="booking-review-card">
                        <h4>Exchange Details</h4>
                        <div class="booking-review-grid">
                            <p><strong>Interested In Exchange</strong><span>{{ ucfirst($selectedInterestedExchange ?: 'N/A') }}</span></p>
                            <p><strong>Exchange Type</strong><span>{{ $selectedExchangeType ? ucfirst(str_replace('_', '-', $selectedExchangeType)) : 'N/A' }}</span></p>
                            <p><strong>Vehicle</strong><span>{{ strtoupper($exchangeVehicleLine) }}</span></p>
                            <p><strong>Expected Price</strong><span>{{ $money($selectedExchangeExpectedPrice) }}</span></p>
                            <p><strong>Quoted Price</strong><span>{{ $money($selectedExchangeQuotedPrice) }}</span></p>
                            <p><strong>Difference</strong><span>{{ $money($selectedExchangeDifference) }}</span></p>
                        </div>
                    </article>

                    <article class="booking-review-card">
                        <h4>Offer Details</h4>
                        <div class="booking-review-grid">
                            <p><strong>Total Cost</strong><span>{{ $money($selectedOfferTotalCost) }}</span></p>
                            <p><strong>Total Discount</strong><span>{{ $money($selectedOfferTotalDiscount) }}</span></p>
                            <p><strong>Final Offer Price</strong><span>{{ $money($selectedOfferFinalPrice) }}</span></p>
                            <p><strong>Purchase Order</strong><span>{{ empty($booking->purchase_order_image) ? 'Not uploaded' : 'Uploaded' }}</span></p>
                        </div>
                    </article>
                </div>
            </section>

            <div id="actionRow" class="action-row {{ $currentStep === 5 ? 'step-five' : '' }} {{ $isExchangeNoMode ? 'next-only' : '' }}">
                <a id="backAction" href="{{ $backUrl }}" class="action-btn back-btn {{ $isExchangeNoMode ? 'hidden' : '' }}">Back</a>
                @if($currentStep === 5)
                    <button type="submit" name="action_type" value="save" class="action-btn save-action-btn">Save</button>
                    <button id="saveExitAction" type="submit" name="action_type" value="save_exit" class="action-btn save-exit-btn {{ $isExchangeNoMode ? 'hidden' : '' }}">Save & Exit</button>
                    <button type="submit" name="action_type" value="submit" class="action-btn submit-action-btn">Submit</button>
                @else
                    <button id="saveExitAction" type="submit" name="action_type" value="save_exit" class="action-btn save-exit-btn {{ $isExchangeNoMode ? 'hidden' : '' }}">Save & Exit</button>
                    <button type="submit" name="action_type" value="next" class="action-btn next-action-btn">Save & Next</button>
                @endif
            </div>
        </form>
    </main>
</div>

@if(session('booking_submitted_popup'))
    <div class="booking-submit-popup" id="bookingSubmitPopup" role="dialog" aria-modal="true" aria-labelledby="bookingSubmitTitle">
        <div class="booking-submit-popup-card">
            <div class="booking-submit-icon" aria-hidden="true">&#10003;</div>
            <h4 id="bookingSubmitTitle">Submitted Successfully</h4>
            <p>{{ session('booking_submitted_message', 'Booking submitted successfully.') }}</p>
            <button type="button" class="booking-submit-popup-btn" id="bookingSubmitPopupOk">OK</button>
        </div>
    </div>
@endif

<script type="application/json" id="bookingCompetitionMapJson">@json($competitionMap->toArray())</script>
<script>
    (function initBookingCompetitionMap() {
        const mapEl = document.getElementById('bookingCompetitionMapJson');
        if (!mapEl) {
            window.BOOKING_COMPETITION_MAP = {};
            return;
        }

        try {
            window.BOOKING_COMPETITION_MAP = JSON.parse(mapEl.textContent || '{}');
        } catch (e) {
            window.BOOKING_COMPETITION_MAP = {};
        }
    })();

    (function () {
        const toggle = document.getElementById('sameAsToggle');
        const editBlock = document.getElementById('editBlock');
        const bookingSameAsCustomerInput = document.getElementById('bookingSameAsCustomer');
        const personalEditableInputs = document.querySelectorAll('[data-personal-editable]');
        const corporateNameRow = document.getElementById('corporateNameRow');
        const toggleBuyingVehicleEdit = document.getElementById('toggleBuyingVehicleEdit');
        const vehicleEditFields = document.getElementById('vehicleEditFields');
        const vehicleReadPill = document.getElementById('vehicleReadPill');
        const interestedModelInput = document.getElementById('interested_model');
        const interestedEngineInput = document.getElementById('interested_engine');
        const interestedVariantInput = document.getElementById('interested_variant');
        const quoteDateWrap = document.getElementById('quoteDateWrap');
        const testDriveYesWrap = document.getElementById('testDriveYesWrap');
        const testDriveNoWrap = document.getElementById('testDriveNoWrap');
        const financeFormWrap = document.getElementById('financeFormWrap');
        const competitionWrap = document.getElementById('competitionWrap');
        const competitionBrandSelect = document.getElementById('competition_brand');
        const competitionModelSelect = document.getElementById('competition_model');
        const existingVehicleWrap = document.getElementById('existingVehicleWrap');
        const existingVehicleBrandSelect = document.getElementById('existing_vehicle_brand');
        const existingVehicleModelSelect = document.getElementById('existing_vehicle_model');
        const exchangeTypeRow = document.getElementById('exchangeTypeRow');
        const exchangeDetailsWrap = document.getElementById('exchangeDetailsWrap');
        const toggleExchangeEdit = document.getElementById('toggleExchangeEdit');
        const exchangeEditFields = document.getElementById('exchangeEditFields');
        const exchangeBrandSelect = document.getElementById('exchange_vehicle_brand');
        const exchangeModelSelect = document.getElementById('exchange_vehicle_model');
        const exchangeExpectedPriceInput = document.getElementById('exchange_expected_price');
        const exchangeQuotedPriceInput = document.getElementById('exchange_quoted_price');
        const exchangeDifferenceInput = document.getElementById('exchange_price_difference');
        const bookingImagesToggle = document.getElementById('bookingImagesToggle');
        const bookingImageBody = document.getElementById('bookingImageBody');
        const bookingAddMoreImagesBtn = document.getElementById('bookingAddMoreImagesBtn');
        const bookingExtraImageGrid = document.getElementById('bookingExtraImageGrid');
        const exchangePreviewObjectUrls = new WeakMap();
        const toggleOfferEdit = document.getElementById('toggleOfferEdit');
        const offerEditGroup = document.getElementById('offerEditGroup');
        const offerUnitPriceInput = document.getElementById('offer_unit_price');
        const offerUnitPriceDiscountInput = document.getElementById('offer_unit_price_discount');
        const offerUnitPriceFreeInput = document.getElementById('offer_unit_price_free');
        const offerVatAmountInput = document.getElementById('offer_vat_amount');
        const offerVatDiscountInput = document.getElementById('offer_vat_discount');
        const offerVatFreeInput = document.getElementById('offer_vat_free');
        const offerTotalCostInput = document.getElementById('offer_total_cost');
        const offerTotalDiscountInput = document.getElementById('offer_total_discount');
        const offerFinalPriceInput = document.getElementById('offer_final_price');
        const offerTotalCostDisplay = document.getElementById('offerTotalCostDisplay');
        const offerTotalDiscountDisplay = document.getElementById('offerTotalDiscountDisplay');
        const offerFinalPriceDisplay = document.getElementById('offerFinalPriceDisplay');
        const offerSummaryPanel = document.getElementById('offerSummaryPanel');
        const offerSummaryVatCost = document.getElementById('offerSummaryVatCost');
        const offerSummaryVatOffer = document.getElementById('offerSummaryVatOffer');
        const offerSummaryVatPayable = document.getElementById('offerSummaryVatPayable');
        const offerSummaryUnitCost = document.getElementById('offerSummaryUnitCost');
        const offerSummaryUnitOffer = document.getElementById('offerSummaryUnitOffer');
        const offerSummaryUnitPayable = document.getElementById('offerSummaryUnitPayable');
        const offerSummaryTotalCost = document.getElementById('offerSummaryTotalCost');
        const offerSummaryTotalOffer = document.getElementById('offerSummaryTotalOffer');
        const offerSummaryTotalPayable = document.getElementById('offerSummaryTotalPayable');
        const offerRemarksToggle = document.getElementById('offerRemarksToggle');
        const offerRemarksText = document.getElementById('offerRemarksText');
        const bookingStepInput = document.querySelector('input[name="booking_step"]');
        const actionRow = document.getElementById('actionRow');
        const backAction = document.getElementById('backAction');
        const saveExitAction = document.getElementById('saveExitAction');

        function syncEditState() {
            if (!toggle || !editBlock) return;
            const editable = toggle.checked;

            editBlock.classList.toggle('read-only', !editable);
            if (bookingSameAsCustomerInput) {
                bookingSameAsCustomerInput.value = editable ? '0' : '1';
            }

            personalEditableInputs.forEach((input) => {
                if (input.tagName === 'INPUT' && input.type === 'radio') {
                    input.disabled = !editable;
                    return;
                }

                if (input.tagName === 'SELECT') {
                    input.disabled = !editable;
                    return;
                }

                if (input.tagName === 'INPUT') {
                    input.readOnly = !editable;
                }
            });
        }

        function syncCorporateRow() {
            if (!corporateNameRow) return;
            corporateNameRow.classList.toggle('hidden', picked('customer_type') !== 'corporate');
        }

        function syncVehicleEditState() {
            if (!toggleBuyingVehicleEdit || !vehicleEditFields) return;
            vehicleEditFields.classList.toggle('hidden', !toggleBuyingVehicleEdit.checked);
        }

        function syncVehiclePill() {
            if (!vehicleReadPill) return;
            const parts = [
                interestedModelInput ? interestedModelInput.value.trim() : '',
                interestedEngineInput ? interestedEngineInput.value.trim() : '',
                interestedVariantInput ? interestedVariantInput.value.trim() : '',
            ].filter(Boolean);

            vehicleReadPill.textContent = parts.length ? parts.join(' / ') : 'Not selected';
        }

        function setSelectOptions(selectEl, values, placeholder, selectedValue) {
            if (!selectEl) return;
            const safeSelected = selectedValue || '';

            let html = '<option value="">' + placeholder + '</option>';
            values.forEach((value) => {
                const selected = value === safeSelected ? ' selected' : '';
                html += '<option value="' + value + '"' + selected + '>' + value + '</option>';
            });

            if (safeSelected && !values.includes(safeSelected)) {
                html += '<option value="' + safeSelected + '" selected>' + safeSelected + '</option>';
            }

            selectEl.innerHTML = html;
        }

        async function loadEngines(model, selectedEngine) {
            if (!interestedEngineInput || !interestedVariantInput) return;
            if (!model) {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', '');
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
                syncVehiclePill();
                return;
            }

            try {
                const res = await fetch('/get-engines/' + encodeURIComponent(model));
                const data = await res.json();
                const engines = data.map((item) => item.engine_type).filter(Boolean);
                setSelectOptions(interestedEngineInput, engines, 'Select Engine Type', selectedEngine || '');
            } catch (e) {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', selectedEngine || '');
            }

            syncVehiclePill();
        }

        async function loadVariants(model, engine, selectedVariant) {
            if (!interestedVariantInput) return;
            if (!model || !engine) {
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
                syncVehiclePill();
                return;
            }

            try {
                const res = await fetch('/get-variants/' + encodeURIComponent(model) + '/' + encodeURIComponent(engine));
                const data = await res.json();
                const variants = data.map((item) => item.variant).filter(Boolean);
                setSelectOptions(interestedVariantInput, variants, 'Select Variant', selectedVariant || '');
            } catch (e) {
                setSelectOptions(interestedVariantInput, [], 'Select Variant', selectedVariant || '');
            }

            syncVehiclePill();
        }

        function picked(name) {
            const selected = document.querySelector('input[name="' + name + '"]:checked');
            return selected ? selected.value : '';
        }

        function syncBuyingState() {
            if (quoteDateWrap) {
                quoteDateWrap.classList.toggle('hidden', picked('quote_taken') !== 'yes');
            }

            if (testDriveYesWrap) {
                testDriveYesWrap.classList.toggle('hidden', picked('test_drive_given') !== 'yes');
            }

            if (testDriveNoWrap) {
                testDriveNoWrap.classList.toggle('hidden', picked('test_drive_given') !== 'no');
            }

            if (financeFormWrap) {
                financeFormWrap.classList.toggle('hidden', picked('purchase_mode') !== 'finance');
            }

            if (competitionWrap) {
                competitionWrap.classList.toggle('hidden', picked('interested_in_competition') !== 'yes');
            }

            if (existingVehicleWrap) {
                existingVehicleWrap.classList.toggle('hidden', picked('first_time_buyer') !== 'no');
            }

            if (exchangeTypeRow) {
                const showExchangeType = picked('interested_in_exchange') === 'yes';
                exchangeTypeRow.classList.toggle('hidden', !showExchangeType);

                const exchangeTypeInputs = exchangeTypeRow.querySelectorAll('input, select, textarea, button');
                exchangeTypeInputs.forEach((input) => {
                    input.disabled = !showExchangeType;
                });

                if (!showExchangeType) {
                    const exchangeTypeRadios = exchangeTypeRow.querySelectorAll('input[name="exchange_type"]');
                    exchangeTypeRadios.forEach((radio) => {
                        radio.checked = false;
                    });
                }
            }

            if (exchangeDetailsWrap) {
                const showExchangeDetails = picked('interested_in_exchange') === 'yes'
                    && ['in_house', 'outhouse'].includes(picked('exchange_type'));
                exchangeDetailsWrap.classList.toggle('hidden', !showExchangeDetails);

                // Show full exchange input fields when Yes + (In-House/Outhouse),
                // regardless of Edit checkbox state.
                if (exchangeEditFields) {
                    exchangeEditFields.classList.toggle('hidden', !showExchangeDetails);
                }
            }

            syncExchangeNoActionMode();
        }

        function syncExchangeEditState() {
            if (!exchangeDetailsWrap || !exchangeEditFields) return;
            const showExchangeDetails = !exchangeDetailsWrap.classList.contains('hidden');
            exchangeEditFields.classList.toggle('hidden', !showExchangeDetails);
        }

        function syncCompetitionModels() {
            if (!competitionBrandSelect || !competitionModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = competitionBrandSelect.value || '';
            const selectedModel = competitionModelSelect.dataset.selectedModel || competitionModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(competitionModelSelect, models, 'Select Model', selectedModel);
            competitionModelSelect.dataset.selectedModel = '';
        }

        function syncExistingVehicleModels() {
            if (!existingVehicleBrandSelect || !existingVehicleModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = existingVehicleBrandSelect.value || '';
            const selectedModel = existingVehicleModelSelect.dataset.selectedModel || existingVehicleModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(existingVehicleModelSelect, models, 'Select Model', selectedModel);
            existingVehicleModelSelect.dataset.selectedModel = '';
        }

        function syncExchangeModels() {
            if (!exchangeBrandSelect || !exchangeModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = exchangeBrandSelect.value || '';
            const selectedModel = exchangeModelSelect.dataset.selectedModel || exchangeModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(exchangeModelSelect, models, 'Select Model', selectedModel);
            exchangeModelSelect.dataset.selectedModel = '';
        }

        function syncExchangeDifference() {
            if (!exchangeExpectedPriceInput || !exchangeQuotedPriceInput || !exchangeDifferenceInput) return;

            const expected = parseFloat(exchangeExpectedPriceInput.value || '0');
            const quoted = parseFloat(exchangeQuotedPriceInput.value || '0');

            if (Number.isNaN(expected) || Number.isNaN(quoted)) {
                exchangeDifferenceInput.value = '';
                return;
            }

            exchangeDifferenceInput.value = (expected - quoted).toFixed(2);
        }

        function renumberExtraImageTiles() {
            if (!bookingExtraImageGrid) return;
            const tiles = bookingExtraImageGrid.querySelectorAll('.exchange-upload-tile-extra');
            tiles.forEach((tile, index) => {
                const title = tile.querySelector('.exchange-upload-title');
                if (title) {
                    title.textContent = `Car picture ${index + 3}`;
                }
            });
        }

        function addExtraImageTile() {
            if (!bookingExtraImageGrid) return;

            const tile = document.createElement('div');
            tile.className = 'exchange-upload-tile exchange-upload-tile-extra';
            tile.innerHTML = `
                <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                <span class="exchange-upload-title"></span>
                <img class="exchange-upload-preview" alt="Extra exchange preview" hidden>
                <button type="button" class="exchange-preview-clear" aria-label="Remove selected image">&times;</button>
                <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
            `;

            bookingExtraImageGrid.appendChild(tile);
            const fileInput = tile.querySelector('.exchange-file-input');
            if (fileInput) {
                bindExchangeUploadPreview(fileInput);
            }
            renumberExtraImageTiles();
        }

        function applyExchangePreviewToTile(inputEl, sourceUrl) {
            const tile = inputEl.closest('.exchange-upload-tile');
            if (!tile) return;
            const previewEl = tile.querySelector('.exchange-upload-preview');

            if (!previewEl || !sourceUrl) {
                tile.classList.remove('has-preview');
                if (previewEl) {
                    previewEl.hidden = true;
                    previewEl.removeAttribute('src');
                }
                return;
            }

            previewEl.src = sourceUrl;
            previewEl.hidden = false;
            tile.classList.add('has-preview');
        }

        function clearExchangeUploadPreview(inputEl) {
            if (!inputEl) return;
            const previousObjectUrl = exchangePreviewObjectUrls.get(inputEl);
            if (previousObjectUrl) {
                URL.revokeObjectURL(previousObjectUrl);
                exchangePreviewObjectUrls.delete(inputEl);
            }
            inputEl.value = '';
            applyExchangePreviewToTile(inputEl, '');
        }

        function bindExchangeUploadPreview(inputEl) {
            if (!inputEl) return;

            inputEl.addEventListener('change', () => {
                const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
                if (!file) {
                    clearExchangeUploadPreview(inputEl);
                    return;
                }

                if (!String(file.type || '').startsWith('image/')) {
                    alert('Please choose a valid image file.');
                    clearExchangeUploadPreview(inputEl);
                    return;
                }

                const previousObjectUrl = exchangePreviewObjectUrls.get(inputEl);
                if (previousObjectUrl) {
                    URL.revokeObjectURL(previousObjectUrl);
                    exchangePreviewObjectUrls.delete(inputEl);
                }

                const objectUrl = URL.createObjectURL(file);
                exchangePreviewObjectUrls.set(inputEl, objectUrl);
                applyExchangePreviewToTile(inputEl, objectUrl);
            });
        }

        function syncBookingImageBody() {
            if (!bookingImagesToggle || !bookingImageBody) return;
            bookingImageBody.classList.toggle('hidden', !bookingImagesToggle.checked);
        }

        function syncExchangeNoActionMode() {
            if (!bookingStepInput || bookingStepInput.value !== '3') return;

            const onlyNext = picked('interested_in_exchange') === 'no';
            if (actionRow) {
                actionRow.classList.toggle('next-only', onlyNext);
            }
            if (backAction) {
                backAction.classList.toggle('hidden', onlyNext);
            }
            if (saveExitAction) {
                saveExitAction.classList.toggle('hidden', onlyNext);
            }
        }

        function toMoney(value) {
            const n = parseFloat(value || '0');
            return Number.isNaN(n) ? 0 : Math.max(0, n);
        }

        function formatOfferMoney(value) {
            return Math.round(toMoney(value)).toLocaleString('en-US');
        }

        function syncOfferReadonlyState() {
            if (!toggleOfferEdit || !offerEditGroup) return;

            const editable = toggleOfferEdit.checked;
            offerEditGroup.classList.toggle('hidden', !editable);
            if (offerSummaryPanel) {
                offerSummaryPanel.classList.toggle('hidden', editable);
            }

            const targets = offerEditGroup.querySelectorAll(
                'input[type="number"], input[type="checkbox"]'
            );

            targets.forEach((el) => {
                if (el === offerTotalCostInput || el === offerTotalDiscountInput || el === offerFinalPriceInput) {
                    el.readOnly = true;
                    return;
                }

                if (el.type === 'checkbox') {
                    el.disabled = !editable;
                } else {
                    el.readOnly = !editable;
                }
            });
        }

        function syncOfferRemarksState() {
            if (!offerRemarksToggle || !offerRemarksText) return;

            offerRemarksText.classList.toggle('hidden', !offerRemarksToggle.checked);
        }

        function syncOfferTotals() {
            if (!offerUnitPriceInput || !offerVatAmountInput) return;

            const unit = toMoney(offerUnitPriceInput.value);
            const vat = toMoney(offerVatAmountInput.value);
            let unitDiscount = toMoney(offerUnitPriceDiscountInput ? offerUnitPriceDiscountInput.value : 0);
            let vatDiscount = toMoney(offerVatDiscountInput ? offerVatDiscountInput.value : 0);
            const unitFree = !!(offerUnitPriceFreeInput && offerUnitPriceFreeInput.checked);
            const vatFree = !!(offerVatFreeInput && offerVatFreeInput.checked);

            if (unitFree) {
                unitDiscount = unit;
                if (offerUnitPriceDiscountInput) {
                    offerUnitPriceDiscountInput.value = unit.toFixed(2);
                }
            } else {
                unitDiscount = Math.min(unitDiscount, unit);
                if (offerUnitPriceDiscountInput) {
                    offerUnitPriceDiscountInput.value = unitDiscount.toFixed(2);
                }
            }

            if (vatFree) {
                vatDiscount = vat;
                if (offerVatDiscountInput) {
                    offerVatDiscountInput.value = vat.toFixed(2);
                }
            } else {
                vatDiscount = Math.min(vatDiscount, vat);
                if (offerVatDiscountInput) {
                    offerVatDiscountInput.value = vatDiscount.toFixed(2);
                }
            }

            const totalCost = unit + vat;
            const totalDiscount = unitDiscount + vatDiscount;
            const finalPrice = Math.max(0, totalCost - totalDiscount);

            if (offerTotalCostInput) offerTotalCostInput.value = totalCost.toFixed(2);
            if (offerTotalDiscountInput) offerTotalDiscountInput.value = totalDiscount.toFixed(2);
            if (offerFinalPriceInput) offerFinalPriceInput.value = finalPrice.toFixed(2);

            if (offerTotalCostDisplay) offerTotalCostDisplay.textContent = formatOfferMoney(totalCost);
            if (offerTotalDiscountDisplay) offerTotalDiscountDisplay.textContent = formatOfferMoney(totalDiscount);
            if (offerFinalPriceDisplay) offerFinalPriceDisplay.textContent = formatOfferMoney(finalPrice);

            if (offerSummaryVatCost) offerSummaryVatCost.textContent = formatOfferMoney(vat);
            if (offerSummaryVatOffer) offerSummaryVatOffer.textContent = formatOfferMoney(vatDiscount);
            if (offerSummaryVatPayable) offerSummaryVatPayable.textContent = formatOfferMoney(Math.max(0, vat - vatDiscount));
            if (offerSummaryUnitCost) offerSummaryUnitCost.textContent = formatOfferMoney(unit);
            if (offerSummaryUnitOffer) offerSummaryUnitOffer.textContent = formatOfferMoney(unitDiscount);
            if (offerSummaryUnitPayable) offerSummaryUnitPayable.textContent = formatOfferMoney(Math.max(0, unit - unitDiscount));
            if (offerSummaryTotalCost) offerSummaryTotalCost.textContent = formatOfferMoney(totalCost);
            if (offerSummaryTotalOffer) offerSummaryTotalOffer.textContent = formatOfferMoney(totalDiscount);
            if (offerSummaryTotalPayable) offerSummaryTotalPayable.textContent = formatOfferMoney(finalPrice);
        }

        if (toggle) {
            toggle.addEventListener('change', syncEditState);
            syncEditState();
        }

        document.querySelectorAll('input[name="customer_type"]').forEach((input) => {
            input.addEventListener('change', syncCorporateRow);
        });

        if (toggleBuyingVehicleEdit) {
            toggleBuyingVehicleEdit.addEventListener('change', syncVehicleEditState);
            syncVehicleEditState();
        }

        if (interestedModelInput) {
            interestedModelInput.addEventListener('change', async function () {
                await loadEngines(this.value, '');
                await loadVariants(this.value, '', '');
                syncVehiclePill();
            });
        }

        if (interestedEngineInput) {
            interestedEngineInput.addEventListener('change', async function () {
                const model = interestedModelInput ? interestedModelInput.value : '';
                await loadVariants(model, this.value, '');
                syncVehiclePill();
            });
        }

        if (interestedVariantInput) {
            interestedVariantInput.addEventListener('change', syncVehiclePill);
        }

        (async function initVehicleDropdowns() {
            if (!interestedModelInput) return;

            const initialModel = interestedModelInput.dataset.selectedModel || interestedModelInput.value || '';
            const initialEngine = interestedEngineInput ? (interestedEngineInput.dataset.selectedEngine || '') : '';
            const initialVariant = interestedVariantInput ? (interestedVariantInput.dataset.selectedVariant || '') : '';

            if (initialModel) {
                interestedModelInput.value = initialModel;
                await loadEngines(initialModel, initialEngine);
                if (initialEngine) {
                    await loadVariants(initialModel, initialEngine, initialVariant);
                }
            } else {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', '');
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
            }

            syncVehiclePill();
        })();

        document.querySelectorAll(
            'input[name="quote_taken"], input[name="test_drive_given"], input[name="purchase_mode"], input[name="interested_in_exchange"], input[name="exchange_type"], input[name="interested_in_competition"], input[name="first_time_buyer"]'
        ).forEach((input) => {
            input.addEventListener('change', syncBuyingState);
        });

        if (competitionBrandSelect) {
            competitionBrandSelect.addEventListener('change', function () {
                if (competitionModelSelect) {
                    competitionModelSelect.dataset.selectedModel = '';
                }
                syncCompetitionModels();
            });
        }

        if (existingVehicleBrandSelect) {
            existingVehicleBrandSelect.addEventListener('change', function () {
                if (existingVehicleModelSelect) {
                    existingVehicleModelSelect.dataset.selectedModel = '';
                }
                syncExistingVehicleModels();
            });
        }

        if (toggleExchangeEdit) {
            toggleExchangeEdit.addEventListener('change', syncExchangeEditState);
            syncExchangeEditState();
        }

        if (exchangeBrandSelect) {
            exchangeBrandSelect.addEventListener('change', function () {
                if (exchangeModelSelect) {
                    exchangeModelSelect.dataset.selectedModel = '';
                }
                syncExchangeModels();
            });
        }

        if (bookingAddMoreImagesBtn) {
            bookingAddMoreImagesBtn.addEventListener('click', addExtraImageTile);
        }

        if (bookingImagesToggle) {
            bookingImagesToggle.addEventListener('change', syncBookingImageBody);
        }

        if (bookingExtraImageGrid) {
            bookingExtraImageGrid.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const clearBtn = target.closest('.exchange-preview-clear');
                if (clearBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const tile = clearBtn.closest('.exchange-upload-tile');
                    const fileInput = tile ? tile.querySelector('.exchange-file-input') : null;
                    if (fileInput) {
                        clearExchangeUploadPreview(fileInput);
                    }
                    return;
                }

                const removeBtn = target.closest('.exchange-remove-btn');
                if (!removeBtn) return;

                event.preventDefault();
                event.stopPropagation();

                const tile = removeBtn.closest('.exchange-upload-tile-extra');
                if (!tile) return;
                const fileInput = tile.querySelector('.exchange-file-input');
                if (fileInput) {
                    clearExchangeUploadPreview(fileInput);
                }
                tile.remove();
                renumberExtraImageTiles();
            });
        }

        if (bookingImageBody) {
            bookingImageBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                if (target.closest('#bookingExtraImageGrid')) return;

                const clearBtn = target.closest('.exchange-preview-clear');
                if (!clearBtn) return;

                event.preventDefault();
                event.stopPropagation();

                const tile = clearBtn.closest('.exchange-upload-tile');
                const fileInput = tile ? tile.querySelector('.exchange-file-input') : null;
                if (fileInput) {
                    clearExchangeUploadPreview(fileInput);
                }
            });
        }

        [exchangeExpectedPriceInput, exchangeQuotedPriceInput].forEach((el) => {
            if (el) {
                el.addEventListener('input', syncExchangeDifference);
            }
        });

        [offerUnitPriceInput, offerUnitPriceDiscountInput, offerVatAmountInput, offerVatDiscountInput].forEach((el) => {
            if (el) {
                el.addEventListener('input', syncOfferTotals);
            }
        });

        [offerUnitPriceFreeInput, offerVatFreeInput].forEach((el) => {
            if (el) {
                el.addEventListener('change', syncOfferTotals);
            }
        });

        if (toggleOfferEdit) {
            toggleOfferEdit.addEventListener('change', syncOfferReadonlyState);
            syncOfferReadonlyState();
        }

        if (offerRemarksToggle) {
            offerRemarksToggle.addEventListener('change', syncOfferRemarksState);
            syncOfferRemarksState();
        }

        syncBuyingState();
        syncCompetitionModels();
        syncExistingVehicleModels();
        syncExchangeModels();
        syncCorporateRow();
        syncExchangeDifference();
        syncOfferTotals();
        syncExchangeNoActionMode();
        renumberExtraImageTiles();
        syncBookingImageBody();
        document.querySelectorAll('.exchange-file-input').forEach((inputEl) => {
            bindExchangeUploadPreview(inputEl);
        });
    })();
</script>
@if(session('booking_submitted_popup'))
<script>
    (() => {
        const popup = document.getElementById('bookingSubmitPopup');
        if (!popup) return;

        const okBtn = document.getElementById('bookingSubmitPopupOk');
        const redirectUrl = @json(url('/epr'));
        const goToEpr = () => {
            window.location.href = redirectUrl;
        };

        okBtn?.addEventListener('click', goToEpr);
        setTimeout(goToEpr, 1800);
    })();
</script>
@endif
@endsection
